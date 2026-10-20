<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Item extends Model
{
    public function getIsOrderableAttribute(): bool
    {
        return (int) $this->item_status === 1 && !(bool) $this->today_unavailable;
    }

    public function scopeVisibleOnMenu($query)
    {
        // Items are physically deleted by the existing admin. Status 0/2 means unavailable.
        return $query->whereHas('category_info', function ($category) {
            $category->where('is_available', 1)->where('is_deleted', 2);
        });
    }

    protected $table = 'item';
    protected $fillable = ['cat_id', 'subcat_id', 'item_name', 'slug', 'image', 'item_type', 'has_variation', 'attribute', 'price', 'original_price', 'addons_id', 'item_description', 'preparation_time', 'tax', 'avg_ratting', 'discount_percentage', 'item_status', 'is_featured', 'is_deleted', 'delivery_time'];

    public function subcategory_info()
    {
        return $this->hasOne('App\Models\Subcategory', 'id', 'subcat_id')->select('subcategories.id', 'subcategories.subcategory_name', 'subcategories.slug');
    }
    public function category_info()
    {
        return $this->hasOne('App\Models\Category', 'id', 'cat_id')->select('categories.id', 'categories.category_name', 'categories.slug', DB::raw("CONCAT('" . url(env('ASSETSPATHURL') . 'admin-assets/images/category/') . "/', image) AS image_url"));
    }
    public function item_image()
    {
        return $this->hasOne('App\Models\ItemImages', 'item_id', 'id')->select('item_images.id', 'item_images.image as image_name', 'item_images.item_id', DB::raw("CONCAT('" . url(env('ASSETSPATHURL') . 'admin-assets/images/item/') . "/', item_images.image) AS image_url"));
    }
    public function item_images()
    {
        return $this->hasMany('App\Models\ItemImages', 'item_id', 'id')->select('item_images.id', 'item_images.image as image_name', 'item_images.item_id', DB::raw("CONCAT('" . url(env('ASSETSPATHURL') . 'admin-assets/images/item/') . "/', item_images.image) AS image_url"));
    }
    public function extras()
    {
        return $this->hasMany('App\Models\Extra', 'item_id', 'id')->select('id', 'name', 'price', 'item_id');
    }
}
