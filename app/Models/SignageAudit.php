<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignageAudit extends Model
{
    protected $table = 'signage_audits';
    protected $guarded = ['id'];
    protected $casts = [];
}
