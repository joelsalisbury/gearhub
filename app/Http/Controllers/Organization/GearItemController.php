<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\GearItem;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class GearItemController extends Controller
{
    public function index(Request $request, Organization $organization): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,unavailable'],
        ]);

        $query = $organization->gearItems()->orderBy('name')->orderBy('id');
        $search = $filters['q'] ?? null;

        if (is_string($search) && $search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('identifier', 'like', '%'.$search.'%');
            });
        }

        $category = $filters['category'] ?? null;

        if (is_string($category) && $category !== '') {
            $query->where('category', $category);
        }

        $status = $filters['status'] ?? null;

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        return view('organizations.admin.gear.index', [
            'organization' => $organization,
            'gearItems' => $query->paginate(15)->withQueryString(),
            'categories' => $organization->gearItems()
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'filters' => [
                'q' => $search ?? '',
                'category' => $category ?? '',
                'status' => $status ?? '',
            ],
        ]);
    }

    public function create(Organization $organization): View
    {
        return view('organizations.admin.gear.create', [
            'organization' => $organization,
            'gearItem' => new GearItem([
                'quantity' => 1,
                'status' => 'active',
            ]),
        ]);
    }

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $photo = $request->file('photo');
        unset($data['photo']);

        if ($photo instanceof UploadedFile) {
            $data['image_path'] = $this->storePhoto($photo, $organization);
        }

        $organization->gearItems()->create($data);

        return redirect()
            ->route('organizations.admin.gear.index', ['organization' => $organization->slug])
            ->with('status', 'Gear added.');
    }

    public function edit(Organization $organization, GearItem $gearItem): View
    {
        return view('organizations.admin.gear.edit', [
            'organization' => $organization,
            'gearItem' => $gearItem,
        ]);
    }

    public function update(Request $request, Organization $organization, GearItem $gearItem): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $photo = $request->file('photo');
        $previousPhotoPath = $gearItem->image_path;
        unset($data['photo']);

        if ($photo instanceof UploadedFile) {
            $data['image_path'] = $this->storePhoto($photo, $organization);
        }

        $gearItem->update($data);

        if (
            $photo instanceof UploadedFile
            && $previousPhotoPath !== null
            && ! Storage::disk('public')->delete($previousPhotoPath)
        ) {
            throw new RuntimeException("Unable to remove the replaced gear photo [{$previousPhotoPath}].");
        }

        return redirect()
            ->route('organizations.admin.gear.edit', [
                'organization' => $organization->slug,
                'gearItem' => $gearItem,
            ])
            ->with('status', 'Gear updated.');
    }

    public function destroy(Organization $organization, GearItem $gearItem): RedirectResponse
    {
        $imagePath = $gearItem->image_path;
        $gearItem->delete();

        if ($imagePath !== null && ! Storage::disk('public')->delete($imagePath)) {
            throw new RuntimeException("Unable to remove the deleted gear photo [{$imagePath}].");
        }

        return redirect()
            ->route('organizations.admin.gear.index', ['organization' => $organization->slug])
            ->with('status', 'Gear deleted.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', 'in:active,unavailable'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function storePhoto(UploadedFile $photo, Organization $organization): string
    {
        $path = $photo->store('gear/'.$organization->getKey(), 'public');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Unable to store the uploaded gear photo.');
        }

        return $path;
    }
}
