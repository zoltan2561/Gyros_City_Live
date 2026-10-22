<?php

namespace App\Support;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Routing\Route;

/** Shared server-side grants for routes, navigation and employee landing pages. */
final class AdminPermissions
{
    public static function registry(): array
    {
        return [
            0 => ['admin_ui.overview', 'home'], 1 => ['labels.orders', 'orders'],
            2 => ['admin_ui.report', 'report'], 3 => ['admin_ui.sliders', 'slider'],
            4 => ['admin_ui.banners', 'bannersection-1'], 5 => ['admin_ui.coupons', 'promocode'],
            6 => ['labels.notification', 'notification'], 7 => ['admin_ui.categories', 'category'],
            8 => ['admin_ui.subcategories', 'sub-category'], 9 => ['admin_ui.addon_groups', 'addongroup'],
            10 => ['admin_ui.items', 'item'], 11 => ['admin_ui.shipping', 'shippingarea'],
            12 => ['admin_ui.hours', 'time'], 13 => ['admin_ui.payment', 'payment'],
            14 => ['admin_access.featured_reviews', 'store-review'], 15 => ['labels.bookings', 'bookings'],
            16 => ['labels.inquiries', 'contact'], 17 => ['admin_ui.customers', 'users'],
            18 => ['admin_ui.drivers', 'driver'], 19 => ['admin_ui.roles', 'roles'],
            20 => ['admin_ui.employees', 'employee'], 21 => ['admin_ui.website_content', 'aboutus'],
            22 => ['admin_ui.settings', 'settings'], 23 => ['labels.addons_manager', 'systemaddons'],
            24 => ['labels.clear_cache', 'clear-cache'], 27 => ['admin_ui.language', 'language-settings'],
            29 => ['reviews.title', 'reviews'], 30 => ['admin_ui.tax', 'tax'],
            31 => ['admin_ui.global_extras', 'global_extras'],
            34 => ['newsletter.title', 'newsletter'],
        ];
    }

    public static function grantableIds(): array
    {
        return array_values(array_diff(array_keys(self::registry()), [19, 20, 23]));
    }

    public static function parseModules(?string $value): array
    {
        if ($value === null || $value === '') return [];
        $modules = [];
        foreach (explode(',', $value) as $part) {
            if (!preg_match('/^(0|[1-9][0-9]*)$/D', trim($part))) return [];
            $id = (int) trim($part);
            // Retain valid legacy grants when a role also names unavailable addons.
            if (!array_key_exists($id, self::registry()) && !in_array($id, [25, 26, 28, 32, 33], true)) return [];
            if (in_array($id, self::grantableIds(), true)) $modules[] = $id;
        }
        return array_values(array_unique($modules));
    }

    public static function isActive(?User $user): bool
    {
        if (!$user || !in_array((int) $user->type, [1, 4], true)
            || (int) $user->is_available !== 1 || (int) $user->is_deleted !== 2) return false;
        if ((int) $user->type === 1) return true;
        $role = Roles::find($user->role_id);
        $user->setRelation('permissionRole', $role);
        return $role && (int) $role->is_available === 1;
    }

    public static function modules(?User $user): array
    {
        if (!$user) return [];
        if ((int) $user->type === 1) return array_keys(self::registry());
        if ((int) $user->type !== 4 || (int) $user->is_available !== 1 || (int) $user->is_deleted !== 2) return [];
        $role = $user->relationLoaded('permissionRole') ? $user->getRelation('permissionRole') : Roles::find($user->role_id);
        return $role && (int) $role->is_available === 1 ? self::parseModules($role->modules) : [];
    }

    public static function can(?User $user, int $module): bool
    {
        return in_array($module, self::modules($user), true);
    }

    public static function landing(User $user): string
    {
        foreach (self::registry() as $id => $entry) {
            if ($id !== 24 && self::can($user, $id)) return '/admin/' . $entry[1];
        }
        return '/admin/profile';
    }

    public static function requirement(Route $route)
    {
        if ($route->getActionName() === 'Closure') return $route->uri() === 'admin/clear-cache' ? 24 : null;
        return self::actions()[ltrim($route->getActionName(), '\\')] ?? null;
    }

    public static function allowsRoute(User $user, Route $route): bool
    {
        if ((int) $user->type === 1) return true;
        $permission = self::requirement($route);
        return $permission === 'self' || (is_int($permission) && self::can($user, $permission));
    }

