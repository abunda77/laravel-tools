<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitCashPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_cash_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tools/split-cash');

        $response
            ->assertOk()
            ->assertSee('Split Cash')
            ->assertSee('x-data="splitCash()"', false)
            ->assertDontSee('alpine:init');
    }

    public function test_split_cash_alpine_component_is_registered_in_global_bundle(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("Alpine.data('splitCash'", $script);
    }
}
