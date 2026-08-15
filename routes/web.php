<?php

use App\Services\QrCodeTemporaryFileService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Volt\Volt;

Route::view('/', 'home.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('workspace/chatbot', 'workspace.chatbot')->name('workspace.chatbot');
    Route::view('external-api', 'external-api')->name('external-api');
    Route::view('external-api/ytshorts', 'external-api.ytshorts')->name('external-api.ytshorts');
    Route::view('external-api/ytmp4', 'external-api.ytmp4')->name('external-api.ytmp4');
    Route::view('custom-scripts', 'custom-scripts')->name('custom-scripts');
    Route::view('execution-history', 'execution-history')->name('execution-history');
    Route::view('operations/api-key-backups', 'operations.api-key-backups')->name('operations.api-key-backups');
    Route::view('settings', 'settings')->name('settings');
    Route::view('profile', 'profile')->name('profile');
    Route::view('search', 'search')->name('search');
    Route::view('search/tokopedia', 'search.tokopedia')->name('search.tokopedia');
    Route::view('search/unsplash', 'search.unsplash')->name('search.unsplash');
    Route::get('search/freepik-image', function (): View {
        abort_unless(config('services.freepik.enabled'), 404);

        return view('search.freepik-image');
    })->name('search.freepik-image');
    Route::view('search/google-image', 'search.google-image')->name('search.google-image');
    Route::view('search/tiktok', 'search.tiktok')->name('search.tiktok');
    Route::view('search/anime-quotes', 'search.anime-quotes')->name('search.anime-quotes');
    Route::view('search/youtube', 'search.youtube')->name('search.youtube');
    Route::view('search/youtube-finder', 'search.youtube-finder')->name('search.youtube-finder');
    Route::view('search/youtube-channel', 'search.youtube-channel')->name('search.youtube-channel');
    Route::view('tools', 'tools')->name('tools');
    Route::view('tools/character-sheet', 'tools.character-sheet')->name('tools.character-sheet');
    Route::view('tools/split-cash', 'tools.split-cash')->name('tools.split-cash');
    Route::view('tools/calculator-pvc', 'tools.pvc-calculator')->name('tools.calculator-pvc');
    Route::view('tools/wall-meter', 'tools.wall-meter')->name('tools.wall-meter');
    Route::view('tools/cek-resi', 'tools.cek-resi')->name('tools.cek-resi');
    Route::view('tools/holiday', 'tools.holiday')->name('tools.holiday');
    Route::view('tools/wilayah-api', 'tools.wilayah-api')->name('tools.wilayah-api');
    Route::view('tools/send-whatsapp', 'tools.send-whatsapp')->name('tools.send-whatsapp');
    Route::get('qr-code/download/{filename}', function (string $filename, QrCodeTemporaryFileService $temporaryFileService) {
        $path = $temporaryFileService->path($filename);
        $storage = Storage::disk($temporaryFileService->disk());

        abort_unless($storage->exists($path), 404);

        return response()->streamDownload(function () use ($storage, $path): void {
            echo $storage->get($path);
        }, $filename, [
            'Content-Type' => $temporaryFileService->mimeType($filename),
        ]);
    })->name('qr-code.download');
    Volt::route('qr-code/generate', 'qr-code.generate')->name('qr-code.generate');
    Route::get('generation/image', function (): View {
        abort_unless(config('services.freepik.enabled'), 404);

        return view('generation.image');
    })->name('generation.index');
    Route::get('generation/video', function (): View {
        abort_unless(config('services.freepik.enabled'), 404);

        return view('generation.video');
    })->name('generation.video');
    Route::view('image-ai/image2prompt', 'image-ai.image2prompt')->name('image-ai.image2prompt');
    Route::view('image-ai/generate-image-z', 'image-ai.generate-image-z')->name('image-ai.generate-image-z');
    Route::get('image-ai/improve-prompt', function (): View {
        abort_unless(config('services.freepik.enabled'), 404);

        return view('image-ai.improve-prompt');
    })->name('image-ai.improve-prompt');
    Route::view('internet', 'internet')->name('internet');
    Route::view('internet/currency-exchange-rate', 'internet.currency-exchange-rate')->name('internet.currency-exchange-rate');
    Route::view('internet/proxy-validate', 'internet.proxy-validate')->name('internet.proxy-validate');
    Route::view('internet/whois', 'internet.whois')->name('internet.whois');
    Route::view('internet/bookmark', 'internet.bookmark')->name('internet.bookmark');
    Route::view('apifreaks-tools', 'apifreaks-tools')->name('apifreaks-tools');
    Route::view('apifreaks-tools/credit-usage', 'apifreaks-tools.credit-usage')->name('apifreaks-tools.credit-usage');
    Route::view('apifreaks-tools/domain-whois-lookup', 'apifreaks-tools.domain-whois-lookup')->name('apifreaks-tools.domain-whois-lookup');
    Route::view('apifreaks-tools/domain-whois-history-lookup', 'apifreaks-tools.domain-whois-history-lookup')->name('apifreaks-tools.domain-whois-history-lookup');
    Route::view('apifreaks-tools/domain-search', 'apifreaks-tools.domain-search')->name('apifreaks-tools.domain-search');
    Route::view('apifreaks-tools/subdomain-lookup', 'apifreaks-tools.subdomain-lookup')->name('apifreaks-tools.subdomain-lookup');
    Route::view('apifreaks-tools/commodity-symbols', 'apifreaks-tools.commodity-symbols')->name('apifreaks-tools.commodity-symbols');
    Route::view('apifreaks-tools/live-commodity-prices', 'apifreaks-tools.live-commodity-prices')->name('apifreaks-tools.live-commodity-prices');
    Route::view('apifreaks-tools/historical-commodity-prices', 'apifreaks-tools.historical-commodity-prices')->name('apifreaks-tools.historical-commodity-prices');
    Route::view('apifreaks-tools/ip-geolocation-lookup', 'apifreaks-tools.ip-geolocation-lookup')->name('apifreaks-tools.ip-geolocation-lookup');
    Route::view('apify-scraper/gmaps-1-0', 'apify-scraper.gmaps-1-0')->name('apify-scraper.gmaps-1-0');
});

require __DIR__.'/auth.php';
