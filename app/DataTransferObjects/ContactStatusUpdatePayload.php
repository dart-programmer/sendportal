<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\ContactStatus;
use App\Enums\ContactStatusSource;
use Carbon\CarbonImmutable;
use JsonSerializable;

final class ContactStatusUpdatePayload implements JsonSerializable
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly ContactStatusSource $source,
        public readonly ContactStatus $status,
        public readonly string $messageId,
        public readonly CarbonImmutable $occurredAt,
        public readonly array $metadata = [],
    ) {
    }

    /**
     * @return array{source: string, status: string, message_id: string, occurred_at: string, metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source->value,
            'status' => $this->status->value,
            'message_id' => $this->messageId,
            'occurred_at' => $this->occurredAt->toIso8601String(),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array{source: string, status: string, message_id: string, occurred_at: string, metadata?: array<string, mixed>} $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            ContactStatusSource::from($payload['source']),
            ContactStatus::from($payload['status']),
            $payload['message_id'],
            CarbonImmutable::parse($payload['occurred_at']),
            $payload['metadata'] ?? []
        );
    }
}

