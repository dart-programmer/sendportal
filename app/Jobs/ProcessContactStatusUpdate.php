<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Contacts\ContactStatusUpdaterInterface;
use App\DataTransferObjects\ContactStatusUpdatePayload;
use App\Models\ContactStatusUpdate;
use App\Repositories\MongoContactRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Sendportal\Base\Models\Message;

class ProcessContactStatusUpdate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array{source: string, status: string, message_id: string, occurred_at: string, metadata?: array<string, mixed>} $payload
     */
    public function __construct(private readonly array $payload)
    {
        $this->queue = 'contact-status-updates';
    }

    public function handle(ContactStatusUpdaterInterface $updater, MongoContactRepository $mongoRepository): void
    {
        $payload = ContactStatusUpdatePayload::fromArray($this->payload);

        $message = Message::with('subscriber')->where('message_id', $payload->messageId)->first();

        if (! $message) {
            Log::warning('Contact status update skipped: message not found for webhook payload.', [
                'payload' => $payload->toArray(),
            ]);

            return;
        }

        $context = [
            'workspace_id' => $message->workspace_id,
            'subscriber_id' => $message->subscriber_id,
            'message_id' => $message->message_id,
            'email' => $message->recipient_email,
        ];

        if ($message->subscriber) {
            $context['subscriber'] = Arr::only($message->subscriber->toArray(), [
                'id',
                'first_name',
                'last_name',
                'email',
                'phone',
            ]);
        }

        if ($mongoRepository->isEnabled()) {
            $mongoDocument = $mongoRepository->findByEmail($message->recipient_email);

            if ($mongoDocument !== null) {
                $context['mongo'] = $mongoDocument;
            }
        }

        $updater->push($payload, $context);

        ContactStatusUpdate::create([
            'workspace_id' => $message->workspace_id,
            'subscriber_id' => $message->subscriber_id,
            'status' => $payload->status->value,
            'source' => $payload->source->value,
            'message_id' => $message->message_id,
            'occurred_at' => $payload->occurredAt,
            'processed_at' => now(),
            'meta' => [
                'payload' => $payload->metadata,
                'context' => Arr::only($context, ['email', 'subscriber_id', 'workspace_id']),
            ],
        ]);
    }
}
