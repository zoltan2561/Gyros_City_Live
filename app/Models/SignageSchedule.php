<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignageSchedule extends Model
{
    protected $table = 'signage_schedules';
    protected $guarded = ['id'];
    protected $casts = ['days' => 'array'];
}
