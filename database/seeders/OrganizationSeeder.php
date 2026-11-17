<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $nemba = Organization::updateOrCreate(
            ['slug' => 'nemba'],
            [
                'name' => 'NEMBA',
                'description' => null,
                'parent_id' => null,
                'timezone' => 'America/New_York',
            ],
        );

        Organization::updateOrCreate(
            ['slug' => 'qcnemba'],
            [
                'name' => 'Quiet Corner NEMBA',
                'description' => 'Mountain biking equipment and loaner bikes for Quiet Corner NEMBA riders.',
                'parent_id' => $nemba->id,
                'timezone' => 'America/New_York',
            ],
        );
    }
}
