<?php

namespace Database\Seeders;

use App\Models\GearItem;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class GearItemSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'qcnemba')->first();

        if ($organization === null) {
            return;
        }

        $gearItems = [
            [
                'identifier' => 'BIKE-M',
                'name' => 'Trail bike · Medium',
                'description' => 'Adult mountain bike with a medium frame.',
                'category' => 'Bikes',
                'quantity' => 1,
                'status' => 'active',
                'notes' => null,
            ],
            [
                'identifier' => 'BIKE-L',
                'name' => 'Trail bike · Large',
                'description' => 'Adult mountain bike with a large frame.',
                'category' => 'Bikes',
                'quantity' => 1,
                'status' => 'active',
                'notes' => null,
            ],
            [
                'identifier' => 'HELMET-LOANER',
                'name' => 'Loaner helmet',
                'description' => 'Interchangeable adult mountain biking helmets.',
                'category' => 'Helmets',
                'quantity' => 10,
                'status' => 'active',
                'notes' => null,
            ],
            [
                'identifier' => 'TOOL-KIT',
                'name' => 'Trailside tool kit',
                'description' => 'Shared tools for basic trail-side adjustments.',
                'category' => 'Tools',
                'quantity' => 1,
                'status' => 'unavailable',
                'notes' => 'Unavailable while the kit is being checked.',
            ],
        ];

        foreach ($gearItems as $gearItem) {
            GearItem::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'identifier' => $gearItem['identifier'],
                ],
                $gearItem,
            );
        }
    }
}
