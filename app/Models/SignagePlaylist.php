<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignagePlaylist extends Model
{
    protected $table = 'signage_playlists';
    protected $guarded = ['id'];
    protected $casts = ['scene_ids' => 'array'];

    public function editVersion(): string
    {
        return hash('sha256', json_encode([$this->name, $this->scene_ids]));
    }
}
