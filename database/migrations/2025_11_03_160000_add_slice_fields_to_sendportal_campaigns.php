<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('sendportal_campaigns', function (Blueprint $table) {
            $table->boolean('slice_enabled')->default(false);
            $table->unsignedInteger('slice_size')->nullable();
            $table->unsignedInteger('slice_interval_minutes')->nullable();
            $table->unsignedInteger('slice_offset')->default(0);
            $table->timestamp('slice_last_run_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sendportal_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'slice_enabled',
                'slice_size',
                'slice_interval_minutes',
                'slice_offset',
                'slice_last_run_at',
            ]);
        });
    }
};
