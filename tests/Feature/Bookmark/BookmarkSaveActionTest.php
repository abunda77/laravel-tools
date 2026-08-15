<?php

namespace Tests\Feature\Bookmark;

use App\Livewire\Internet\BookmarkIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BookmarkSaveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_membuat_bookmark_dan_muncul_di_daftar(): void
    {
        Http::fake([
            '*' => Http::response(
                '<html><head><title>Contoh</title></head><body></body></html>',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BookmarkIndex::class)
            ->set('url', 'https://example.com/artikel')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Bookmark berhasil disimpan.')
            ->assertSee('example.com');

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'url' => 'https://example.com/artikel',
        ]);
    }

    public function test_preview_muncul_saat_url_diisi(): void
    {
        Http::fake([
            '*' => Http::response(
                '<html><head><title>Judul Preview</title><meta property="og:description" content="Deskripsi preview" /></head><body></body></html>',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BookmarkIndex::class)
            ->set('url', 'https://example.com/artikel')
            ->assertSet('previewError', null)
            ->assertSet('preview.title', 'Judul Preview')
            ->assertSee('Judul Preview');
    }
}
