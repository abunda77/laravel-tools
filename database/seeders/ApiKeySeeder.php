<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use Illuminate\Database\Seeder;

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keys = [
            ['name' => 'freepik_provider', 'label' => 'Freepik Image Generation', 'description' => 'API Key untuk layanan Freepik Text-to-Image Generation (Z-Image Turbo)'],
            ['name' => 'openai', 'label' => 'OpenAI', 'description' => 'API key untuk provider OpenAI di ChatBot.'],
            ['name' => 'gemini', 'label' => 'Gemini', 'description' => 'API key untuk provider Gemini di ChatBot.'],
            ['name' => 'anthropic', 'label' => 'Claude / Anthropic', 'description' => 'API key untuk provider Claude / Anthropic di ChatBot.'],
            ['name' => 'perplexity', 'label' => 'Perplexity', 'description' => 'API key untuk Perplexity Sonar dan Search citations.'],
            ['name' => 'youtubeapi_provider', 'label' => 'YouTube Data API', 'description' => 'API key dari Google Cloud Console untuk YouTube Data API v3 (digunakan di fitur Youtube Channel).'],
        ];

        foreach ($keys as $key) {
            ApiKey::firstOrCreate(
                ['name' => $key['name']],
                [
                    'label' => $key['label'],
                    'description' => $key['description'],
                    'value' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
