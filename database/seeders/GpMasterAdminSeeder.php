<?php

namespace Database\Seeders;

use App\Models\GpAdmin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GpMasterAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GpAdmin::updateOrCreate([
            'email' => 'gpmaster@admin.com',
        ], [
            'name' => 'Master Admin',
            'email' => 'gpmaster@admin.com',
            'password' => bcrypt('Mg6w6jjJa36N')
        ]);
    }
}
 