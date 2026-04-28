<?php

namespace Tests\Feature\Tools;

use App\Livewire\Tools\WallMeter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WallMeterTest extends TestCase
{
    use RefreshDatabase;

    public function test_wall_meter_page_requires_authentication(): void
    {
        $response = $this->get(route('tools.wall-meter'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_wall_meter_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tools.wall-meter'));

        $response->assertOk();
        $response->assertSeeLivewire(WallMeter::class);
    }

    public function test_wall_meter_can_calculate_trigonometry_result(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WallMeter::class)
            ->set('horizontalDistance', 10.0)
            ->set('elevationAngleDeg', 30.0)
            ->set('instrumentHeight', 1.5)
            ->call('recalculate')
            ->assertHasNoErrors()
            ->assertSet('verticalComponent', 5.7735)
            ->assertSet('totalHeight', 7.2735)
            ->assertSet('sightLineLength', 11.547)
            ->assertSet('tangentValue', 0.57735);
    }

    public function test_wall_meter_validates_input_ranges(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WallMeter::class)
            ->set('horizontalDistance', 0.05)
            ->set('elevationAngleDeg', 90)
            ->set('instrumentHeight', 0)
            ->call('recalculate')
            ->assertHasErrors([
                'horizontalDistance',
                'elevationAngleDeg',
                'instrumentHeight',
            ]);
    }
}
