<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignagePlaylist extends Model
{
    protected $table = 'signage_playlists';
    protected $guarded = ['id'];
    protected $casts = ['scene_ids' => 'array'];
}
