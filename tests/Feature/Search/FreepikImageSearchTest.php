<?php

namespace Tests\Feature\Search;

use App\Livewire\Search\FreepikImage;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FreepikImageSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_freepik_image_page_requires_authentication(): void
    {
        $response = $this->get(route('search.freepik-image'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_freepik_image_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.freepik-image'));

        $response->assertOk();
        $response->assertSeeLivewire(FreepikImage::class);
    }

    public function test_freepik_image_can_search_resources_and_load_format_download_data_using_freepik_provider_key(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.magnific.com/v1/resources?*' => Http::response([
                'data' => [
                    [
                        'id' => 15667327,
                        'title' => 'White t-shirt with copy space on gray background',
                        'url' => 'https://www.freepik.com/free-photo/white-t-shirts-with-copy-space-gray-background_15667327.htm',
                        'filename' => 'white-tshirt.zip',
                        'meta' => [
                            'published_at' => '2025-04-10T08:30:00.000Z',
                            'is_new' => true,
                            'available_formats' => [
                                'jpg' => [
                                    'total' => 1,
                                    'items' => [
                                        ['id' => 91, 'name' => 'preview.jpg', 'size' => 1500, 'colorspace' => 'RGB'],
                                    ],
                                ],
                                'png' => [
                                    'total' => 1,
                                    'items' => [
                                        ['id' => 92, 'name' => 'preview.png', 'size' => 1500, 'colorspace' => 'RGB'],
                                    ],
                                ],
                            ],
                        ],
                        'image' => [
                            'type' => 'photo',
                            'orientation' => 'horizontal',
                            'source' => [
                                'url' => 'https://img.freepik.com/free-photo/tshirt_53876-104920.jpg',
                                'key' => 'large',
                                'size' => '740x640',
                            ],
                        ],
                        'stats' => [
                            'downloads' => 2500,
                            'likes' => 180,
                        ],
                        'author' => [
                            'id' => 77,
                            'name' => 'John Doe',
                            'slug' => 'john-doe',
                            'avatar' => 'https://avatar.cdnpk.net/77.jpg',
                        ],
                    ],
                ],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 12,
                    'total' => 1,
                ],
            ], 200),
            'https://api.magnific.com/v1/resources/15667327' => Http::response([
                'data' => [
                    'id' => 15667327,
                    'title' => 'White t-shirt with copy space on gray background',
                    'filename' => 'white-tshirt.zip',
                    'url' => 'https://www.freepik.com/free-photo/white-t-shirts-with-copy-space-gray-background_15667327.htm',
                    'meta' => [
                        'published_at' => '2025-04-10T08:30:00.000Z',
                        'is_new' => true,
                        'available_formats' => [
                            'jpg' => [
                                'total' => 1,
                                'items' => [
                                    ['id' => 91, 'name' => 'preview.jpg', 'size' => 1500, 'colorspace' => 'RGB'],
                                ],
                            ],
                            'png' => [
                                'total' => 1,
                                'items' => [
                                    ['id' => 92, 'name' => 'preview.png', 'size' => 1500, 'colorspace' => 'RGB'],
                                ],
                            ],
                        ],
                    ],
                    'image' => [
                        'type' => 'photo',
                        'orientation' => 'horizontal',
                        'source' => [
                            'url' => 'https://img.freepik.com/free-photo/tshirt_53876-104920.jpg',
                            'key' => 'large',
                            'size' => '740x640',
                        ],
                    ],
                    'stats' => [
                        'downloads' => 2500,
                        'likes' => 180,
                    ],
                    'author' => [
                        'id' => 77,
                        'name' => 'John Doe',
                        'slug' => 'john-doe',
                        'avatar' => 'https://avatar.cdnpk.net/77.jpg',
                    ],
                ],
            ], 200),
            'https://api.magnific.com/v1/resources/15667327/download/png' => Http::response([
                'data' => [
                    'filename' => 'white-tshirt-preview.png',
                    'signed_url' => 'https://img.freepik.com/premium-photo/white-tshirt-preview.png',
                    'url' => 'https://downloadscdn5.freepik.com/d/15667327/white-tshirt-preview.png',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(FreepikImage::class)
            ->set('query', 'white t-shirt mockup')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.query', 'white t-shirt mockup')
            ->assertSet('result.total', 1)
            ->assertSet('result.resources.0.id', 15667327)
            ->assertSet('result.resources.0.title', 'White t-shirt with copy space on gray background')
            ->assertSee('White t-shirt with copy space on gray background');

        $component->call('selectResource', 15667327)
            ->assertSet('selectedResource.id', 15667327)
            ->assertSet('selectedFormats.jpg.total', 1)
            ->assertSet('selectedFormats.png.total', 1)
            ->assertSee('White t-shirt with copy space on gray background');

        $component->call('downloadFormat', 'png')
            ->assertSet('selectedFormat', 'png')
            ->assertSet('selectedFormatDownload.filename', 'white-tshirt-preview.png')
            ->assertSet('selectedFormatDownload.signed_url', 'https://img.freepik.com/premium-photo/white-tshirt-preview.png');

        Http::assertSent(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://api.magnific.com/v1/resources?')
                && ($query['term'] ?? null) === 'white t-shirt mockup'
                && (int) ($query['page'] ?? 0) === 1
                && (int) ($query['limit'] ?? 0) === 12
                && ($query['order'] ?? null) === 'relevance'
                && $request->hasHeader('x-magnific-api-key', 'saved-freepik-key');
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.magnific.com/v1/resources/15667327'
                && $request->hasHeader('x-magnific-api-key', 'saved-freepik-key');
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.magnific.com/v1/resources/15667327/download/png'
                && $request->hasHeader('x-magnific-api-key', 'saved-freepik-key');
        });
    }

    public function test_freepik_image_requires_active_freepik_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FreepikImage::class)
            ->set('query', 'white t-shirt mockup')
            ->call('run')
            ->assertSet('errorMessage', 'Freepik API key belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "freepik_provider".');
    }

    public function test_freepik_image_validates_query(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FreepikImage::class)
            ->set('query', '')
            ->call('run')
            ->assertHasErrors(['query']);
    }

    private function configureRequestSettings(): void
    {
        app(SystemSettings::class)->putMany([
            'request_timeout_seconds' => 30,
            'request_retry_times' => 1,
            'request_retry_sleep_ms' => 100,
            'queue_connection' => 'database',
        ]);
    }

    private function createFreepikApiKey(): void
    {
        ApiKey::query()->create([
            'name' => 'freepik_provider',
            'label' => 'Freepik Provider',
            'value' => 'saved-freepik-key',
            'is_active' => true,
        ]);
    }
}
