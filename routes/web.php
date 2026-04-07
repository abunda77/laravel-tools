<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('external-api', 'external-api')->name('external-api');
    Route::view('custom-scripts', 'custom-scripts')->name('custom-scripts');
    Route::view('execution-history', 'execution-history')->name('execution-history');
    Route::view('settings', 'settings')->name('settings');
    Route::view('profile', 'profile')->name('profile');
    Route::view('search', 'search')->name('search');
    Route::view('tools', 'tools')->name('tools');
    Route::view('internet', 'internet')->name('internet');
});

require __DIR__.'/auth.php';
