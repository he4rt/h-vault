<?php

declare(strict_types=1);

use He4rt\Vault\Http\Controllers\Api\AuthTokenController;
use He4rt\Vault\Http\Controllers\Api\FileController;
use He4rt\Vault\Http\Controllers\Api\FileServeController;
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
});
