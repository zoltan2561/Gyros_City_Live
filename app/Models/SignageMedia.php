<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignageMedia extends Model
{
    protected $table = 'signage_media';
    protected $guarded = ['id'];
    protected $casts = ['price_snapshot' => 'array', 'price_valid_until' => 'datetime', 'baked_prices' => 'boolean'];
}
