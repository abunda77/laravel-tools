<?php

namespace App\Livewire\Tools;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Number;
use Livewire\Component;

class PvcCalculator extends Component
{
    public string $fieldLength = '3';

    public string $fieldLengthUnit = 'm';

    public string $fieldWidth = '3';

    public string $fieldWidthUnit = 'm';

    public string $productPreset = 'panel_20x300';

    public string $sheetWidthCm = '20';

    public string $sheetLengthCm = '300';

    public string $sheetThicknessMm = '8';

    public string $pricePerSheet = '40000';

    public string $wastePercentage = '10';

    public ?array $result = null;

    /**
     * @var array<string, array{label: string, type: string, width_cm: int, length_cm: int, thickness_mm: int, price_per_sheet: int, price_note: string}>
     */
    private const PRODUCT_PRESETS = [
        'panel_20x290' => [
            'label' => 'PVC panel strip 20 cm x 2,9 m x 7 mm',
            'type' => 'Panel strip',
            'width_cm' => 20,
            'length_cm' => 290,
            'thickness_mm' => 7,
            'price_per_sheet' => 39000,
            'price_note' => 'Estimasi umum Rp39.000/lembar.',
        ],
        'panel_20x300' => [
            'label' => 'PVC panel strip 20 cm x 3 m x 8 mm',
            'type' => 'Panel strip',
            'width_cm' => 20,
            'length_cm' => 300,
            'thickness_mm' => 8,
            'price_per_sheet' => 40000,
            'price_note' => 'Estimasi umum Rp40.000/lembar.',
        ],
        'panel_25x300' => [
            'label' => 'PVC panel strip 25 cm x 3 m x 8 mm',
            'type' => 'Panel strip',
            'width_cm' => 25,
            'length_cm' => 300,
            'thickness_mm' => 8,
            'price_per_sheet' => 50000,
            'price_note' => 'Estimasi umum Rp50.000/lembar.',
        ],
        'panel_30x300' => [
            'label' => 'PVC panel strip 30 cm x 3 m x 8 mm',
            'type' => 'Panel strip',
            'width_cm' => 30,
            'length_cm' => 300,
            'thickness_mm' => 8,
            'price_per_sheet' => 67500,
            'price_note' => 'Estimasi umum Rp67.500/lembar.',
        ],
        'panel_40x300' => [
            'label' => 'PVC panel strip 40 cm x 3 m x 8 mm',
            'type' => 'Panel strip',
            'width_cm' => 40,
            'length_cm' => 300,
            'thickness_mm' => 8,
            'price_per_sheet' => 90000,
            'price_note' => 'Estimasi umum Rp90.000/lembar.',
        ],
        'board_122x244_3' => [
            'label' => 'PVC board 122 cm x 244 cm x 3 mm',
            'type' => 'Board / sheet',
            'width_cm' => 122,
            'length_cm' => 244,
            'thickness_mm' => 3,
            'price_per_sheet' => 112500,
            'price_note' => 'Estimasi umum Rp112.500/lembar.',
        ],
        'board_122x244_5' => [
            'label' => 'PVC board 122 cm x 244 cm x 5 mm',
            'type' => 'Board / sheet',
            'width_cm' => 122,
            'length_cm' => 244,
            'thickness_mm' => 5,
            'price_per_sheet' => 160000,
            'price_note' => 'Estimasi umum Rp160.000/lembar.',
        ],
        'board_122x244_8' => [
            'label' => 'PVC board 122 cm x 244 cm x 8 mm',
            'type' => 'Board / sheet',
            'width_cm' => 122,
            'length_cm' => 244,
            'thickness_mm' => 8,
            'price_per_sheet' => 225000,
            'price_note' => 'Estimasi umum Rp225.000/lembar.',
        ],
        'board_122x244_10' => [
            'label' => 'PVC board 122 cm x 244 cm x 10 mm',
            'type' => 'Board / sheet',
            'width_cm' => 122,
            'length_cm' => 244,
            'thickness_mm' => 10,
            'price_per_sheet' => 310000,
            'price_note' => 'Estimasi umum Rp310.000/lembar.',
        ],
        'custom' => [
            'label' => 'Custom ukuran dan harga',
            'type' => 'Custom',
            'width_cm' => 20,
            'length_cm' => 300,
            'thickness_mm' => 8,
            'price_per_sheet' => 40000,
            'price_note' => 'Isi manual sesuai ukuran dan harga toko yang Anda pakai.',
        ],
    ];

