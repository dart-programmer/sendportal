<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use App\Pipelines\Campaigns\CompleteCampaignWithSlicing;
use App\Pipelines\Campaigns\CreateSlicedMessages;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Pipelines\Campaigns\CompleteCampaign;
use Sendportal\Base\Pipelines\Campaigns\CreateMessages;
use Sendportal\Base\Pipelines\Campaigns\StartCampaign;
use Sendportal\Base\Services\Campaigns\CampaignDispatchService as BaseService;

class CampaignDispatchService extends BaseService
{
    public function handle(Campaign $campaign)
    {
        if (! $campaign = $this->findCampaign($campaign->id)) {
            return;
        }

        if (! $campaign->queued) {
            Log::error('Campaign does not have a queued status campaign_id=' . $campaign->id . ' status_id=' . $campaign->status_id);

            return;
        }

        $pipes = $this->pipesFor($campaign);

        try {
            app(Pipeline::class)
                ->send($campaign)
                ->through($pipes)
                ->then(static function ($campaign) {
                    return $campaign;
                });
        } catch (\Exception $exception) {
            Log::error('Error dispatching campaign id=' . $campaign->id . ' exception=' . $exception->getMessage() . ' trace=' . $exception->getTraceAsString());
        }
    }

    protected function findCampaign(int $id): ?Campaign
    {
        return Campaign::with('tags')->find($id);
    }

    protected function pipesFor(Campaign $campaign): array
    {
        if ($this->slicingEnabled($campaign)) {
            return [
                StartCampaign::class,
                CreateSlicedMessages::class,
                CompleteCampaignWithSlicing::class,
            ];
        }

        return [
            StartCampaign::class,
            CreateMessages::class,
            CompleteCampaign::class,
        ];
    }

    protected function slicingEnabled(Campaign $campaign): bool
    {
        if ($campaign->slice_enabled && $campaign->slice_size) {
            return true;
        }

        $defaultSize = config('campaign-slices.default_size');

        if ($defaultSize) {
            $campaign->slice_enabled = true;
            $campaign->slice_size = $campaign->slice_size ?: $defaultSize;
            $campaign->slice_interval_minutes = $campaign->slice_interval_minutes ?: config('campaign-slices.interval_minutes');
            $campaign->save();

            return true;
        }

        return false;
    }
}

