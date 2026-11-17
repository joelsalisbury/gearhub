<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\GearItem;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicGearController extends Controller
{
    public function index(Request $request, Organization $organization): View
    {
        abort_unless($organization->isActive(), 404);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);
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

        return view('organizations.gear.index', [
            'organization' => $organization,
            'gearItems' => $query->paginate(12)->withQueryString(),
            'categories' => $organization->gearItems()
                ->where('status', 'active')
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'filters' => [
                'q' => $search,
                'category' => $category,
            ],
        ]);
    }

    public function show(Organization $organization, GearItem $gearItem): View
    {
        abort_unless($organization->isActive() && $gearItem->status === 'active', 404);

        return view('organizations.gear.show', [
            'organization' => $organization,
            'gearItem' => $gearItem,
        ]);
    }
}
