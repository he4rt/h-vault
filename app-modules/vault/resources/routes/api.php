<?php

declare(strict_types=1);

use He4rt\Vault\Http\Controllers\Api\AlbumController;
use He4rt\Vault\Http\Controllers\Api\AuthTokenController;
use He4rt\Vault\Http\Controllers\Api\BucketController;
use He4rt\Vault\Http\Controllers\Api\FileController;
use He4rt\Vault\Http\Controllers\Api\FileServeController;
use He4rt\Vault\Http\Controllers\Api\SharedController;
use Illuminate\Support\Facades\Route;

Route::get('f/{token}/{filename}', FileServeController::class)
    ->where('filename', '.*')
    ->name('vault.files.serve');

Route::middleware('api')->prefix('api/v1')->group(function (): void {
    Route::post('auth/token', [AuthTokenController::class, 'store'])
        ->name('vault.auth.token');

    Route::put('files', [FileController::class, 'store'])
        ->name('vault.files.store');

    Route::get('files/{token}', [FileController::class, 'show'])
        ->name('vault.files.show');

    Route::patch('files/{token}', [FileController::class, 'update'])
        ->middleware('auth:sanctum')
        ->name('vault.files.update');

    Route::delete('files/{token}', [FileController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('vault.files.destroy');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('buckets', [BucketController::class, 'store'])->name('vault.buckets.store');
        Route::get('buckets/{token}', [BucketController::class, 'show'])->name('vault.buckets.show');
        Route::delete('buckets/{token}', [BucketController::class, 'destroy'])->name('vault.buckets.destroy');
        Route::get('buckets/{token}/files', [BucketController::class, 'files'])->name('vault.buckets.files');

        Route::post('buckets/{bucket}/albums', [AlbumController::class, 'store'])->name('vault.albums.store');
        Route::get('buckets/{bucket}/albums/{album}', [AlbumController::class, 'show'])->name('vault.albums.show');
        Route::delete('buckets/{bucket}/albums/{album}', [AlbumController::class, 'destroy'])->name('vault.albums.destroy');
    });

    Route::get('shared/buckets/{share_token}', [SharedController::class, 'bucket'])->name('vault.shared.bucket');
    Route::get('shared/albums/{share_token}', [SharedController::class, 'album'])->name('vault.shared.album');
});
