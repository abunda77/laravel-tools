<?php

namespace Tests\Feature\Operations;

use App\Livewire\Operations\InfoSystem;
use App\Models\User;
use App\Support\SystemInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InfoSystemPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_probe_sistem_mengembalikan_data_utama(): void
    {
        $systemInfo = app(SystemInfo::class);

        $this->assertNotEmpty($systemInfo->overviewCards());
        $this->assertNotEmpty($systemInfo->osInfo());
        $this->assertNotEmpty($systemInfo->phpInfo());
        $this->assertNotEmpty($systemInfo->phpModules());
        $this->assertIsArray($systemInfo->unloadedExtensions());
        $this->assertNotEmpty($systemInfo->infraGroups());

        $composer = $systemInfo->composerPackages();
        $this->assertNotEmpty($composer['packages']);
        $this->assertSame(count($composer['packages']), $composer['counts']['total']);
        $this->assertSame(
            $composer['counts']['production'] + $composer['counts']['development'],
            $composer['counts']['total'],
        );
    }

    public function test_modul_php_memiliki_kategori_yang_dikenal(): void
    {
        $systemInfo = app(SystemInfo::class);
        $categories = ['Core', 'Zend', 'Ekstensi'];

        foreach ($systemInfo->phpModules() as $module) {
            $this->assertArrayHasKey('name', $module);
            $this->assertArrayHasKey('version', $module);
            $this->assertContains($module['category'], $categories);
        }

        $counts = $systemInfo->moduleCountsByCategory();
        $this->assertSame(count($systemInfo->phpModules()), array_sum($counts));
    }

    public function test_guest_tidak_dapat_mengakses_halaman(): void
    {
        $this->get('/operations/info-system')->assertRedirect('/login');
    }

    public function test_halaman_info_system_dapat_dirender(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/operations/info-system');

        $response
            ->assertOk()
            ->assertSee('Info System')
            ->assertSee('Ringkasan')
            ->assertSee('Sistem Operasi')
            ->assertSee('Modul PHP');
    }

    public function test_menu_info_system_tampil_di_sidebar_operations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Operations')
            ->assertSee('Info System');
    }

    public function test_tab_dapat_berpindah_dan_menampilkan_konten(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(InfoSystem::class)
            ->assertSuccessful()
            ->assertSee('Ringkasan')
            ->call('setTab', 'modules')
            ->assertSet('activeTab', 'modules')
            ->assertSee('Modul PHP Aktif')
            ->call('setTab', 'composer')
            ->assertSet('activeTab', 'composer')
            ->assertSee('Paket Terpasang')
            ->call('setTab', 'infra')
            ->assertSet('activeTab', 'infra')
            ->assertSee('Runtime Aplikasi')
            ->call('setTab', 'os')
            ->assertSet('activeTab', 'os')
            ->assertSee('Detail Sistem Operasi')
            ->call('setTab', 'php')
            ->assertSet('activeTab', 'php')
            ->assertSee('Konfigurasi PHP');
    }

    public function test_tab_tidak_dikenal_diabaikan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(InfoSystem::class)
            ->call('setTab', 'tidak-ada')
            ->assertSet('activeTab', 'overview');
    }

    public function test_refresh_menampilkan_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(InfoSystem::class)
            ->call('refresh')
            ->assertSee('Informasi sistem diperbarui.');
    }

    public function test_auto_refresh_dapat_diaktifkan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(InfoSystem::class)
            ->assertSet('autoRefresh', false)
            ->set('autoRefresh', true)
            ->assertSet('autoRefresh', true);
    }
}
