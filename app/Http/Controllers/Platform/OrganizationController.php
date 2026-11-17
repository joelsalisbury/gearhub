<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $searchTerm = trim($request->string('q')->toString());

        $organizations = Organization::query()
            ->with('parent')
            ->withCount('users')
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $query->where('name', 'like', '%'.$searchTerm.'%');
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('platform.organizations.index', [
            'organizations' => $organizations,
            'searchTerm' => $searchTerm,
        ]);
    }

    public function create(): View
    {
        return view('platform.organizations.create', [
            'organization' => new Organization,
            'parents' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = Organization::create($request->validate($this->rules()));

        return redirect()
            ->route('platform.organizations.show', $organization)
            ->with('status', 'Organization created.');
    }

    public function show(Organization $organization): View
    {
        $organization->load(['parent', 'users']);

        return view('platform.organizations.show', [
            'organization' => $organization,
        ]);
    }

    public function edit(Organization $organization): View
    {
        return view('platform.organizations.edit', [
            'organization' => $organization,
            'parents' => Organization::query()
                ->where('id', '!=', $organization->getKey())
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $previousSlug = $organization->slug;
        $organization->update($request->validate($this->rules($organization)));
        $status = 'Organization updated.';

        if ($previousSlug !== $organization->slug) {
            $status .= ' Existing links using the previous slug may no longer work.';
        }

        return redirect()
            ->route('platform.organizations.show', $organization)
            ->with('status', $status);
    }

    public function toggleStatus(Organization $organization): RedirectResponse
    {
        $organization->forceFill([
            'is_active' => ! $organization->isActive(),
        ])->save();

        $status = $organization->isActive()
            ? 'Organization reactivated.'
            : 'Organization deactivated.';

        return redirect()
            ->route('platform.organizations.show', $organization)
            ->with('status', $status);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?Organization $organization = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('organizations', 'slug')->ignore($organization),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'timezone' => ['required', 'timezone'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:organizations,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($organization): void {
                    if ($organization === null || ! is_numeric($value)) {
                        return;
                    }

                    $ancestorId = (int) $value;

                    while ($ancestorId !== 0) {
                        if ($ancestorId === $organization->getKey()) {
                            $fail('An organization cannot be its own ancestor.');

                            return;
                        }

                        $ancestorId = (int) Organization::query()
                            ->whereKey($ancestorId)
                            ->value('parent_id');
                    }
                },
            ],
        ];
    }
}
