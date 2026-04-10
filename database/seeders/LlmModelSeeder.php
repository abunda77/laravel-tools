<?php

namespace Database\Seeders;

use App\Models\LlmModel;
use Illuminate\Database\Seeder;

class LlmModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            ['provider' => 'openai', 'name' => 'gpt-4.1', 'label' => 'GPT 4.1', 'supports_documents' => true, 'supports_images' => true, 'sort_order' => 10],
            ['provider' => 'openai', 'name' => 'gpt-4.1-mini', 'label' => 'GPT 4.1 Mini', 'supports_documents' => true, 'supports_images' => true, 'sort_order' => 20],
            ['provider' => 'gemini', 'name' => 'gemini-2.5-pro', 'label' => 'Gemini 2.5 Pro', 'supports_documents' => true, 'supports_images' => true, 'sort_order' => 10],
            ['provider' => 'gemini', 'name' => 'gemini-2.5-flash', 'label' => 'Gemini 2.5 Flash', 'supports_documents' => true, 'supports_images' => true, 'sort_order' => 20],
            ['provider' => 'anthropic', 'name' => 'claude-sonnet-4-5-20250929', 'label' => 'Claude Sonnet 4.5', 'supports_documents' => true, 'supports_images' => true, 'sort_order' => 10],
            ['provider' => 'anthropic', 'name' => 'claude-haiku-4-5-20251001', 'label' => 'Claude Haiku 4.5', 'supports_documents' => true, 'supports_images' => true, 'sort_order' => 20],
            ['provider' => 'perplexity', 'name' => 'sonar-pro', 'label' => 'Sonar Pro', 'supports_documents' => false, 'supports_images' => false, 'sort_order' => 10],
            ['provider' => 'perplexity', 'name' => 'sonar', 'label' => 'Sonar', 'supports_documents' => false, 'supports_images' => false, 'sort_order' => 20],
        ];

        foreach ($models as $model) {
            LlmModel::query()->updateOrCreate(
                ['provider' => $model['provider'], 'name' => $model['name']],
                [
                    'label' => $model['label'],
                    'supports_documents' => $model['supports_documents'],
                    'supports_images' => $model['supports_images'],
                    'supports_web_search' => true,
                    'is_active' => true,
                    'sort_order' => $model['sort_order'],
                ],
            );
        }
    }
}
