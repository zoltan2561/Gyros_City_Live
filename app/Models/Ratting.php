<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class Ratting extends Model

{
    protected $table = 'ratting';

    protected $fillable = ['user_id', 'item_id', 'ratting', 'comment', 'name', 'image', 'status'];

    public function scopeCustomerReviews($query)
    {
        return $query->whereNull('name')->whereHas('user_info', function ($user) {
            $user->where('type', 2);
        });
    }

    public function scopeFeaturedTestimonials($query)
    {
        return $query->whereNull('item_id')->whereNotNull('name');
    }

    public function scopePublicReviews($query, $kind = null)
    {
        $query->customerReviews()->where('status', 1);
        if (!\App\Helpers\helper::reviews_enabled($kind)) {
            return $query->whereRaw('1 = 0');
        }
        if ($kind === 'product_review') {
            return $query->whereNotNull('item_id');
        }
        if ($kind === 'store_review') {
            return $query->whereNull('item_id');
        }
        return $query->where(function ($types) {
            if (\App\Helpers\helper::reviews_enabled('product_review')) $types->orWhereNotNull('item_id');
            if (\App\Helpers\helper::reviews_enabled('store_review')) $types->orWhereNull('item_id');
        });
    }

    protected static function booted()
    {
        static::saved(function ($review) { $review->refreshItemAverage(); });
        static::deleted(function ($review) { $review->refreshItemAverage(); });
    }

    public function refreshItemAverage(): void
    {
        \App\Helpers\helper::forget_review_averages();
        if ($this->item_id) {
            Item::whereKey($this->item_id)->update(['avg_ratting' => (float) static::customerReviews()
                ->where('item_id', $this->item_id)->where('status', 1)->avg('ratting')]);
        }
    }

    public function user_info()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id')->select('id', 'name', 'email', 'mobile', 'token', DB::raw("CONCAT('" . url(env('ASSETSPATHURL') . 'admin-assets/images/profile/') . "/', users.profile_image) AS profile_image"));
    }

    public function item_info()
    {
        return $this->hasOne('App\Models\Item', 'id', 'item_id')->select('id', 'item_name');
    }
}
