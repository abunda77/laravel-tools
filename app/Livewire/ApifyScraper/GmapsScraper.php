<?php

namespace App\Livewire\ApifyScraper;

use App\Models\ApiKey;
use App\Services\Apify\GmapsScraperService;
use Dompdf\Dompdf;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GmapsScraper extends Component
{
    public string $searchQuery = '';

    public string $gmapsUrl = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $areaWidth = '';

    public string $areaHeight = '';

    public string $maxResults = '';

    public bool $hasSavedApiKey = false;

    /**
     * @var list<array<string, mixed>>
     */
    public array $results = [];

    /**
     * @var list<string>
     */
    public array $columns = [];

    public bool $hasResults = false;

    public int $resultsCount = 0;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', GmapsScraperService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function run(GmapsScraperService $gmapsScraperService): void
    {
        $validated = $this->validate([
            'searchQuery' => ['required', 'string', 'max:255'],
            'gmapsUrl' => ['nullable', 'url', 'max:2048'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'areaWidth' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'areaHeight' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'maxResults' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        try {
            $this->results = $gmapsScraperService->scrape([
                'search_query' => $validated['searchQuery'],
                'gmaps_url' => $validated['gmapsUrl'] ?? '',
                'latitude' => $validated['latitude'] ?? '',
                'longitude' => $validated['longitude'] ?? '',
                'area_width' => $validated['areaWidth'] ?? 20,
                'area_height' => $validated['areaHeight'] ?? 20,
                'max_results' => $validated['maxResults'] ?? 500,
            ]);

            $this->columns = $this->extractColumns($this->results);
            $this->resultsCount = count($this->results);
            $this->hasResults = $this->resultsCount > 0;
            $this->errorMessage = null;
            $this->hasSavedApiKey = true;
        } catch (\Throwable $throwable) {
            $this->results = [];
            $this->columns = [];
            $this->resultsCount = 0;
            $this->hasResults = false;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function exportCsv(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $this->columns);

            foreach ($this->results as $row) {
                fputcsv($handle, $this->rowValues($row));
            }

            fclose($handle);
        }, 'apify-gmaps-1-0.csv');
    }

    public function exportXlsx(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($this->columns, null, 'A1');

            $rowNumber = 2;

            foreach ($this->results as $row) {
                $sheet->fromArray($this->rowValues($row), null, 'A'.$rowNumber);
                $rowNumber++;
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'apify-gmaps-1-0.xlsx');
    }

    public function exportPdf(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $dompdf = new Dompdf([
                'isRemoteEnabled' => false,
            ]);
            $dompdf->loadHtml($this->pdfMarkup());
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            echo $dompdf->output();
        }, 'apify-gmaps-1-0.pdf');
    }

    public function getPrettyJsonProperty(): string
    {
        return json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    public function isUrlValue(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function render(): View
    {
        return view('livewire.apify-scraper.gmaps-scraper');
    }

    /**
     * @param  list<array<string, mixed>>  $results
     * @return list<string>
     */
    private function extractColumns(array $results): array
    {
        $columns = [];

        foreach ($results as $row) {
            foreach (array_keys($row) as $column) {
                if (! in_array($column, $columns, true)) {
                    $columns[] = $column;
                }
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<mixed>
     */
    private function rowValues(array $row): array
    {
        return array_map(
            fn (string $column): mixed => $row[$column] ?? null,
            $this->columns,
        );
    }

    private function pdfMarkup(): string
    {
        $headers = implode('', array_map(
            fn (string $column): string => '<th>'.e(Str::headline($column)).'</th>',
            $this->columns,
        ));

        $rows = implode('', array_map(function (array $row): string {
            $cells = implode('', array_map(
                fn (mixed $value): string => '<td>'.e((string) ($value ?? '-')).'</td>',
                $this->rowValues($row),
            ));

            return '<tr>'.$cells.'</tr>';
        }, $this->results));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 12px; }
        p { margin: 0 0 16px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Apify GMaps 1.0 Export</h1>
    <p>Total rows: {$this->resultsCount}</p>
    <table>
        <thead>
            <tr>{$headers}</tr>
        </thead>
        <tbody>{$rows}</tbody>
    </table>
</body>
</html>
HTML;
    }
}
