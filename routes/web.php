<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('external-api', 'external-api')->name('external-api');
    Route::view('custom-scripts', 'custom-scripts')->name('custom-scripts');
    Route::view('execution-history', 'execution-history')->name('execution-history');
    Route::view('operations/api-key-backups', 'operations.api-key-backups')->name('operations.api-key-backups');
    Route::view('settings', 'settings')->name('settings');
    Route::view('profile', 'profile')->name('profile');
    Route::view('search', 'search')->name('search');
    Route::view('tools', 'tools')->name('tools');
    Route::view('tools/split-cash', 'tools.split-cash')->name('tools.split-cash');
    Route::view('generation/image', 'generation.image')->name('generation.index');
    Route::view('image-ai/image2prompt', 'image-ai.image2prompt')->name('image-ai.image2prompt');
    Route::view('image-ai/improve-prompt', 'image-ai.improve-prompt')->name('image-ai.improve-prompt');
    Route::view('internet', 'internet')->name('internet');
    Route::view('internet/currency-exchange-rate', 'internet.currency-exchange-rate')->name('internet.currency-exchange-rate');
});

require __DIR__.'/auth.php';
