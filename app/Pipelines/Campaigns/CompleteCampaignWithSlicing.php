<?php

declare(strict_types=1);

namespace App\Pipelines\Campaigns;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;
use Sendportal\Base\Pipelines\Campaigns\CompleteCampaign;

class CompleteCampaignWithSlicing extends CompleteCampaign
{
    public function handle(Campaign $campaign, $next)
    {
        if (! $this->slicingEnabled($campaign)) {
            return parent::handle($campaign, $next);
        }

        $processed = (int) ($campaign->slice_processed ?? 0);
        $campaign->slice_offset = (int) ($campaign->slice_offset ?? 0) + $processed;
        $campaign->slice_last_run_at = Carbon::now();

        $total = $campaign->active_subscriber_count;
        $campaign->slice_offset = min($campaign->slice_offset, $total);
        $remaining = max(0, $total - $campaign->slice_offset);

        if ($remaining > 0 && $processed > 0) {
            $campaign->status_id = CampaignStatus::STATUS_QUEUED;
            $campaign->scheduled_at = Carbon::now()->addMinutes($this->interval($campaign));
            $campaign->save();

            Log::info('Campaign queued for next slice', [
                'campaign_id' => $campaign->id,
                'slice_offset' => $campaign->slice_offset,
                'remaining' => $remaining,
                'next_run_at' => $campaign->scheduled_at,
            ]);

            return $next($campaign);
        }

        $campaign->status_id = CampaignStatus::STATUS_SENT;
        $campaign->save();

        Log::info('Campaign slicing completed', [
            'campaign_id' => $campaign->id,
            'total_processed' => $campaign->slice_offset,
        ]);

        return $next($campaign);
    }

    protected function slicingEnabled(Campaign $campaign): bool
    {
        return (bool) ($campaign->slice_enabled && $campaign->slice_size);
    }

    protected function remainingSubscribers(Campaign $campaign): int
    {
        $total = $campaign->active_subscriber_count;

        return max(0, $total - (int) ($campaign->slice_offset ?? 0));
    }

    protected function interval(Campaign $campaign): int
    {
        return (int) ($campaign->slice_interval_minutes ?? config('campaign-slices.interval_minutes'));
    }
}

