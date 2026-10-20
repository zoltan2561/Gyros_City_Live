<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    protected $fillable = ['subject', 'body', 'created_by'];
    protected $casts = ['started_at' => 'datetime'];

    public function deliveries()
    {
        return $this->hasMany(NewsletterDelivery::class);
    }

    public function progress(): array
    {
        $counts = $this->deliveries()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $progress = [];
        foreach (['pending', 'sending', 'sent', 'failed', 'skipped'] as $status) {
            $progress[$status] = (int) ($counts[$status] ?? 0);
        }
        $progress['total'] = array_sum($progress);
        $progress['finished'] = $progress['pending'] === 0 && $progress['sending'] === 0;
        return $progress;
    }
}
