<?php

namespace App;

use Carbon\CarbonImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class AvailabilityPeriod
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {
        if ($startsAt->greaterThanOrEqualTo($endsAt)) {
            throw new InvalidArgumentException('An availability period must end after it starts.');
        }
    }

    /**
     * @return array{pickup_date: string, pickup_time: string, return_date: string, return_time: string}
     */
    public function localInputs(string $timezone): array
    {
        $localStart = $this->startsAt->setTimezone(new DateTimeZone($timezone));
        $localEnd = $this->endsAt->setTimezone(new DateTimeZone($timezone));

        return [
            'pickup_date' => $localStart->format('Y-m-d'),
            'pickup_time' => $localStart->format('H:i'),
            'return_date' => $localEnd->format('Y-m-d'),
            'return_time' => $localEnd->format('H:i'),
        ];
    }
}
