<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignageScene extends Model
{
    protected $table = 'signage_scenes';
    protected $guarded = ['id'];
    protected $casts = ['content' => 'array'];

    public function editVersion(): string
    {
        return hash('sha256', json_encode([$this->name, $this->content]));
    }
}
