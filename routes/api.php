<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [\App\Http\Controllers\Api\AuthController::class, 'token']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/auth/token', [\App\Http\Controllers\Api\AuthController::class, 'revoke']);
    Route::get('/organizations', [\App\Http\Controllers\Api\OrganizationController::class, 'index']);
    Route::get('/organizations/{organization}', [\App\Http\Controllers\Api\OrganizationController::class, 'show']);
    Route::get('/organizations/{organization}/credentials', [\App\Http\Controllers\Api\CredentialController::class, 'index']);
    Route::post('/organizations/{organization}/credentials', [\App\Http\Controllers\Api\CredentialController::class, 'store']);
    Route::put('/organizations/{organization}/credentials/{credential}', [\App\Http\Controllers\Api\CredentialController::class, 'update']);
    Route::delete('/organizations/{organization}/credentials/{credential}', [\App\Http\Controllers\Api\CredentialController::class, 'destroy']);
    Route::get('/credentials/search', [\App\Http\Controllers\Api\CredentialController::class, 'search']);
});
