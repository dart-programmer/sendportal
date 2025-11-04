<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use App\Contracts\Contacts\ContactStatusUpdaterInterface;
use App\DataTransferObjects\ContactStatusUpdatePayload;
use Illuminate\Support\Facades\Log;

final class GrpcContactStatusUpdater implements ContactStatusUpdaterInterface
{
    public function __construct(private readonly GrpcContactClient $client)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function push(ContactStatusUpdatePayload $payload, array $context = []): void
    {
        if (! $this->client->isEnabled()) {
            Log::debug('Contact status update skipped because gRPC client is disabled.', [
                'payload' => $payload->toArray(),
            ]);

            return;
        }

        $this->client->send($payload, $context);
    }
}
