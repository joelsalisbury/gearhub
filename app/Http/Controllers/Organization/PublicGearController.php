<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\GearItem;
use App\Models\Organization;
use App\Services\AvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicGearController extends Controller
{
    public function index(
        Request $request,
        Organization $organization,
        AvailabilityService $availabilityService,
    ): View {
        abort_unless($organization->isActive(), 404);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'pickup_date' => ['nullable', 'date_format:Y-m-d'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'return_date' => ['nullable', 'date_format:Y-m-d'],
            'return_time' => ['nullable', 'date_format:H:i'],
        ]);
        $period = $availabilityService->periodFromLocalInputs($filters, $organization->timezone);
        $search = trim($filters['q'] ?? '');
        $category = $filters['category'] ?? '';

        $query = $organization->gearItems()
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('id');

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        $gearItems = $query->paginate(12)->withQueryString();
        $periodInputs = $period?->localInputs($organization->timezone) ?? [];

        return view('organizations.gear.index', [
            'organization' => $organization,
            'gearItems' => $gearItems,
            'categories' => $organization->gearItems()
                ->where('status', 'active')
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'period' => $period,
            'periodInputs' => $periodInputs,
            'availableQuantities' => $period === null
                ? []
                : $availabilityService->availableQuantities(
                    $organization,
                    $gearItems->getCollection(),
                    $period,
                ),
            'filters' => [
                'q' => $search,
                'category' => $category,
            ],
        ]);
    }

    public function show(
        Request $request,
        Organization $organization,
        GearItem $gearItem,
        AvailabilityService $availabilityService,
    ): View {
        abort_unless($organization->isActive() && $gearItem->status === 'active', 404);

        $inputs = $request->validate([
            'pickup_date' => ['nullable', 'date_format:Y-m-d'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'return_date' => ['nullable', 'date_format:Y-m-d'],
            'return_time' => ['nullable', 'date_format:H:i'],
        ]);
        $period = $availabilityService->periodFromLocalInputs($inputs, $organization->timezone);

        return view('organizations.gear.show', [
            'organization' => $organization,
            'gearItem' => $gearItem,
            'period' => $period,
            'periodInputs' => $period?->localInputs($organization->timezone) ?? [],
            'availableQuantity' => $period === null
                ? null
                : $availabilityService->availableQuantity($organization, $gearItem, $period),
        ]);
    }
}
