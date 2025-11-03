<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class D7WebhooksController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        Log::info('D7 webhook placeholder received.', [
            'payload' => $request->all(),
        ]);

        return response()->json([
            'status' => 'accepted',
            'message' => 'D7 Network webhook processing not yet implemented.',
        ], 202);
    }
}
