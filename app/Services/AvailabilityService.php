<?php

namespace App\Services;

use App\AvailabilityPeriod;
use App\Models\GearItem;
use App\Models\Organization;
use App\Models\ReservationItem;
use App\ReservationStatus;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    /**
     * @param  array<string, mixed>  $inputs
     */
    public function periodFromLocalInputs(array $inputs, string $timezone): ?AvailabilityPeriod
    {
        $fields = [
            'pickup_date',
            'pickup_time',
            'return_date',
            'return_time',
        ];
        $values = [];

        foreach ($fields as $field) {
            $value = $inputs[$field] ?? null;

            if ($value !== null && ! is_string($value)) {
                throw ValidationException::withMessages([
                    $field => 'Enter a valid date or time.',
                ]);
            }

            $values[$field] = $value;
        }

        if (count(array_filter($values, fn (?string $value): bool => $value !== null && $value !== '')) === 0) {
            return null;
        }

        foreach ($fields as $field) {
            if (! is_string($values[$field]) || $values[$field] === '') {
                throw ValidationException::withMessages([
                    $field => 'Enter both a pickup and return date and time.',
                ]);
            }
        }

        $startsAt = $this->parseLocalDateTime(
            $values['pickup_date'],
            $values['pickup_time'],
            $timezone,
            'pickup_time',
        );
        $endsAt = $this->parseLocalDateTime(
            $values['return_date'],
            $values['return_time'],
            $timezone,
            'return_time',
        );

        if ($startsAt->greaterThanOrEqualTo($endsAt)) {
            throw ValidationException::withMessages([
                'return_time' => 'The return date and time must be after pickup.',
            ]);
        }

        if ($startsAt->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            throw ValidationException::withMessages([
                'pickup_date' => 'Pickup must be in the future.',
            ]);
        }

        return new AvailabilityPeriod($startsAt, $endsAt);
    }

    /**
     * @param  iterable<GearItem>  $gearItems
     * @return array<int, int>
     */
    public function availableQuantities(
        Organization $organization,
        iterable $gearItems,
        AvailabilityPeriod $period,
    ): array {
        $available = [];
        $eligibleItemIds = [];

        foreach ($gearItems as $gearItem) {
            $available[$gearItem->id] = 0;

            if (
                (int) $gearItem->organization_id === (int) $organization->id
                && $gearItem->status === 'active'
                && $organization->isActive()
            ) {
                $available[$gearItem->id] = max(0, (int) $gearItem->quantity);
                $eligibleItemIds[] = $gearItem->id;
            }
        }

        if ($eligibleItemIds === []) {
            return $available;
        }

        $eventsByItem = [];
        $reservationItems = ReservationItem::query()
            ->whereIn('gear_item_id', $eligibleItemIds)
            ->whereHas('reservation', function (Builder $query) use ($organization, $period): void {
                $query->where('organization_id', $organization->id)
                    ->where('starts_at', '<', $period->endsAt)
                    ->where(function (Builder $query) use ($period): void {
                        $query->where(function (Builder $query) use ($period): void {
                            $query->whereIn('status', [
                                ReservationStatus::Pending->value,
                                ReservationStatus::Confirmed->value,
                            ])->where('ends_at', '>', $period->startsAt);
                        })->orWhere(function (Builder $query) use ($period): void {
                            $query->where('status', ReservationStatus::CheckedOut->value)
                                ->where(function (Builder $query) use ($period): void {
                                    $query->whereNull('returned_at')
                                        ->orWhere('returned_at', '>', $period->startsAt);
                                });
                        });
                    });
            })
            ->with('reservation:id,organization_id,starts_at,ends_at,status,returned_at')
            ->get();

        foreach ($reservationItems as $reservationItem) {
            $reservation = $reservationItem->reservation;

            if ($reservation === null) {
                continue;
            }

            $startsAt = max(
                $reservation->starts_at->getTimestamp(),
                $period->startsAt->getTimestamp(),
            );
            $reservationEndsAt = $reservation->status === ReservationStatus::CheckedOut
                ? $reservation->returned_at
                : $reservation->ends_at;

            if ($reservationEndsAt === null) {
                $endsAt = $period->endsAt->getTimestamp();
            } else {
                $endsAt = min(
                    $reservationEndsAt->getTimestamp(),
                    $period->endsAt->getTimestamp(),
                );
            }

            if ($endsAt <= $startsAt) {
                continue;
            }

            $quantity = max(0, (int) $reservationItem->quantity);
            $eventsByItem[$reservationItem->gear_item_id][$startsAt] =
                ($eventsByItem[$reservationItem->gear_item_id][$startsAt] ?? 0) + $quantity;
            $eventsByItem[$reservationItem->gear_item_id][$endsAt] =
                ($eventsByItem[$reservationItem->gear_item_id][$endsAt] ?? 0) - $quantity;
        }

        foreach ($eventsByItem as $gearItemId => $events) {
            // Applying net changes at each timestamp preserves half-open interval boundaries.
            ksort($events, SORT_NUMERIC);
            $inUse = 0;
            $maximumInUse = 0;

            foreach ($events as $quantityChange) {
                $inUse += $quantityChange;
                $maximumInUse = max($maximumInUse, $inUse);
            }

            $available[$gearItemId] = max(0, $available[$gearItemId] - $maximumInUse);
        }

        return $available;
    }

    public function availableQuantity(
        Organization $organization,
        GearItem $gearItem,
        AvailabilityPeriod $period,
    ): int {
        return $this->availableQuantities($organization, [$gearItem], $period)[$gearItem->id] ?? 0;
    }

    public function canAccommodate(
        Organization $organization,
        GearItem $gearItem,
        AvailabilityPeriod $period,
        int $requestedQuantity,
    ): bool {
        return $requestedQuantity > 0
            && $this->availableQuantity($organization, $gearItem, $period) >= $requestedQuantity;
    }

    private function parseLocalDateTime(
        string $date,
        string $time,
        string $timezone,
        string $errorField,
    ): CarbonImmutable {
        $wallClock = $date.' '.$time;
        $utcZone = new DateTimeZone('UTC');
        $wallClockDate = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $wallClock, $utcZone);
        $parseErrors = DateTimeImmutable::getLastErrors();

        if (
            $wallClockDate === false
            || ($parseErrors !== false && ($parseErrors['warning_count'] > 0 || $parseErrors['error_count'] > 0))
            || $wallClockDate->format('Y-m-d H:i') !== $wallClock
        ) {
            throw ValidationException::withMessages([
                $errorField => 'Enter a valid local date and time.',
            ]);
        }

        $timeZone = new DateTimeZone($timezone);
        $transitions = $timeZone->getTransitions(
            $wallClockDate->getTimestamp() - 3 * 24 * 60 * 60,
            $wallClockDate->getTimestamp() + 3 * 24 * 60 * 60,
        );
        $offsets = $transitions === false
            ? [$timeZone->getOffset($wallClockDate)]
            : array_unique(array_column($transitions, 'offset'));
        $matches = [];

        foreach ($offsets as $offset) {
            $timestamp = $wallClockDate->getTimestamp() - $offset;
            $candidate = (new DateTimeImmutable('@'.$timestamp))->setTimezone($timeZone);

            if ($candidate->format('Y-m-d H:i') === $wallClock) {
                $matches[$timestamp] = CarbonImmutable::instance($candidate)->utc();
            }
        }

        if (count($matches) !== 1) {
            throw ValidationException::withMessages([
                $errorField => count($matches) === 0
                    ? 'This local time does not exist because of a daylight-saving time change.'
                    : 'This local time is ambiguous because of a daylight-saving time change.',
            ]);
        }

        return array_values($matches)[0];
    }
}
