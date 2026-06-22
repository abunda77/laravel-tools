<?php

namespace Tests\Feature\Tools;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterSheetPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_sheet_submenu_is_visible_in_tools_navigation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Tools')
            ->assertSee('Character Sheet');
    }

    public function test_character_sheet_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tools.character-sheet'));

        $response
            ->assertOk()
            ->assertSee('Character Sheet')
            ->assertSee('Character Sheet Generator')
            ->assertSee('Image to JSON Prompt Engineer')
            ->assertSee('Story Board Generator')
            ->assertSee('Image to JSON Prompt')
            ->assertSee('Character Sheet Director')
            ->assertSee('Video & Image Prompt Generator')
            ->assertSee('https://chatgpt.com/g/g-NF7Nl0SuH-character-sheet-generator', false)
            ->assertSee('https://chatgpt.com/g/g-6a14204b90f481918380f88a135896ee-image-to-json-prompt-engineer', false)
            ->assertSee('https://chatgpt.com/g/g-6a25f2ed0600819188dc4e347003fa53-story-board-generator', false)
            ->assertSee('https://gemini.google.com/gem/1lO1TZAM6m4lAyHE0-ey02571I8lio0_j?usp=sharing', false)
            ->assertSee('https://gemini.google.com/gem/1by6b_R1ZReYog-wCHqIpdDzc8ayLFnh0?usp=sharing', false)
            ->assertSee('https://gemini.google.com/share/a257460e7f1b', false);
    }
}
