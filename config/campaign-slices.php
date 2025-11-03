<?php

declare(strict_types=1);

return [
    'default_size' => env('CAMPAIGN_SLICE_DEFAULT_SIZE') ? (int) env('CAMPAIGN_SLICE_DEFAULT_SIZE') : null,
    'interval_minutes' => (int) env('CAMPAIGN_SLICE_INTERVAL_MINUTES', 1440),
];

