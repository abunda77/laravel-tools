<?php

namespace Tests\Feature\Settings;

use App\Livewire\Operations\ApiKeyBackupManager;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ApiKeyBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_page_requires_authentication(): void
    {
        $response = $this->get(route('operations.api-key-backups'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_backup_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('operations.api-key-backups'));

        $response->assertOk();
        $response->assertSeeLivewire(ApiKeyBackupManager::class);
        $response->assertSee('Backup Data ApiKey');
    }

    public function test_user_can_create_and_download_api_key_backup(): void
    {
        Storage::fake('local');

        $this->createApiKey();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ApiKeyBackupManager::class)
            ->call('createBackup')
            ->assertHasNoErrors();

        $files = Storage::disk('local')->files('api-key-backups');

        $this->assertCount(1, $files);

        $payload = json_decode(Storage::disk('local')->get($files[0]), true);

        $this->assertSame('laravel-tools.api-keys', $payload['type']);
        $this->assertSame('freepik_provider', $payload['api_keys'][0]['name']);
        $this->assertSame('secret-freepik-key', $payload['api_keys'][0]['value']);

        Livewire::actingAs($user)
            ->test(ApiKeyBackupManager::class)
            ->call('downloadBackup', basename($files[0]))
            ->assertFileDownloaded(basename($files[0]));
    }

    public function test_user_can_delete_api_key_backup_file(): void
    {
        Storage::fake('local');

        $this->createApiKey();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ApiKeyBackupManager::class)
            ->call('createBackup')
            ->assertHasNoErrors();

        $files = Storage::disk('local')->files('api-key-backups');

        $this->assertCount(1, $files);

        $filename = basename($files[0]);

        Livewire::actingAs($user)
            ->test(ApiKeyBackupManager::class)
            ->call('deleteBackup', $filename)
            ->assertHasNoErrors();

        Storage::disk('local')->assertMissing('api-key-backups/'.$filename);
    }

    public function test_user_can_restore_api_keys_from_backup_file(): void
    {
        Storage::fake('local');

        $payload = [
            'type' => 'laravel-tools.api-keys',
            'version' => 1,
            'exported_at' => now()->toISOString(),
            'count' => 1,
            'api_keys' => [
                [
                    'name' => 'downloader_provider',
                    'label' => 'Downloader Provider',
                    'description' => 'Restored key',
                    'value' => 'restored-secret',
                    'is_active' => true,
                ],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('api-key-backup.json', json_encode($payload, JSON_THROW_ON_ERROR));
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ApiKeyBackupManager::class)
            ->set('backupFile', $file)
            ->call('restoreBackup')
            ->assertHasNoErrors();

        $apiKey = ApiKey::findByName('downloader_provider');

        $this->assertNotNull($apiKey);
        $this->assertSame('Downloader Provider', $apiKey->label);
        $this->assertSame('Restored key', $apiKey->description);
        $this->assertSame('restored-secret', $apiKey->value);
        $this->assertTrue($apiKey->is_active);
    }

    private function createApiKey(): void
    {
        ApiKey::query()->create([
            'name' => 'freepik_provider',
            'label' => 'Freepik Provider',
            'description' => 'Freepik API key',
            'value' => 'secret-freepik-key',
            'is_active' => true,
        ]);
    }
}
