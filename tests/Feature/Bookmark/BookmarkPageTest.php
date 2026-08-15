<?php

namespace Tests\Feature\Bookmark;

use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookmarkPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_halaman_bookmark_tampil_untuk_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('internet.bookmark'))
            ->assertOk()
            ->assertSee('Bookmark');
    }

    public function test_halaman_bookmark_diblokir_untuk_guest(): void
    {
        $this->get(route('internet.bookmark'))
            ->assertRedirect(route('login'));
    }

    public function test_menampilkan_daftar_bookmark(): void
    {
        Bookmark::factory()
            ->for($this->user)
            ->count(3)
            ->create();

        $this->actingAs($this->user)
            ->get(route('internet.bookmark'))
            ->assertOk()
            ->assertSee('Daftar bookmark');
    }

    public function test_hanya_menampilkan_bookmark_milik_user_terkait(): void
    {
        $otherUser = User::factory()->create();

        Bookmark::factory()->for($this->user)->create(['title' => 'Bookmark Saya']);
        Bookmark::factory()->for($otherUser)->create(['title' => 'Bookmark Orang Lain']);

        $this->actingAs($this->user)
            ->get(route('internet.bookmark'))
            ->assertOk()
            ->assertSee('Bookmark Saya')
            ->assertDontSee('Bookmark Orang Lain');
    }

    public function test_menampilkan_kategori_di_sidebar(): void
    {
        BookmarkCategory::factory()
            ->for($this->user)
            ->create(['name' => 'Coding']);

        $this->actingAs($this->user)
            ->get(route('internet.bookmark'))
            ->assertOk()
            ->assertSee('Coding');
    }

    public function test_hanya_menampilkan_kategori_milik_user(): void
    {
        $otherUser = User::factory()->create();

        BookmarkCategory::factory()->for($this->user)->create(['name' => 'Kategori Saya']);
        BookmarkCategory::factory()->for($otherUser)->create(['name' => 'Kategori Orang Lain']);

        $this->actingAs($this->user)
            ->get(route('internet.bookmark'))
            ->assertOk()
            ->assertSee('Kategori Saya')
            ->assertDontSee('Kategori Orang Lain');
    }

    public function test_api_bookmark_list(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        Bookmark::factory()
            ->for($this->user)
            ->count(3)
            ->create();

        $response = $this->withToken($token)
            ->getJson(route('api.bookmarks.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_api_bookmark_list_diblokir_tanpa_token(): void
    {
        $this->getJson(route('api.bookmarks.index'))
            ->assertUnauthorized();
    }

    public function test_api_bookmark_store(): void
    {
        Http::fake([
            '*' => Http::response(
                '<html><head><title>Contoh</title><meta property="og:description" content="Deskripsi contoh" /></head><body></body></html>',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.bookmarks.store'), [
                'url' => 'https://example.com',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.url', 'https://example.com')
            ->assertJsonPath('data.title', 'Contoh');
    }

    public function test_api_bookmark_store_duplikat(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        Bookmark::factory()->for($this->user)->create(['url' => 'https://example.com']);

        $response = $this->withToken($token)
            ->postJson(route('api.bookmarks.store'), [
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(409);
    }

    public function test_api_bookmark_store_dengan_kategori(): void
    {
        Http::fake(['*' => Http::response('<html><head><title>Test</title></head></html>', 200, ['Content-Type' => 'text/html'])]);

        $token = $this->user->createToken('test')->plainTextToken;

        $category = BookmarkCategory::factory()->for($this->user)->create();

        $response = $this->withToken($token)
            ->postJson(route('api.bookmarks.store'), [
                'url' => 'https://example.com',
                'category_id' => $category->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.category.id', $category->id);
    }

    public function test_api_bookmark_preview(): void
    {
        Http::fake([
            '*' => Http::response(
                '<html><head><title>Judul Halaman</title><meta property="og:description" content="Deskripsi OG" /><meta property="og:image" content="https://example.com/image.jpg" /></head><body></body></html>',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.bookmarks.preview'), [
                'url' => 'https://example.com',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Judul Halaman')
            ->assertJsonPath('data.description', 'Deskripsi OG')
            ->assertJsonPath('data.image_url', 'https://example.com/image.jpg')
            ->assertJsonPath('data.domain', 'example.com');
    }

    public function test_api_bookmark_categories_list(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        BookmarkCategory::factory()->for($this->user)->count(2)->create();

        $response = $this->withToken($token)
            ->getJson(route('api.bookmark-categories.index'));

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_api_bookmark_category_create(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson(route('api.bookmark-categories.store'), [
                'name' => 'Coding',
                'color' => '#10b981',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Coding');
    }

    public function test_api_bookmark_show(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $bookmark = Bookmark::factory()->for($this->user)->create();

        $response = $this->withToken($token)
            ->getJson(route('api.bookmarks.show', $bookmark));

        $response->assertOk()
            ->assertJsonPath('data.id', $bookmark->id);
    }

    public function test_api_bookmark_show_milik_user_lain(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $otherUser = User::factory()->create();
        $bookmark = Bookmark::factory()->for($otherUser)->create();

        $this->withToken($token)
            ->getJson(route('api.bookmarks.show', $bookmark))
            ->assertNotFound();
    }

    public function test_api_bookmark_delete(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $bookmark = Bookmark::factory()->for($this->user)->create();

        $this->withToken($token)
            ->deleteJson(route('api.bookmarks.destroy', $bookmark))
            ->assertNoContent();

        $this->assertSoftDeleted($bookmark);
    }

    public function test_api_bookmark_category_delete(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $category = BookmarkCategory::factory()->for($this->user)->create();
        $bookmark = Bookmark::factory()->for($this->user)->create(['category_id' => $category->id]);

        $this->withToken($token)
            ->deleteJson(route('api.bookmark-categories.destroy', $category))
            ->assertNoContent();

        $this->assertDatabaseMissing('bookmark_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('bookmarks', ['id' => $bookmark->id, 'category_id' => null]);
    }
}
