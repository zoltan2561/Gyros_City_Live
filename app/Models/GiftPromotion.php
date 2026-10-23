<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftPromotion extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'trigger_item_ids' => 'array', 'gift_item_ids' => 'array',
        'repeat' => 'boolean', 'per_item' => 'boolean', 'active' => 'boolean',
        'required_qty' => 'integer', 'gift_qty' => 'integer',
        'starts_at' => 'date', 'ends_at' => 'date',
    ];
}