    public function mount(): void
    {
        $this->applyPreset();
        $this->calculate();
    }

    public function updatedProductPreset(): void
    {
        $this->applyPreset();
        $this->calculate();
    }

    public function calculate(): void
    {
        $validated = $this->validate([
            'fieldLength' => ['required', 'numeric', 'gt:0'],
            'fieldLengthUnit' => ['required', 'in:m,cm'],
            'fieldWidth' => ['required', 'numeric', 'gt:0'],
            'fieldWidthUnit' => ['required', 'in:m,cm'],
            'productPreset' => ['required', 'string'],
            'sheetWidthCm' => ['required', 'numeric', 'gt:0'],
            'sheetLengthCm' => ['required', 'numeric', 'gt:0'],
            'sheetThicknessMm' => ['required', 'numeric', 'gt:0'],
            'pricePerSheet' => ['required', 'numeric', 'gte:0'],
            'wastePercentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $fieldLengthCm = $this->toCentimeters((float) $validated['fieldLength'], $validated['fieldLengthUnit']);
        $fieldWidthCm = $this->toCentimeters((float) $validated['fieldWidth'], $validated['fieldWidthUnit']);
        $sheetWidthCm = (float) $validated['sheetWidthCm'];
        $sheetLengthCm = (float) $validated['sheetLengthCm'];
        $wasteFactor = 1 + ((float) $validated['wastePercentage'] / 100);

        $fieldAreaSquareMeters = ($fieldLengthCm * $fieldWidthCm) / 10000;
        $sheetAreaSquareMeters = ($sheetWidthCm * $sheetLengthCm) / 10000;
        $baseSheets = (int) ceil($fieldAreaSquareMeters / $sheetAreaSquareMeters);
        $recommendedSheets = (int) ceil($baseSheets * $wasteFactor);
        $pricePerSheet = (float) $validated['pricePerSheet'];

        $this->result = [
            'preset_label' => $this->selectedPreset()['label'],
            'product_type' => $this->selectedPreset()['type'],
            'field_area_square_meters' => round($fieldAreaSquareMeters, 2),
            'sheet_area_square_meters' => round($sheetAreaSquareMeters, 4),
            'base_sheets' => $baseSheets,
            'recommended_sheets' => $recommendedSheets,
            'waste_percentage' => (float) $validated['wastePercentage'],
            'price_per_sheet' => (int) round($pricePerSheet),
            'base_total_cost' => (int) round($baseSheets * $pricePerSheet),
            'recommended_total_cost' => (int) round($recommendedSheets * $pricePerSheet),
            'coverage_margin_square_meters' => round(($recommendedSheets * $sheetAreaSquareMeters) - $fieldAreaSquareMeters, 2),
            'sheet_width_cm' => round($sheetWidthCm, 2),
            'sheet_length_cm' => round($sheetLengthCm, 2),
            'sheet_thickness_mm' => round((float) $validated['sheetThicknessMm'], 2),
            'price_note' => $this->selectedPreset()['price_note'],
        ];
    }

    public function formattedCurrency(int|float $amount): string
    {
        return Number::currency((float) $amount, in: 'IDR', locale: 'id');
    }

    public function render(): View
    {
        return view('livewire.tools.pvc-calculator');
    }

    /**
     * @return array<string, array{label: string, type: string, width_cm: int, length_cm: int, thickness_mm: int, price_per_sheet: int, price_note: string}>
     */
    public function presets(): array
    {
        return self::PRODUCT_PRESETS;
    }

    private function applyPreset(): void
    {
        $preset = $this->selectedPreset();

        $this->sheetWidthCm = (string) $preset['width_cm'];
        $this->sheetLengthCm = (string) $preset['length_cm'];
        $this->sheetThicknessMm = (string) $preset['thickness_mm'];
        $this->pricePerSheet = (string) $preset['price_per_sheet'];
    }

    /**
     * @return array{label: string, type: string, width_cm: int, length_cm: int, thickness_mm: int, price_per_sheet: int, price_note: string}
     */
    private function selectedPreset(): array
    {
        return self::PRODUCT_PRESETS[$this->productPreset] ?? self::PRODUCT_PRESETS['panel_20x300'];
    }

    private function toCentimeters(float $value, string $unit): float
    {
        if ($unit === 'm') {
            return $value * 100;
        }

        return $value;
    }
}
