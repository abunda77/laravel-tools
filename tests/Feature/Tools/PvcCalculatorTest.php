<?php

namespace Tests\Feature\Tools;

use App\Livewire\Tools\PvcCalculator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PvcCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_pvc_calculator_page_requires_authentication(): void
    {
        $response = $this->get(route('tools.calculator-pvc'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_pvc_calculator_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tools.calculator-pvc'));

        $response->assertOk();
        $response->assertSeeLivewire(PvcCalculator::class);
    }

    public function test_pvc_calculator_can_estimate_strip_panel_quantity_and_cost(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PvcCalculator::class)
            ->set('fieldLength', '3')
            ->set('fieldLengthUnit', 'm')
            ->set('fieldWidth', '3')
            ->set('fieldWidthUnit', 'm')
            ->set('productPreset', 'panel_20x300')
            ->set('wastePercentage', '10')
            ->call('calculate')
            ->assertHasNoErrors()
            ->assertSet('result.field_area_square_meters', 9.0)
            ->assertSet('result.sheet_area_square_meters', 0.6)
            ->assertSet('result.base_sheets', 15)
            ->assertSet('result.recommended_sheets', 17)
            ->assertSet('result.base_total_cost', 600000)
            ->assertSet('result.recommended_total_cost', 680000)
            ->assertSee('17 lembar');
    }

    public function test_pvc_calculator_can_handle_board_measurement_in_centimeters(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PvcCalculator::class)
            ->set('fieldLength', '250')
            ->set('fieldLengthUnit', 'cm')
            ->set('fieldWidth', '120')
            ->set('fieldWidthUnit', 'cm')
            ->set('productPreset', 'board_122x244_5')
            ->set('wastePercentage', '0')
            ->call('calculate')
            ->assertHasNoErrors()
            ->assertSet('result.field_area_square_meters', 3.0)
            ->assertSet('result.base_sheets', 2)
            ->assertSet('result.recommended_sheets', 2)
            ->assertSet('result.base_total_cost', 320000)
            ->assertSee('Board / sheet');
    }

    public function test_pvc_calculator_validates_positive_dimensions(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PvcCalculator::class)
            ->set('fieldLength', '0')
            ->call('calculate')
            ->assertHasErrors(['fieldLength']);
    }
}
