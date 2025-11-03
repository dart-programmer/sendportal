<?php

declare(strict_types=1);

namespace App\Contracts\Contacts;

use App\DataTransferObjects\ContactStatusUpdatePayload;

interface ContactStatusUpdaterInterface
{
    /**
     * Push a contact status update to configured downstream systems (gRPC, etc.).
     *
     * @param array<string, mixed> $context Additional context resolved in the job (workspace, subscriber, mongo document, etc.).
     */
    public function push(ContactStatusUpdatePayload $payload, array $context = []): void;
}
