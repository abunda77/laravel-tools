<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\QrCodeTemporaryFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class QrCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_page_is_visible_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('qr-code.generate'));

        $response
            ->assertOk()
            ->assertSeeVolt('qr-code.generate')
            ->assertSee('QR Code Generator');
    }

    public function test_generate_page_blocks_guests(): void
    {
        $response = $this->get(route('qr-code.generate'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_content_is_required(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('qr-code.generate')
            ->set('content', '')
            ->call('generate');

        $component->assertHasErrors('content');
    }

    public function test_generate_stores_png_and_jpg_files(): void
    {
        Storage::fake(QrCodeTemporaryFileService::Disk);

        $user = User::factory()->create();

        $component = Volt::test('qr-code.generate')
            ->set('content', 'https://laravel.com')
            ->call('generate');

        $component
            ->assertHasNoErrors()
            ->assertSet('previewDataUri', fn (string $value): bool => str_starts_with($value, 'data:image/png;base64,'));

        $this->assertNotEmpty($component->get('pngFilename'));
        $this->assertNotEmpty($component->get('jpgFilename'));

        Storage::disk(QrCodeTemporaryFileService::Disk)
            ->assertExists(QrCodeTemporaryFileService::Directory.'/'.$component->get('pngFilename'));
        Storage::disk(QrCodeTemporaryFileService::Disk)
            ->assertExists(QrCodeTemporaryFileService::Directory.'/'.$component->get('jpgFilename'));
    }

    public function test_generated_file_can_be_downloaded(): void
    {
        Storage::fake(QrCodeTemporaryFileService::Disk);

        $user = User::factory()->create();

        $component = Volt::test('qr-code.generate')
            ->set('content', 'https://laravel.com')
            ->call('generate');

        $pngFilename = $component->get('pngFilename');

        $response = $this->actingAs($user)->get(route('qr-code.download', ['filename' => $pngFilename]));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_temporary_files_can_be_cleared_manually(): void
    {
        Storage::fake(QrCodeTemporaryFileService::Disk);

        $user = User::factory()->create();

        $component = Volt::test('qr-code.generate')
            ->set('content', 'https://laravel.com')
            ->call('generate');

        $pngFilename = $component->get('pngFilename');
        $jpgFilename = $component->get('jpgFilename');

        $this->assertTrue(
            Storage::disk(QrCodeTemporaryFileService::Disk)->exists(QrCodeTemporaryFileService::Directory.'/'.$pngFilename),
        );

        $component->call('clearTemporaryFiles');

        $component
            ->assertSet('pngFilename', '')
            ->assertSet('jpgFilename', '')
            ->assertSet('previewDataUri', '');

        Storage::disk(QrCodeTemporaryFileService::Disk)
            ->assertMissing(QrCodeTemporaryFileService::Directory.'/'.$pngFilename);
        Storage::disk(QrCodeTemporaryFileService::Disk)
            ->assertMissing(QrCodeTemporaryFileService::Directory.'/'.$jpgFilename);
    }

    public function test_expired_files_are_cleaned_up_by_service(): void
    {
        Storage::fake(QrCodeTemporaryFileService::Disk);

        $directory = QrCodeTemporaryFileService::Directory;
        $fresh = Uuid::uuid4()->toString().'.png';
        $expired = Uuid::uuid4()->toString().'.png';

        Storage::disk(QrCodeTemporaryFileService::Disk)->put($directory.'/'.$fresh, 'data');
        Storage::disk(QrCodeTemporaryFileService::Disk)->put($directory.'/'.$expired, 'data');
        touch(
            Storage::disk(QrCodeTemporaryFileService::Disk)->path($directory.'/'.$expired),
            now()->subHours(QrCodeTemporaryFileService::ExpiryHours + 1)->getTimestamp(),
        );

        $service = new QrCodeTemporaryFileService;
        $deleted = $service->cleanupExpiredFiles();

        $this->assertSame(1, $deleted);
        Storage::disk(QrCodeTemporaryFileService::Disk)->assertMissing($directory.'/'.$expired);
        Storage::disk(QrCodeTemporaryFileService::Disk)->assertExists($directory.'/'.$fresh);
    }
}
