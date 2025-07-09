<?php

namespace Database\Seeders;

use App\Models\GpSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GpSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GpSettings::updateOrCreate([
            'key' => 'driver_fee'
        ], [
            'int_value' => 25
        ]);
    }
}
