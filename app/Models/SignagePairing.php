<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignagePairing extends Model
{
    protected $table = 'signage_pairings';
    protected $guarded = ['id'];
    protected $casts = ['expires_at' => 'datetime'];
    protected $hidden = ['nonce_hash', 'code_hash', 'delivery'];
}
