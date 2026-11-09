<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignageRelease extends Model
{
    protected $table = 'signage_releases';
    protected $guarded = ['id'];
    protected $casts = ['snapshot' => 'array'];
}
