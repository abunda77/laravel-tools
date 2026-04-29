<?php

namespace Tests\Feature\Search;

use App\Livewire\Search\AnimeQuoteSearch;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AnimeQuoteSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_anime_quote_search_page_requires_authentication(): void
    {
        $response = $this->get(route('search.anime-quotes'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_anime_quote_search_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.anime-quotes'));

        $response->assertOk();
        $response->assertSeeLivewire(AnimeQuoteSearch::class);
    }

    public function test_anime_quote_search_can_fetch_quotes_using_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/random/animequote*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'result' => [
                    [
                        'char' => 'Rio Futaba',
                        'from_anime' => 'Seishun Buta Yarou wa Bunny Girl Senpai no Yume wo Minai',
                        'episode' => 'Episode 1',
                        'quote' => 'Otak manusia takkan melihat sesuatu yang tak ingin dilihatnya.',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AnimeQuoteSearch::class)
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.author', 'Feri')
            ->assertSet('result.total', 1)
            ->assertSet('result.quotes.0.char', 'Rio Futaba')
            ->assertSet('result.quotes.0.from_anime', 'Seishun Buta Yarou wa Bunny Girl Senpai no Yume wo Minai')
            ->assertSee('Rio Futaba')
            ->assertSee('Otak manusia takkan melihat sesuatu yang tak ingin dilihatnya.');

        Http::assertSent(function ($request) {
            $url = $request->url();
            $query = [];

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://api.ferdev.my.id/random/animequote')
                && ($query['apikey'] ?? null) === 'saved-downloader-key';
        });
    }

    public function test_anime_quote_search_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AnimeQuoteSearch::class)
            ->call('run')
            ->assertSet('errorMessage', 'API key Quotes Anime belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "downloader_provider".');
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

    private function createDownloaderApiKey(): void
    {
        ApiKey::query()->create([
            'name' => 'downloader_provider',
            'label' => 'Downloader Provider',
            'value' => 'saved-downloader-key',
            'is_active' => true,
        ]);
    }
}
