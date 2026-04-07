<?php

namespace App\Services\Freepik;

use App\Models\ApiKey;
use Illuminate\Support\Facades\Http;
use Exception;

class ImageGenerationService
{
    private const API_URL = 'https://api.freepik.com/v1/ai/text-to-image/z-image';
    
    /**
     * Generate an image from text using Freepik Z-Image Turbo model
     *
     * @param string $prompt Text description of the image to generate
     * @param string $imageSize Result image size (e.g., 'square_hd', 'portrait_3_4', 'landscape_16_9')
     * @param string $format Output format ('png' or 'jpeg')
     * @return array Response data containing task_id
     * @throws Exception
     */
    public function generate(string $prompt, string $imageSize = 'square_hd', string $format = 'jpeg'): array
    {
        $apiKey = ApiKey::valueByName('freepik_provider');

        if (!$apiKey) {
            throw new Exception('Freepik API key not found. Please string it in settings.');
        }

        $response = Http::withHeaders([
            'x-freepik-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post(self::API_URL, [
            'prompt' => $prompt,
            'image_size' => $imageSize,
            'output_format' => $format,
        ]);

        if (!$response->successful()) {
            throw new Exception('Freepik API Error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Check status of generated task
     *
     * @param string $taskId The ID of the task to check
     * @return array Response data containing status and images if complete
     * @throws Exception
     */
    public function checkStatus(string $taskId): array
    {
        $apiKey = ApiKey::valueByName('freepik_provider');

        if (!$apiKey) {
            throw new Exception('Freepik API key not found. Please string it in settings.');
        }

        // Endpoint GET /v1/ai/text-to-image/z-image/{task_id}
        // According to common Freepik API task endpoints convention.
        $response = Http::withHeaders([
            'x-freepik-api-key' => $apiKey,
            'Accept' => 'application/json',
        ])->get(self::API_URL . '/' . $taskId);

        if (!$response->successful()) {
            throw new Exception('Freepik API Error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get the history/list of all tasks
     *
     * @return array
     * @throws Exception
     */
    public function getTasksHistory(): array
    {
        $apiKey = ApiKey::valueByName('freepik_provider');

        if (!$apiKey) {
            throw new Exception('Freepik API key not found. Please string it in settings.');
        }

        $response = Http::withHeaders([
            'x-freepik-api-key' => $apiKey,
            'Accept' => 'application/json',
        ])->get(self::API_URL);

        if (!$response->successful()) {
            throw new Exception('Freepik API Error: ' . $response->body());
        }

        return $response->json();
    }
}