    private static function actions(): array
    {
        // Explicit controller actions: an added endpoint is denied until mapped.
        return [
            'App\\Http\\Controllers\\admin\\GiftPromotionController@index' => 'owner',
            'App\\Http\\Controllers\\admin\\GiftPromotionController@save' => 'owner',
            'App\\Http\\Controllers\\admin\\GiftPromotionController@destroy' => 'owner',
            'App\\Http\\Controllers\\admin\\ItemController@quickUpdate' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@toggleTodayUnavailable' => 10,
            'App\\Http\\Controllers\\admin\\SettingController@updateContent' => 21,
            'App\\Http\\Controllers\\admin\\NewsletterController@index' => 34,
            'App\\Http\\Controllers\\admin\\NewsletterController@create' => 34,
            'App\\Http\\Controllers\\admin\\NewsletterController@store' => 34,
            'App\\Http\\Controllers\\admin\\NewsletterController@edit' => 34,
            'App\\Http\\Controllers\\admin\\NewsletterController@update' => 34,
            'App\\Http\\Controllers\\admin\\NewsletterController@test' => 34,
            'App\\Http\\Controllers\\admin\\NewsletterController@send' => 34,
            'App\\Http\\Controllers\\admin\\NewsletterController@show' => 34,
            'App\\Http\\Controllers\\admin\\NewsletterController@process' => 34,
            'App\\Http\\Controllers\\admin\\SettingController@updateFirebase' => 6,
            'App\\Http\\Controllers\\addons\\BlogController@add' => 21,
            'App\\Http\\Controllers\\addons\\BlogController@blogs' => 21,
            'App\\Http\\Controllers\\addons\\BlogController@delete' => 21,
            'App\\Http\\Controllers\\addons\\BlogController@index' => 21,
            'App\\Http\\Controllers\\addons\\BlogController@reorder_blog' => 21,
            'App\\Http\\Controllers\\addons\\BlogController@show' => 21,
            'App\\Http\\Controllers\\addons\\BlogController@showblog' => 21,
            'App\\Http\\Controllers\\addons\\BlogController@store' => 21,
            'App\\Http\\Controllers\\addons\\BlogController@update' => 21,
            'App\\Http\\Controllers\\addons\\LanguageController@add' => 27,
            'App\\Http\\Controllers\\addons\\LanguageController@status' => 27,
            'App\\Http\\Controllers\\addons\\LanguageController@store' => 27,
            'App\\Http\\Controllers\\addons\\PromocodeController@add' => 5,
            'App\\Http\\Controllers\\addons\\PromocodeController@destroy' => 5,
            'App\\Http\\Controllers\\addons\\PromocodeController@index' => 5,
            'App\\Http\\Controllers\\addons\\PromocodeController@reorder_promocode' => 5,
            'App\\Http\\Controllers\\addons\\PromocodeController@show' => 5,
            'App\\Http\\Controllers\\addons\\PromocodeController@status' => 5,
            'App\\Http\\Controllers\\addons\\PromocodeController@store' => 5,
            'App\\Http\\Controllers\\addons\\PromocodeController@update' => 5,
            'App\\Http\\Controllers\\addons\\QuickCallController@quick_call' => 22,
            'App\\Http\\Controllers\\addons\\RattingController@destroy' => 29,
            'App\\Http\\Controllers\\addons\\RattingController@index' => 29,
            'App\\Http\\Controllers\\addons\\RattingController@settings_update' => 'owner',
            'App\\Http\\Controllers\\addons\\RattingController@status' => 29,
            'App\\Http\\Controllers\\addons\\RolesController@add' => 'owner',
            'App\\Http\\Controllers\\addons\\RolesController@index' => 'owner',
            'App\\Http\\Controllers\\addons\\RolesController@show' => 'owner',
            'App\\Http\\Controllers\\addons\\RolesController@status' => 'owner',
            'App\\Http\\Controllers\\addons\\RolesController@store' => 'owner',
            'App\\Http\\Controllers\\addons\\RolesController@update' => 'owner',
            'App\\Http\\Controllers\\addons\\StoreReviewController@add' => 14,
            'App\\Http\\Controllers\\addons\\StoreReviewController@destroy' => 14,
            'App\\Http\\Controllers\\addons\\StoreReviewController@index' => 14,
            'App\\Http\\Controllers\\addons\\StoreReviewController@reorder_ratting' => 14,
            'App\\Http\\Controllers\\addons\\StoreReviewController@show' => 14,
            'App\\Http\\Controllers\\addons\\StoreReviewController@store' => 14,
            'App\\Http\\Controllers\\addons\\StoreReviewController@update' => 14,
            'App\\Http\\Controllers\\admin\\AddonsController@add' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@add_addons_group' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@addons_group_index' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@delete' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@delete_addons_group' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@index' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@reorder_addongroup' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@reorder_addons' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@show' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@show_addons_group' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@status' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@status_addons_group' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@store' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@store_addons_group' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@update' => 9,
            'App\\Http\\Controllers\\admin\\AddonsController@update_addons_group' => 9,
            'App\\Http\\Controllers\\admin\\AdminController@changepassword' => 'self',
            'App\\Http\\Controllers\\admin\\AdminController@changestatus' => 1,
            'App\\Http\\Controllers\\admin\\AdminController@editprofile' => 'self',
            'App\\Http\\Controllers\\admin\\AdminController@getorder' => 1,
            'App\\Http\\Controllers\\admin\\AdminController@home' => 0,
            'App\\Http\\Controllers\\admin\\AdminController@profile' => 'self',
            'App\\Http\\Controllers\\admin\\BannerController@add' => 4,
            'App\\Http\\Controllers\\admin\\BannerController@destroy' => 4,
            'App\\Http\\Controllers\\admin\\BannerController@index' => 4,
            'App\\Http\\Controllers\\admin\\BannerController@reorder_banner' => 4,
            'App\\Http\\Controllers\\admin\\BannerController@show' => 4,
            'App\\Http\\Controllers\\admin\\BannerController@status' => 4,
            'App\\Http\\Controllers\\admin\\BannerController@store' => 4,
            'App\\Http\\Controllers\\admin\\BannerController@update' => 4,
            'App\\Http\\Controllers\\admin\\BookingsController@bookings' => 15,
            'App\\Http\\Controllers\\admin\\BookingsController@bookingstatus' => 15,
            'App\\Http\\Controllers\\admin\\CMSPagesController@aboutus' => 21,
            'App\\Http\\Controllers\\admin\\CMSPagesController@aboutus_update' => 21,
            'App\\Http\\Controllers\\admin\\CMSPagesController@privacypolicy' => 21,
            'App\\Http\\Controllers\\admin\\CMSPagesController@privacypolicy_update' => 21,
            'App\\Http\\Controllers\\admin\\CMSPagesController@refundpolicy' => 21,
            'App\\Http\\Controllers\\admin\\CMSPagesController@refundpolicy_update' => 21,
            'App\\Http\\Controllers\\admin\\CMSPagesController@termscondition' => 21,
            'App\\Http\\Controllers\\admin\\CMSPagesController@termscondition_update' => 21,
            'App\\Http\\Controllers\\admin\\CategoryController@add' => 7,
            'App\\Http\\Controllers\\admin\\CategoryController@delete' => 7,
            'App\\Http\\Controllers\\admin\\CategoryController@index' => 7,
            'App\\Http\\Controllers\\admin\\CategoryController@reorder_category' => 7,
            'App\\Http\\Controllers\\admin\\CategoryController@reorder_subcategory' => 8,
            'App\\Http\\Controllers\\admin\\CategoryController@show' => 7,
            'App\\Http\\Controllers\\admin\\CategoryController@status' => 7,
            'App\\Http\\Controllers\\admin\\CategoryController@store' => 7,
            'App\\Http\\Controllers\\admin\\CategoryController@subcategory_add' => 8,
            'App\\Http\\Controllers\\admin\\CategoryController@subcategory_delete' => 8,
            'App\\Http\\Controllers\\admin\\CategoryController@subcategory_index' => 8,
            'App\\Http\\Controllers\\admin\\CategoryController@subcategory_show' => 8,
            'App\\Http\\Controllers\\admin\\CategoryController@subcategory_status' => 8,
            'App\\Http\\Controllers\\admin\\CategoryController@subcategory_store' => 8,
            'App\\Http\\Controllers\\admin\\CategoryController@subcategory_update' => 8,
            'App\\Http\\Controllers\\admin\\CategoryController@update' => 7,
            'App\\Http\\Controllers\\admin\\ContactController@destroy' => 16,
            'App\\Http\\Controllers\\admin\\ContactController@index' => 16,
            'App\\Http\\Controllers\\admin\\DriverController@add' => 18,
            'App\\Http\\Controllers\\admin\\DriverController@driverdetails' => 18,
            'App\\Http\\Controllers\\admin\\DriverController@index' => 18,
            'App\\Http\\Controllers\\admin\\DriverController@show' => 18,
            'App\\Http\\Controllers\\admin\\DriverController@status' => 18,
            'App\\Http\\Controllers\\admin\\DriverController@store' => 18,
            'App\\Http\\Controllers\\admin\\DriverController@update' => 18,
            'App\\Http\\Controllers\\admin\\GlobalExtrasController@add' => 31,
            'App\\Http\\Controllers\\admin\\GlobalExtrasController@change_status' => 31,
            'App\\Http\\Controllers\\admin\\GlobalExtrasController@delete' => 31,
            'App\\Http\\Controllers\\admin\\GlobalExtrasController@edit' => 31,
            'App\\Http\\Controllers\\admin\\GlobalExtrasController@index' => 31,
            'App\\Http\\Controllers\\admin\\GlobalExtrasController@reorder_global' => 31,
            'App\\Http\\Controllers\\admin\\GlobalExtrasController@store' => 31,
            'App\\Http\\Controllers\\admin\\GlobalExtrasController@update' => 31,
            'App\\Http\\Controllers\\admin\\ItemController@additem' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@delete' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@deleteextras' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@destroyimage' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@edititem' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@featured' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@getextras' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@index' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@list' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@reorder_item' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@showimage' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@status' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@store' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@storeimages' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@subcategories' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@update' => 10,
            'App\\Http\\Controllers\\admin\\ItemController@updateimage' => 10,
            'App\\Http\\Controllers\\admin\\LangController@delete' => 27,
            'App\\Http\\Controllers\\admin\\LangController@edit' => 27,
            'App\\Http\\Controllers\\admin\\LangController@index' => 27,
            'App\\Http\\Controllers\\admin\\LangController@language' => 27,
            'App\\Http\\Controllers\\admin\\LangController@layout' => 27,
            'App\\Http\\Controllers\\admin\\LangController@storeLanguageData' => 27,
            'App\\Http\\Controllers\\admin\\LangController@update' => 27,
            'App\\Http\\Controllers\\admin\\NotificationController@add' => 6,
            'App\\Http\\Controllers\\admin\\NotificationController@index' => 6,
            'App\\Http\\Controllers\\admin\\NotificationController@store' => 6,
            'App\\Http\\Controllers\\admin\\NotificationController@unprocessedAlert' => 1,
            'App\\Http\\Controllers\\admin\\OrderController@assign_driver' => 1,
            'App\\Http\\Controllers\\admin\\OrderController@customerbillinfo' => 1,
            'App\\Http\\Controllers\\admin\\OrderController@generatepdf' => 1,
            'App\\Http\\Controllers\\admin\\OrderController@get_reports' => 2,
            'App\\Http\\Controllers\\admin\\OrderController@index' => 1,
            'App\\Http\\Controllers\\admin\\OrderController@invoice' => 1,
            'App\\Http\\Controllers\\admin\\OrderController@order_note' => 1,
            'App\\Http\\Controllers\\admin\\OrderController@payment_status' => 1,
            'App\\Http\\Controllers\\admin\\OrderController@print' => 1,
            'App\\Http\\Controllers\\admin\\OrderController@update' => 1,
            'App\\Http\\Controllers\\admin\\OtherPagesController@faq_add' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@faq_delete' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@faq_index' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@faq_show' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@faq_store' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@faq_update' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@gallery_add' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@gallery_delete' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@gallery_index' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@gallery_show' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@gallery_store' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@gallery_update' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@our_team_add' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@our_team_delete' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@our_team_index' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@our_team_show' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@our_team_store' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@our_team_update' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@reorder_faq' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@reorder_our_team' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@subscribe' => 16,
            'App\\Http\\Controllers\\admin\\OtherPagesController@tutorial_add' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@tutorial_delete' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@tutorial_index' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@tutorial_show' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@tutorial_store' => 21,
            'App\\Http\\Controllers\\admin\\OtherPagesController@tutorial_update' => 21,
            'App\\Http\\Controllers\\admin\\PaymentController@index' => 13,
            'App\\Http\\Controllers\\admin\\PaymentController@reorder_payment' => 13,
            'App\\Http\\Controllers\\admin\\PaymentController@update' => 13,
            'App\\Http\\Controllers\\admin\\SettingController@delete_feature' => 22,
            'App\\Http\\Controllers\\admin\\SettingController@delete_social_link' => 22,
            'App\\Http\\Controllers\\admin\\SettingController@index' => 22,
            'App\\Http\\Controllers\\admin\\SettingController@settings_update' => 22,
            'App\\Http\\Controllers\\admin\\SettingController@toggleDelivery' => 1,
            'App\\Http\\Controllers\\admin\\ShippingareaController@Edit' => 11,
            'App\\Http\\Controllers\\admin\\ShippingareaController@add' => 11,
            'App\\Http\\Controllers\\admin\\ShippingareaController@delete' => 11,
            'App\\Http\\Controllers\\admin\\ShippingareaController@index' => 11,
            'App\\Http\\Controllers\\admin\\ShippingareaController@reorder_shippingarea' => 11,
            'App\\Http\\Controllers\\admin\\ShippingareaController@store' => 11,
            'App\\Http\\Controllers\\admin\\ShippingareaController@update' => 11,
            'App\\Http\\Controllers\\admin\\SliderController@add' => 3,
            'App\\Http\\Controllers\\admin\\SliderController@destroy' => 3,
            'App\\Http\\Controllers\\admin\\SliderController@index' => 3,
            'App\\Http\\Controllers\\admin\\SliderController@list' => 3,
            'App\\Http\\Controllers\\admin\\SliderController@reorder_slider' => 3,
            'App\\Http\\Controllers\\admin\\SliderController@show' => 3,
            'App\\Http\\Controllers\\admin\\SliderController@status' => 3,
            'App\\Http\\Controllers\\admin\\SliderController@store' => 3,
            'App\\Http\\Controllers\\admin\\SliderController@update' => 3,
            'App\\Http\\Controllers\\admin\\SystemAddonsController@createsystemaddons' => 'owner',
            'App\\Http\\Controllers\\admin\\SystemAddonsController@index' => 'owner',
            'App\\Http\\Controllers\\admin\\SystemAddonsController@store' => 'owner',
            'App\\Http\\Controllers\\admin\\SystemAddonsController@update' => 'owner',
            'App\\Http\\Controllers\\admin\\TaxController@add' => 30,
            'App\\Http\\Controllers\\admin\\TaxController@delete' => 30,
            'App\\Http\\Controllers\\admin\\TaxController@index' => 30,
            'App\\Http\\Controllers\\admin\\TaxController@reorder_tax' => 30,
            'App\\Http\\Controllers\\admin\\TaxController@show' => 30,
            'App\\Http\\Controllers\\admin\\TaxController@status' => 30,
            'App\\Http\\Controllers\\admin\\TaxController@store' => 30,
            'App\\Http\\Controllers\\admin\\TaxController@update' => 30,
            'App\\Http\\Controllers\\admin\\TimeController@index' => 12,
            'App\\Http\\Controllers\\admin\\TimeController@store' => 12,
            'App\\Http\\Controllers\\admin\\UserController@add_customers' => 17,
            'App\\Http\\Controllers\\admin\\UserController@add_deduct' => 17,
            'App\\Http\\Controllers\\admin\\UserController@add_employee' => 'owner',
            'App\\Http\\Controllers\\admin\\UserController@delete' => 17,
            'App\\Http\\Controllers\\admin\\UserController@employee' => 'owner',
            'App\\Http\\Controllers\\admin\\UserController@index' => 17,
            'App\\Http\\Controllers\\admin\\UserController@show_customers' => 17,
            'App\\Http\\Controllers\\admin\\UserController@show_employee' => 'owner',
            'App\\Http\\Controllers\\admin\\UserController@status' => 17,
            'App\\Http\\Controllers\\admin\\UserController@status_employee' => 'owner',
            'App\\Http\\Controllers\\admin\\UserController@store_customers' => 17,
            'App\\Http\\Controllers\\admin\\UserController@store_employee' => 'owner',
            'App\\Http\\Controllers\\admin\\UserController@update_customers' => 17,
            'App\\Http\\Controllers\\admin\\UserController@update_employee' => 'owner',
            'App\\Http\\Controllers\\admin\\UserController@userdetails' => 17,
            'App\\Http\\Controllers\\admin\\WhyChooseUsController@add' => 21,
            'App\\Http\\Controllers\\admin\\WhyChooseUsController@delete' => 21,
            'App\\Http\\Controllers\\admin\\WhyChooseUsController@index' => 21,
            'App\\Http\\Controllers\\admin\\WhyChooseUsController@reorder_choose_us' => 21,
            'App\\Http\\Controllers\\admin\\WhyChooseUsController@show' => 21,
            'App\\Http\\Controllers\\admin\\WhyChooseUsController@store' => 21,
            'App\\Http\\Controllers\\admin\\WhyChooseUsController@update' => 21,
        ];
    }
}
