<?php

namespace Tests\Feature\Tools;

use App\Livewire\Tools\CekResi;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CekResiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cek_resi_page_requires_authentication(): void
    {
        $response = $this->get(route('tools.cek-resi'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_cek_resi_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tools.cek-resi'));

        $response->assertOk();
        $response->assertSeeLivewire(CekResi::class);
    }

    public function test_cek_resi_can_track_package_using_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/tools/cekresi*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'message' => 'Selamat, paket Shopee Express (SPX) Anda dengan no resi SPXID054330680586 status Delivered.',
                'data' => [
                    'resi' => 'SPXID054330680586',
                    'ekspedisi' => 'Shopee Express (SPX)',
                    'ekspedisiCode' => 'SPX',
                    'status' => 'Delivered',
                    'tanggalKirim' => '21/06/2025 14:36',
                    'customerService' => '1500702',
                    'lastPosition' => 'Pesanan tiba di alamat tujuan. diterima oleh Yang bersangkutan. (Delivered)',
                    'shareLink' => 'https://cekresi.com/?r=w&noresi=SPXID054330680586',
                    'history' => [
                        [
                            'tanggal' => '29/06/2025 17:36',
                            'keterangan' => 'Pesanan tiba di alamat tujuan. diterima oleh Yang bersangkutan.',
                        ],
                        [
                            'tanggal' => '29/06/2025 06:15',
                            'keterangan' => 'Pesanan dalam proses pengantaran.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CekResi::class)
            ->set('resi', 'spxid054330680586')
            ->set('ekspedisi', 'shopee-express')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('resi', 'SPXID054330680586')
            ->assertSet('result.status', 'Delivered')
            ->assertSet('result.ekspedisiCode', 'SPX')
            ->assertSet('result.history.0.tanggal', '29/06/2025 17:36')
            ->assertSee('Timeline paket')
            ->assertSee('Pesanan dalam proses pengantaran.');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ferdev.my.id/tools/cekresi?resi=SPXID054330680586&ekspedisi=shopee-express&apikey=saved-downloader-key';
        });
    }

    public function test_cek_resi_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CekResi::class)
            ->set('resi', 'SPXID054330680586')
            ->set('ekspedisi', 'shopee-express')
            ->call('run')
            ->assertSet('errorMessage', 'API key cek resi belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "downloader_provider".');
    }

    public function test_cek_resi_validates_expedition_slug(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CekResi::class)
            ->set('resi', 'SPXID054330680586')
            ->set('ekspedisi', 'Shopee Express')
            ->call('run')
            ->assertHasErrors(['ekspedisi']);
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
