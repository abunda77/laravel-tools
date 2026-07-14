<?php

namespace Tests\Feature\Tools;

use App\Livewire\Tools\WilayahApi;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WilayahApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_wilayah_api_page_requires_authentication(): void
    {
        $response = $this->get(route('tools.wilayah-api'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_wilayah_api_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tools.wilayah-api'));

        $response->assertOk();
        $response->assertSeeLivewire(WilayahApi::class);
    }

    public function test_can_load_provinces_using_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://use.api.co.id/regional/indonesia/provinces*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [
                    ['code' => '11', 'name' => 'ACEH'],
                    ['code' => '31', 'name' => 'DKI JAKARTA'],
                ],
                'paging' => ['page' => 1, 'size' => 100, 'total_item' => 2, 'total_page' => 1],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WilayahApi::class)
            ->call('loadProvinces')
            ->assertHasNoErrors()
            ->assertSet('provinces.total', 2)
            ->assertSet('provinces.items.0.name', 'ACEH')
            ->assertSet('provinces.items.1.code', '31');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'https://use.api.co.id/regional/indonesia/provinces')
                && $request->header('x-api-co-id')[0] === 'saved-apicoid-key';
        });
    }

    public function test_province_filter_is_sent_as_query_parameter(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://use.api.co.id/regional/indonesia/provinces*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [['code' => '31', 'name' => 'DKI JAKARTA']],
                'paging' => ['page' => 1, 'size' => 100, 'total_item' => 1, 'total_page' => 1],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WilayahApi::class)
            ->set('provinceNameFilter', 'jakarta')
            ->call('loadProvinces')
            ->assertSet('provinces.total', 1);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://use.api.co.id/regional/indonesia/provinces?name=jakarta';
        });
    }

    public function test_can_load_regencies_after_selecting_province(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://use.api.co.id/regional/indonesia/provinces/31/regencies*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [
                    ['code' => '3174', 'name' => 'KOTA JAKARTA SELATAN', 'province_code' => '31', 'province' => 'DKI JAKARTA'],
                ],
                'paging' => ['page' => 1, 'size' => 100, 'total_item' => 1, 'total_page' => 1],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WilayahApi::class)
            ->call('selectProvince', '31', 'DKI JAKARTA')
            ->assertSet('provinceCode', '31')
            ->call('loadRegencies')
            ->assertSet('regencies.total', 1)
            ->assertSet('regencies.items.0.name', 'KOTA JAKARTA SELATAN');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://use.api.co.id/regional/indonesia/provinces/31/regencies';
        });
    }

    public function test_can_load_districts_after_selecting_regency(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://use.api.co.id/regional/indonesia/regencies/3174/districts*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [
                    ['code' => '317405', 'name' => 'KEBAYORAN LAMA', 'regency_code' => '3174', 'regency' => 'KOTA JAKARTA SELATAN', 'province_code' => '31', 'province' => 'DKI JAKARTA'],
                ],
                'paging' => ['page' => 1, 'size' => 100, 'total_item' => 1, 'total_page' => 1],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WilayahApi::class)
            ->call('selectProvince', '31', 'DKI JAKARTA')
            ->call('selectRegency', '3174', 'KOTA JAKARTA SELATAN')
            ->assertSet('regencyCode', '3174')
            ->call('loadDistricts')
            ->assertSet('districts.total', 1)
            ->assertSet('districts.items.0.name', 'KEBAYORAN LAMA');
    }

    public function test_can_load_villages_after_selecting_district(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://use.api.co.id/regional/indonesia/districts/317405/villages*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [
                    ['code' => '3174050001', 'name' => 'GROGOL UTARA', 'district_code' => '317405', 'district' => 'KEBAYORAN LAMA', 'regency_code' => '3174', 'regency' => 'KOTA JAKARTA SELATAN', 'province_code' => '31', 'province' => 'DKI JAKARTA', 'postal_codes' => ['12210'], 'is_courier_support' => true],
                ],
                'paging' => ['page' => 1, 'size' => 100, 'total_item' => 1, 'total_page' => 1],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WilayahApi::class)
            ->call('selectProvince', '31', 'DKI JAKARTA')
            ->call('selectRegency', '3174', 'KOTA JAKARTA SELATAN')
            ->call('selectDistrict', '317405', 'KEBAYORAN LAMA')
            ->assertSet('districtCode', '317405')
            ->call('loadVillages')
            ->assertSet('villages.total', 1)
            ->assertSet('villages.items.0.name', 'GROGOL UTARA')
            ->assertSet('villages.items.0.is_courier_support', true);
    }

    public function test_load_regencies_requires_selected_province(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WilayahApi::class)
            ->call('loadRegencies')
            ->assertSet('errorMessage', 'Pilih provinsi terlebih dahulu.');
    }

    public function test_wilayah_api_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WilayahApi::class)
            ->call('loadProvinces')
            ->assertSet('errorMessage', 'API key wilayah Indonesia belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "apicoid_provider".');
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
            'name' => 'apicoid_provider',
            'label' => 'ApiCoId Provider',
            'value' => 'saved-apicoid-key',
            'is_active' => true,
        ]);
    }
}
