<?php

declare(strict_types=1);

namespace App\Pipelines\Campaigns;

use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\Subscriber;
use Sendportal\Base\Models\Tag;
use Sendportal\Base\Pipelines\Campaigns\CreateMessages;
use Illuminate\Support\Facades\Log;

class CreateSlicedMessages extends CreateMessages
{
    protected int $sliceOffset = 0;

    protected int $sliceLimit = 0;

    protected int $processedOverall = 0;

    protected int $dispatchedThisRun = 0;

    protected bool $stopProcessing = false;

    public function handle(Campaign $campaign, $next)
    {
        $this->sliceLimit = (int) ($campaign->slice_enabled ? ($campaign->slice_size ?? 0) : 0);
        $this->sliceOffset = (int) ($campaign->slice_offset ?? 0);
        $this->processedOverall = 0;
        $this->dispatchedThisRun = 0;
        $this->stopProcessing = false;

        $result = parent::handle($campaign, $next);

        $campaign->slice_processed = $this->dispatchedThisRun;

        return $result;
    }

    protected function handleTags(Campaign $campaign)
    {
        foreach ($campaign->tags as $tag) {
            if ($this->stopProcessing) {
                break;
            }

            $this->handleTag($campaign, $tag);
        }
    }

    protected function handleAllSubscribers(Campaign $campaign)
    {
        Subscriber::where('workspace_id', $campaign->workspace_id)
            ->whereNull('unsubscribed_at')
            ->chunkById(1000, function ($subscribers) use ($campaign) {
                $this->dispatchToSubscriber($campaign, $subscribers);

                if ($this->stopProcessing) {
                    return false;
                }
            }, 'id');
    }

    protected function handleTag(Campaign $campaign, Tag $tag): void
    {
        Log::info('- Handling Campaign Tag id=' . $tag->id);

        $tag->subscribers()
            ->whereNull('unsubscribed_at')
            ->chunkById(1000, function ($subscribers) use ($campaign) {
                $this->dispatchToSubscriber($campaign, $subscribers);

                if ($this->stopProcessing) {
                    return false;
                }
            }, 'sendportal_subscribers.id');

        if ($this->stopProcessing) {
            return;
        }
    }

    protected function dispatchToSubscriber(Campaign $campaign, $subscribers)
    {
        Log::info('- Number of subscribers in this chunk: ' . count($subscribers));

        foreach ($subscribers as $subscriber) {
            if (! $this->canSendToSubscriber($campaign->id, $subscriber->id)) {
                continue;
            }

            $this->processedOverall++;

            if ($this->sliceLimit > 0) {
                if ($this->processedOverall <= $this->sliceOffset) {
                    continue;
                }

                if ($this->dispatchedThisRun >= $this->sliceLimit) {
                    $this->stopProcessing = true;
                    break;
                }
            }

            $this->dispatch($campaign, $subscriber);
            $this->dispatchedThisRun++;

            if ($this->sliceLimit > 0 && $this->dispatchedThisRun >= $this->sliceLimit) {
                $this->stopProcessing = true;
                break;
            }
        }
    }
}

