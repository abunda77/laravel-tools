<?php

namespace Tests\Feature\Tools;

use App\Livewire\Tools\Holiday;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HolidayTest extends TestCase
{
    use RefreshDatabase;

    public function test_holiday_page_requires_authentication(): void
    {
        $response = $this->get(route('tools.holiday'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_holiday_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tools.holiday'));

        $response->assertOk();
        $response->assertSeeLivewire(Holiday::class);
    }

    public function test_holiday_can_list_holidays_using_saved_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createApiCoIdApiKey();

        Http::fake([
            'https://use.api.co.id/holidays/indonesia*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [
                    [
                        'date' => '2025-01-01',
                        'name' => 'Tahun Baru Masehi',
                        'type' => 'Public Holiday',
                        'is_joint_holiday' => false,
                        'is_observance' => false,
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Holiday::class)
            ->set('year', '2025')
            ->call('loadHolidays')
            ->assertHasNoErrors()
            ->assertSet('holidays.year', 2025)
            ->assertSet('holidays.total', 1)
            ->assertSet('holidays.items.0.name', 'Tahun Baru Masehi');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://use.api.co.id/holidays/indonesia?year=2025'
                && $request->hasHeader('x-api-co-id', 'saved-apicoid-key');
        });
    }

    public function test_holiday_can_check_date_using_saved_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createApiCoIdApiKey();

        Http::fake([
            'https://use.api.co.id/holidays/indonesia/check/date*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [
                    'date' => '2025-01-01',
                    'is_holiday' => true,
                    'day_of_week' => 'Wednesday',
                    'is_weekend' => false,
                    'holidays' => [['name' => 'Tahun Baru Masehi']],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Holiday::class)
            ->set('checkDate', '2025-01-01')
            ->call('verifyDate')
            ->assertHasNoErrors()
            ->assertSet('checkedDate.is_holiday', true)
            ->assertSet('checkedDate.day_of_week', 'Wednesday')
            ->assertSet('checkedDate.holidays.0.name', 'Tahun Baru Masehi');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://use.api.co.id/holidays/indonesia/check/date?date=2025-01-01'
                && $request->hasHeader('x-api-co-id', 'saved-apicoid-key');
        });
    }

    public function test_holiday_can_load_upcoming_using_saved_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createApiCoIdApiKey();

        Http::fake([
            'https://use.api.co.id/holidays/indonesia*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [
                    [
                        'id' => 1,
                        'date' => '2025-01-01',
                        'name' => 'Tahun Baru Masehi',
                        'type' => 'Public Holiday',
                        'is_upcoming' => true,
                        'days_until' => 44,
                    ],
                    [
                        'id' => 2,
                        'date' => '2025-02-01',
                        'name' => 'Hari yang sudah lewat',
                        'type' => 'Observance',
                        'is_upcoming' => false,
                        'days_until' => -10,
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Holiday::class)
            ->set('upcomingLimit', '10')
            ->call('loadUpcomingHolidays')
            ->assertHasNoErrors()
            ->assertSet('upcoming.total', 1)
            ->assertSet('upcoming.items.0.name', 'Tahun Baru Masehi')
            ->assertSet('upcoming.items.0.days_until', 44);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://use.api.co.id/holidays/indonesia?year=')
                && $request->hasHeader('x-api-co-id', 'saved-apicoid-key');
        });
    }

    public function test_holiday_requires_saved_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Holiday::class)
            ->set('year', '2025')
            ->call('loadHolidays')
            ->assertSet('errorMessage', 'API key API.co.id belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "apicoid_provider".');
    }

    public function test_holiday_validates_year_format(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Holiday::class)
            ->set('year', '1990')
            ->call('loadHolidays')
            ->assertHasErrors(['year']);
    }

    public function test_holiday_validates_date_format(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Holiday::class)
            ->set('checkDate', '01/01/2025')
            ->call('verifyDate')
            ->assertHasErrors(['checkDate']);
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

    private function createApiCoIdApiKey(bool $isActive = true): void
    {
        ApiKey::query()->create([
            'name' => 'apicoid_provider',
            'label' => 'API.co.id Provider',
            'value' => 'saved-apicoid-key',
            'is_active' => $isActive,
        ]);
    }
}
