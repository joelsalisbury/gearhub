<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $searchTerm = trim($validated['q'] ?? '');

        $organizations = Organization::query()
            ->where('is_active', true)
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $query->where(function (Builder $query) use ($searchTerm): void {
                    $query->where('name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('slug', 'like', '%'.$searchTerm.'%');
                });
            })
            ->when($searchTerm === '', fn (Builder $query) => $query->whereKey([]))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('home', [
            'organizations' => $organizations,
            'searchTerm' => $searchTerm,
        ]);
    }
}
