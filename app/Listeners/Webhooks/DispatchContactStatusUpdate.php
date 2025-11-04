<?php

declare(strict_types=1);

namespace App\Listeners\Webhooks;

use App\DataTransferObjects\ContactStatusUpdatePayload;
use App\Enums\ContactStatus;
use App\Enums\ContactStatusSource;
use App\Jobs\ProcessContactStatusUpdate;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Sendportal\Base\Events\Webhooks\MailjetWebhookReceived;

final class DispatchContactStatusUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'contact-status-webhooks';

    public function handle(MailjetWebhookReceived $event): void
    {
        foreach ($this->normalizePayload($event->payload) as $entry) {
            $payload = $this->resolveMailjetPayload($entry);

            if (! $payload) {
                continue;
            }

            ProcessContactStatusUpdate::dispatch($payload->toArray());
        }
    }

    /**
     * @return iterable<int, array<string, mixed>>
     */
    private function normalizePayload(array $payload): iterable
    {
        if (array_key_exists('event', $payload)) {
            yield $payload;

            return;
        }

        foreach ($payload as $item) {
            if (is_array($item)) {
                yield $item;
            }
        }
    }

    private function resolveMailjetPayload(array $payload): ?ContactStatusUpdatePayload
    {
        $event = Arr::get($payload, 'event');
        $messageId = Arr::get($payload, 'MessageID');

        if (! $event || ! $messageId) {
            Log::debug('Skipping Mailjet webhook event without event type or message id.', compact('payload'));

            return null;
        }

        $occurredAt = $this->resolveOccurredAt($payload);

        return match ($event) {
            'unsub', 'spam' => new ContactStatusUpdatePayload(
                ContactStatusSource::Mailjet,
                ContactStatus::UnsubscribedAll,
                (string) $messageId,
                $occurredAt,
                [
                    'mailjet' => $payload,
                    'reason' => Arr::get($payload, 'CustomCampaign'),
                ]
            ),
            'bounce' => new ContactStatusUpdatePayload(
                ContactStatusSource::Mailjet,
                ContactStatus::InvalidContact,
                (string) $messageId,
                $occurredAt,
                [
                    'mailjet' => $payload,
                    'severity' => Arr::get($payload, 'hard_bounce') ? 'permanent' : 'temporary',
                    'description' => Arr::get($payload, 'comment'),
                ]
            ),
            'blocked' => new ContactStatusUpdatePayload(
                ContactStatusSource::Mailjet,
                ContactStatus::InvalidContact,
                (string) $messageId,
                $occurredAt,
                [
                    'mailjet' => $payload,
                    'severity' => $this->blockedIsPermanent(Arr::get($payload, 'error_related_to')) ? 'permanent' : 'temporary',
                    'description' => Arr::get($payload, 'error'),
                ]
            ),
            default => null,
        };
    }

    private function resolveOccurredAt(array $payload): CarbonImmutable
    {
        $timestamp = Arr::get($payload, 'time');

        if (! $timestamp) {
            return CarbonImmutable::now();
        }

        return CarbonImmutable::parse((string) $timestamp);
    }

    private function blockedIsPermanent(?string $code): bool
    {
        if (! $code) {
            return false;
        }

        return in_array(strtolower($code), [
            'blacklisted',
            'spam reporter',
            'domain',
            'relay/access denied',
            'typofix',
            'content',
            'error in template language',
            'spam',
            'content blocked',
            'policy issue',
            'mailjet',
            'duplicate in campaign',
        ], true);
    }
}
