<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use App\DataTransferObjects\ContactStatusUpdatePayload;
use Illuminate\Support\Facades\Log;

final class GrpcContactClient
{
    public function __construct(
        private readonly ?string $endpoint,
        private readonly float $timeout,
    ) {
    }

    public function isEnabled(): bool
    {
        return ! empty($this->endpoint) && extension_loaded('grpc');
    }

    /**
     * @param array<string, mixed> $context
     */
    public function send(ContactStatusUpdatePayload $payload, array $context = []): void
    {
        if (! $this->isEnabled()) {
            Log::debug('Skipping gRPC contact status update; client disabled or endpoint missing.', [
                'endpoint' => $this->endpoint,
                'payload' => $payload->toArray(),
            ]);

            return;
        }

        // Placeholder: real gRPC request will be implemented once proto definitions are available.
        Log::info('gRPC contact status update placeholder invoked.', [
            'endpoint' => $this->endpoint,
            'timeout' => $this->timeout,
            'payload' => $payload->toArray(),
            'context' => $context,
        ]);
    }
}
