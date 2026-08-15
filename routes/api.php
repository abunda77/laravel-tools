<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookmarkController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->name('api.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll'])->name('api.auth.logout-all');
        Route::get('auth/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::get('auth/tokens', [AuthController::class, 'tokens'])->name('api.auth.tokens');
        Route::delete('auth/tokens/{tokenId}', [AuthController::class, 'revokeToken'])->name('api.auth.revoke-token');

        Route::post('bookmarks/preview', [BookmarkController::class, 'preview'])->name('api.bookmarks.preview');
        Route::get('bookmarks', [BookmarkController::class, 'index'])->name('api.bookmarks.index');
        Route::post('bookmarks', [BookmarkController::class, 'store'])->name('api.bookmarks.store');
        Route::get('bookmarks/{bookmark}', [BookmarkController::class, 'show'])->name('api.bookmarks.show');
        Route::match(['put', 'patch'], 'bookmarks/{bookmark}', [BookmarkController::class, 'update'])->name('api.bookmarks.update');
        Route::delete('bookmarks/{bookmark}', [BookmarkController::class, 'destroy'])->name('api.bookmarks.destroy');

        Route::get('bookmark-categories', [BookmarkController::class, 'categoriesIndex'])->name('api.bookmark-categories.index');
        Route::post('bookmark-categories', [BookmarkController::class, 'categoriesStore'])->name('api.bookmark-categories.store');
        Route::match(['put', 'patch'], 'bookmark-categories/{category}', [BookmarkController::class, 'categoriesUpdate'])->name('api.bookmark-categories.update');
        Route::delete('bookmark-categories/{category}', [BookmarkController::class, 'categoriesDestroy'])->name('api.bookmark-categories.destroy');
    });
});
