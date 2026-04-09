<?php

namespace Tests\Feature\Internet;

use App\Livewire\Internet\ProxyValidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProxyValidateTest extends TestCase
{
    use RefreshDatabase;

    public function test_proxy_validate_page_requires_authentication(): void
    {
        $response = $this->get(route('internet.proxy-validate'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_proxy_validate_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('internet.proxy-validate'));

        $response->assertOk();
        $response->assertSeeLivewire(ProxyValidate::class);
    }

    public function test_proxy_validate_can_fetch_and_parse_proxy_rows(): void
    {
        Http::fake([
            'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt' => Http::response(implode("\n", [
                '103.10.1.1:8080 | HTTP | ID | Elite',
                '45.67.89.10:1080 | SOCKS5 | SG | Anonymous',
                'invalid-line',
                '10.0.0.1:not-a-port | HTTP | ID | Anonymous',
            ]), 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProxyValidate::class)
            ->set('selectedSource', 'All Proxies')
            ->call('fetchProxies')
            ->assertHasNoErrors()
            ->assertSet('errorMessage', null)
            ->assertSet('hasLoaded', true)
            ->assertCount('proxies', 2)
            ->assertSee('103.10.1.1:8080')
            ->assertSee('45.67.89.10:1080')
            ->assertSee('Unchecked');
    }

    public function test_proxy_validate_surfaces_fetch_error_message(): void
    {
        Http::fake([
            'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt' => Http::response('upstream error', 500),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProxyValidate::class)
            ->call('fetchProxies')
            ->assertSet('hasLoaded', true)
            ->assertCount('proxies', 0)
            ->assertSee('500');
    }

    public function test_proxy_validate_can_check_single_proxy_validity(): void
    {
        Http::fake([
            'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt' => Http::response(
                '103.10.1.1:8080 | HTTP | ID | Elite',
                200
            ),
            'https://httpbin.org/ip' => Http::response([
                'origin' => '8.8.8.8',
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProxyValidate::class)
            ->call('fetchProxies')
            ->call('validateProxy', '103.10.1.1:8080')
            ->assertSet('proxies.0.status', 'Valid')
            ->assertSet('proxies.0.detected_ip', '8.8.8.8')
            ->assertSee('Valid');
    }

    public function test_proxy_validate_marks_proxy_invalid_when_check_fails(): void
    {
        Http::fake([
            'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt' => Http::response(
                '103.10.1.1:8080 | HTTP | ID | Elite',
                200
            ),
            'https://httpbin.org/ip' => Http::response(['message' => 'fail'], 500),
            'https://api.ipify.org?format=json' => Http::response(['message' => 'fail'], 500),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProxyValidate::class)
            ->call('fetchProxies')
            ->call('validateProxy', '103.10.1.1:8080')
            ->assertSet('proxies.0.status', 'Invalid')
            ->assertSee('Invalid');
    }

    public function test_proxy_validate_can_filter_rows_from_table_header(): void
    {
        Http::fake([
            'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt' => Http::response(implode("\n", [
                '103.10.1.1:8080 | HTTP | ID | Elite',
                '45.67.89.10:1080 | SOCKS5 | SG | Anonymous',
            ]), 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProxyValidate::class)
            ->call('fetchProxies')
            ->set('filterProtocol', 'SOCKS5')
            ->assertSet('filteredProxyCount', 1)
            ->assertSee('45.67.89.10:1080')
            ->assertDontSee('103.10.1.1:8080');
    }

    public function test_proxy_validate_can_check_only_selected_rows(): void
    {
        Http::fake([
            'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt' => Http::response(implode("\n", [
                '103.10.1.1:8080 | HTTP | ID | Elite',
                '45.67.89.10:1080 | SOCKS5 | SG | Anonymous',
            ]), 200),
            'https://httpbin.org/ip' => Http::response([
                'origin' => '8.8.8.8',
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProxyValidate::class)
            ->call('fetchProxies')
            ->set('selectedAddresses', ['45.67.89.10:1080'])
            ->call('validateSelected')
            ->assertSet('proxies.0.status', 'Unchecked')
            ->assertSet('proxies.1.status', 'Valid')
            ->assertSet('selectedCount', 1);
    }

    public function test_proxy_validate_can_select_visible_unchecked_only(): void
    {
        Http::fake([
            'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt' => Http::response(implode("\n", [
                '103.10.1.1:8080 | HTTP | ID | Elite',
                '45.67.89.10:1080 | SOCKS5 | SG | Anonymous',
            ]), 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProxyValidate::class)
            ->call('fetchProxies')
            ->set('proxies.0.status', 'Valid')
            ->call('selectVisibleUncheckedOnly')
            ->assertSet('selectedAddresses', ['45.67.89.10:1080']);
    }

    public function test_proxy_validate_can_export_selected_csv(): void
    {
        Http::fake([
            'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt' => Http::response(
                '103.10.1.1:8080 | HTTP | ID | Elite',
                200
            ),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProxyValidate::class)
            ->call('fetchProxies')
            ->set('selectedAddresses', ['103.10.1.1:8080'])
            ->call('exportSelectedCsv')
            ->assertFileDownloaded('proxy-validate-export.csv');
    }

    public function test_proxy_validate_can_export_selected_txt(): void
    {
        Http::fake([
            'https://raw.githubusercontent.com/anutmagang/Free-HighQuality-Proxy-Socks/main/results/all.txt' => Http::response(
                '103.10.1.1:8080 | HTTP | ID | Elite',
                200
            ),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProxyValidate::class)
            ->call('fetchProxies')
            ->set('selectedAddresses', ['103.10.1.1:8080'])
            ->call('exportSelectedTxt')
            ->assertFileDownloaded('proxy-validate-export.txt');
    }
}
