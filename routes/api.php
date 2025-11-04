<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Webhooks\D7WebhooksController;
use App\Http\Middleware\RequireWorkspace;
use Illuminate\Support\Facades\Route;
use Sendportal\Base\Facades\Sendportal;

Route::middleware([
    config('sendportal-host.throttle_middleware'),
    RequireWorkspace::class,
])->group(function () {
    // Auth'd API routes (workspace-level auth!).
    Sendportal::apiRoutes();
});

// Non-auth'd API routes.
Sendportal::publicApiRoutes();

Route::prefix('v1/webhooks')->name('sendportal.api.webhooks.')->group(function () {
    Route::post('d7-network', [D7WebhooksController::class, 'handle'])->name('d7-network');
});
