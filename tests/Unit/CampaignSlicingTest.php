<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Pipelines\Campaigns\CompleteCampaignWithSlicing;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;

class CampaignSlicingTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    /** @test */
    public function it_requeues_campaign_when_more_slices_remain(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 11, 3, 10, 0, 0));

        $campaign = $this->makeCampaign([
            'slice_enabled' => true,
            'slice_size' => 150,
            'slice_offset' => 0,
            'slice_interval_minutes' => 1440,
            'status_id' => CampaignStatus::STATUS_SENDING,
            'active_subscriber_count' => 300,
        ]);
        $campaign->slice_processed = 150;

        (new CompleteCampaignWithSlicing())->handle($campaign, static fn ($payload) => $payload);

        self::assertSame(CampaignStatus::STATUS_QUEUED, $campaign->status_id);
        self::assertSame(150, $campaign->slice_offset);
        self::assertInstanceOf(Carbon::class, $campaign->scheduled_at);
        self::assertTrue($campaign->scheduled_at->eq(Carbon::now()->addMinutes(1440)));
        self::assertInstanceOf(Carbon::class, $campaign->slice_last_run_at);
    }

    /** @test */
    public function it_marks_campaign_sent_when_final_slice_processed(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 11, 3, 10, 0, 0));

        $campaign = $this->makeCampaign([
            'slice_enabled' => true,
            'slice_size' => 150,
            'slice_offset' => 150,
            'slice_interval_minutes' => 1440,
            'status_id' => CampaignStatus::STATUS_SENDING,
            'active_subscriber_count' => 300,
        ]);
        $campaign->slice_processed = 150;

        (new CompleteCampaignWithSlicing())->handle($campaign, static fn ($payload) => $payload);

        self::assertSame(CampaignStatus::STATUS_SENT, $campaign->status_id);
        self::assertSame(300, $campaign->slice_offset);
        self::assertNull($campaign->scheduled_at);
        self::assertInstanceOf(Carbon::class, $campaign->slice_last_run_at);
    }

    private function makeCampaign(array $attributes): Campaign
    {
        $campaign = new class extends Campaign
        {
            public function save(array $options = []): bool
            {
                return true;
            }

            public function getActiveSubscriberCountAttribute(): int
            {
                return $this->attributes['active_subscriber_count'] ?? 0;
            }
        };

        $campaign->forceFill($attributes);

        return $campaign;
    }
}

