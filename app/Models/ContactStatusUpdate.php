<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactStatusUpdate extends Model
{
    protected $table = 'contact_status_updates';

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];
}
