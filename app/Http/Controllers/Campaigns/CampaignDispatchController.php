<?php

declare(strict_types=1);

namespace App\Http\Controllers\Campaigns;

use App\Http\Requests\CampaignDispatchRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Sendportal\Base\Facades\Sendportal;
use Sendportal\Base\Http\Controllers\Campaigns\CampaignDispatchController as BaseController;
use Sendportal\Base\Interfaces\QuotaServiceInterface;
use Sendportal\Base\Models\CampaignStatus;
use Sendportal\Base\Repositories\Campaigns\CampaignTenantRepositoryInterface;

class CampaignDispatchController extends BaseController
{
    public function __construct(
        CampaignTenantRepositoryInterface $campaigns,
        QuotaServiceInterface $quotaService
    ) {
        parent::__construct($campaigns, $quotaService);
    }

    public function send(CampaignDispatchRequest $request, int $id): RedirectResponse
    {
        $campaign = $this->campaigns->find(Sendportal::currentWorkspaceId(), $id, ['email_service', 'messages']);

        if ($campaign->status_id !== CampaignStatus::STATUS_DRAFT) {
            return redirect()->route('sendportal.campaigns.status', $id);
        }

        if (! $campaign->email_service_id) {
            return redirect()->route('sendportal.campaigns.edit', $id)
                ->withErrors(__('Please select an Email Service'));
        }

        $campaign->update([
            'send_to_all' => $request->get('recipients') === 'send_to_all',
        ]);

        $campaign->tags()->sync($request->get('tags'));

        if ($this->quotaService->exceedsQuota($campaign->email_service, $campaign->unsent_count)) {
            return redirect()->route('sendportal.campaigns.edit', $id)
                ->withErrors(__('The number of subscribers for this campaign exceeds your SES quota'));
        }

        $scheduledAt = $request->get('schedule') === 'scheduled'
            ? Carbon::parse($request->get('scheduled_at'))
            : now();

        $this->applySliceSettings($campaign, $request);

        $campaign->update([
            'scheduled_at' => $scheduledAt,
            'status_id' => CampaignStatus::STATUS_QUEUED,
            'save_as_draft' => $request->get('behaviour') === 'draft',
        ]);

        return redirect()->route('sendportal.campaigns.status', $id);
    }

    protected function applySliceSettings($campaign, CampaignDispatchRequest $request): void
    {
        $enabled = $request->boolean('slice_enabled');
        $defaultSize = config('campaign-slices.default_size');

        if (! $enabled) {
            $campaign->forceFill([
                'slice_enabled' => false,
                'slice_size' => null,
                'slice_interval_minutes' => null,
                'slice_offset' => 0,
                'slice_last_run_at' => null,
            ])->save();

            return;
        }

        $size = (int) ($request->input('slice_size') ?: $defaultSize);
        $interval = (int) ($request->input('slice_interval_minutes') ?: config('campaign-slices.interval_minutes'));

        if ($size < 1) {
            $size = $defaultSize ?: 0;
        }

        if ($size < 1) {
            // nothing to enable.
            $campaign->forceFill([
                'slice_enabled' => false,
                'slice_size' => null,
                'slice_interval_minutes' => null,
                'slice_offset' => 0,
                'slice_last_run_at' => null,
            ])->save();

            return;
        }

        $campaign->forceFill([
            'slice_enabled' => true,
            'slice_size' => $size,
            'slice_interval_minutes' => $interval,
            'slice_offset' => 0,
            'slice_last_run_at' => null,
        ])->save();
    }
}
