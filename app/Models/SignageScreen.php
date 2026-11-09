<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignageScreen extends Model
{
    protected $table = 'signage_screens';
    protected $guarded = ['id'];
    protected $casts = ['status' => 'array', 'last_seen_at' => 'datetime', 'delivered_release_ids' => 'array', 'offline_price_seconds'=>'integer'];
    protected $hidden = ['token_hash'];
}
