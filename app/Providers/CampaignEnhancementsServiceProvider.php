<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Campaigns\CampaignDispatchController;
use App\Services\Campaigns\CampaignDispatchService;
use Illuminate\Support\ServiceProvider;
use Sendportal\Base\Http\Controllers\Campaigns\CampaignDispatchController as BaseCampaignDispatchController;
use Sendportal\Base\Services\Campaigns\CampaignDispatchService as BaseCampaignDispatchService;

class CampaignEnhancementsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BaseCampaignDispatchService::class, CampaignDispatchService::class);

        $this->app->bind(BaseCampaignDispatchController::class, CampaignDispatchController::class);
    }
}
