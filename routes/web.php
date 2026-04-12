<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('workspace/chatbot', 'workspace.chatbot')->name('workspace.chatbot');
    Route::view('external-api', 'external-api')->name('external-api');
    Route::view('custom-scripts', 'custom-scripts')->name('custom-scripts');
    Route::view('execution-history', 'execution-history')->name('execution-history');
    Route::view('operations/api-key-backups', 'operations.api-key-backups')->name('operations.api-key-backups');
    Route::view('settings', 'settings')->name('settings');
    Route::view('profile', 'profile')->name('profile');
    Route::view('search', 'search')->name('search');
    Route::view('search/tokopedia', 'search.tokopedia')->name('search.tokopedia');
    Route::view('search/unsplash', 'search.unsplash')->name('search.unsplash');
    Route::view('search/google-image', 'search.google-image')->name('search.google-image');
    Route::view('search/tiktok', 'search.tiktok')->name('search.tiktok');
    Route::view('tools', 'tools')->name('tools');
    Route::view('tools/split-cash', 'tools.split-cash')->name('tools.split-cash');
    Route::view('tools/cek-resi', 'tools.cek-resi')->name('tools.cek-resi');
    Route::view('generation/image', 'generation.image')->name('generation.index');
    Route::view('generation/video', 'generation.video')->name('generation.video');
    Route::view('image-ai/image2prompt', 'image-ai.image2prompt')->name('image-ai.image2prompt');
    Route::view('image-ai/improve-prompt', 'image-ai.improve-prompt')->name('image-ai.improve-prompt');
    Route::view('internet', 'internet')->name('internet');
    Route::view('internet/currency-exchange-rate', 'internet.currency-exchange-rate')->name('internet.currency-exchange-rate');
    Route::view('internet/proxy-validate', 'internet.proxy-validate')->name('internet.proxy-validate');
    Route::view('internet/whois', 'internet.whois')->name('internet.whois');
});

require __DIR__.'/auth.php';
