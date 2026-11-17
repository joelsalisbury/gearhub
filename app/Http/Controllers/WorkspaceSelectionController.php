<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceSelectionController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            return redirect()->route('platform.organizations.index');
        }

        $organizations = $user->organizations()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($organizations->count() === 1) {
            return redirect()->route('organizations.admin', [
                'organization' => $organizations->first()->slug,
            ]);
        }

        return view('workspaces.index', [
            'organizations' => $organizations,
        ]);
    }
}
