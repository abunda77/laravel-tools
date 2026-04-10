<?php

namespace Tests\Feature\Internet;

use App\Livewire\Internet\Whois;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WhoisTest extends TestCase
{
    use RefreshDatabase;

    public function test_whois_page_requires_authentication(): void
    {
        $response = $this->get(route('internet.whois'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_whois_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('internet.whois'));

        $response->assertOk();
        $response->assertSeeLivewire(Whois::class);
    }

    public function test_whois_can_lookup_domain_using_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/internet/whois*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'data' => [
                    'domain' => 'produkmastah.com',
                    'result' => "Domain Name: PRODUKMASTAH.COM\nRegistrar: DYNADOT LLC\nCreation Date: 2025-02-17T23:14:08.0Z\nRegistrar Registration Expiration Date: 2027-02-17T23:14:08.0Z\nName Server: davina.ns.cloudflare.com\nName Server: ed.ns.cloudflare.com\nDNSSEC: unsigned\n",
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Whois::class)
            ->set('domain', 'https://Produkmastah.com/path?query=1')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('domain', 'produkmastah.com')
            ->assertSet('result.domain', 'produkmastah.com')
            ->assertSet('result.summary.Registrar', 'DYNADOT LLC')
            ->assertSet('result.summary.Name Servers', 'davina.ns.cloudflare.com, ed.ns.cloudflare.com')
            ->assertSee('Raw result')
            ->assertSee('DYNADOT LLC');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ferdev.my.id/internet/whois?domain=produkmastah.com&apikey=saved-downloader-key';
        });
    }

    public function test_whois_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Whois::class)
            ->set('domain', 'produkmastah.com')
            ->call('run')
            ->assertSet('errorMessage', 'API key Whois belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "downloader_provider".');
    }

    public function test_whois_validates_domain_format(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Whois::class)
            ->set('domain', 'not a domain')
            ->call('run')
            ->assertSet('errorMessage', 'Domain tidak valid. Gunakan format seperti produkmastah.com.');
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
