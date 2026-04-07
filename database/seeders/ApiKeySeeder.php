<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\ApiKey::firstOrCreate(
            ['name' => 'freepik_provider'],
            [
                'label' => 'Freepik Image Generation',
                'description' => 'API Key untuk layanan Freepik Text-to-Image Generation (Z-Image Turbo)',
                'value' => null, // Biarkan kosong agar user set via dashboard
                'is_active' => true,
            ]
        );
    }
}
