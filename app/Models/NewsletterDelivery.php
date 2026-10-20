<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterDelivery extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['token', 'email'];
    protected $casts = ['sent_at' => 'datetime'];

    public function newsletter()
    {
        return $this->belongsTo(Newsletter::class);
    }
}
