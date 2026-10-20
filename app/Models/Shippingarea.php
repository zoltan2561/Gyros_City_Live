<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shippingarea extends Model
{
    use HasFactory;
    protected $table = 'shipping_area';

    public function getCityNamesAttribute(): array
    {
        return \App\Services\DeliveryAreaNames::fromLabel((string) $this->name);
    }
}
