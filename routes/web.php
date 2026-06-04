<?php
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\ItemController;
use App\Http\Controllers\admin\CategoryController;
use App\Http\Controllers\admin\ContactController;
use App\Http\Controllers\admin\SliderController;
use App\Http\Controllers\admin\AddonsController;
use App\Http\Controllers\admin\DriverController;
use App\Http\Controllers\admin\BannerController;
use App\Http\Controllers\admin\UserController;
use App\Http\Controllers\admin\SettingController;
use App\Http\Controllers\admin\OrderController;
use App\Http\Controllers\admin\PaymentController;
use App\Http\Controllers\admin\CMSPagesController;
use App\Http\Controllers\admin\NotificationController;
use App\Http\Controllers\admin\SystemAddonsController;
use App\Http\Controllers\admin\TimeController;
use App\Http\Controllers\admin\OtherPagesController;
use App\Http\Controllers\admin\BookingsController;
use App\Http\Controllers\admin\GlobalExtrasController;
use App\Http\Controllers\front\UserController as WebUserController;
use App\Http\Controllers\front\ItemController as WebItemController;
use App\Http\Controllers\front\OrderController as WebOrderController;
use App\Http\Controllers\front\PromocodeController as WebPromocodeController;
use App\Http\Controllers\front\OtherPagesController as WebOtherPagesController;
use App\Http\Controllers\front\BookingsController as WebBookingsController;
use App\Http\Controllers\front\HomeController;
use App\Http\Controllers\front\MenuController;
use App\Http\Controllers\front\FavoriteController;
use App\Http\Controllers\front\CartController;
use App\Http\Controllers\front\CheckoutController;
use App\Http\Controllers\front\AddressController;
use App\Http\Controllers\front\WalletController;
use App\Http\Controllers\admin\LangController;
use App\Http\Controllers\admin\ShippingareaController;
use App\Http\Controllers\admin\TaxController;
use App\Http\Controllers\Admin\WhyChooseUsController;
use App\Http\Controllers\BarionController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These routes are loaded by the RouteServiceProvider within a group which contains the "web" middleware group. Now create something great!
|
*/
// language


// Beépített reset route-ok
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

//noti email alert cucc
Route::middleware(['auth']) //
->post('admin/order-unprocessed-alert', [NotificationController::class, 'unprocessedAlert'])
    ->name('admin.unprocessed.order.alert')
	->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
	->withoutMiddleware(['auth']);
//Delivery hivasa

Route::post('/admin/toggle-delivery', [SettingController::class, 'toggleDelivery'])
    ->name('admin.toggleDelivery')
    ->middleware(['auth']); // <-- 'admin' törölve



// Régi URL alias (opció A: közvetlenül ugyanarra a metódusra)
Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('forgot-password');

// (opció B: csak átirányít)
Route::get('forgot-password', function () {
    return redirect()->route('password.request');
});


//Barion
Route::prefix('barion')->group(function () {
    Route::post('/indit',  [BarionController::class,'start'])->name('barion.start');
    Route::get('/utan',    [BarionController::class,'after'])->name('barion.after');
    Route::match(['GET','POST'],'/callback', [BarionController::class,'callback'])->name('barion.callback');
});








Route::get('/language-{lang}', [LangController::class, 'change'])->name('language');
Route::post('add-on/session/save', [AdminController::class, 'sessionsave']);
Route::group(['namespace' => 'front', 'middleware' => 'MaintenanceMiddleware'], function () {
	// home
	Route::get('/', [HomeController::class, 'index'])->name('home');
	Route::get('/direction', [HomeController::class, 'change_dir'])->name('change_dir');
	Route::get('/categories', [HomeController::class, 'categories'])->name('categories');
	// item
	Route::get('/menu', [MenuController::class, 'index'])->name('menu');
	Route::get('/show-item', [WebItemController::class, 'showitem']);
	Route::get('/item-{slug}', [WebItemController::class, 'itemdetails'])->name('itemdetails');
	Route::get('/search', [WebItemController::class, 'search'])->name('search');
	Route::get('/view-all', [WebItemController::class, 'viewall'])->name('viewall');
	Route::get('/get-item-allergens', [WebItemController::class, 'getitemallergens'])->name('get_item_allergens');
	// otherpages
	Route::get('/abous-us', [WebOtherPagesController::class, 'aboutus'])->name('about-us');
	Route::get('/privacy-policy', [WebOtherPagesController::class, 'privacypolicy'])->name('privacy-policy');
	Route::get('/refund-policy', [WebOtherPagesController::class, 'refundpolicy'])->name('refund-policy');
	Route::get('/gallery', [WebOtherPagesController::class, 'gallery'])->name('gallery');
	Route::get('/terms-conditions', [WebOtherPagesController::class, 'termsconditions'])->name('terms-conditions');
	Route::get('/faq', [WebOtherPagesController::class, 'faq'])->name('faq');
	Route::get('/contactus', [WebOtherPagesController::class, 'contactindex'])->name('contact-us');
	Route::post('/contactus/store', [WebOtherPagesController::class, 'contactstore'])->name('createcontact');
	Route::get('/testimonials', [WebOtherPagesController::class, 'testimonials'])->name('testimonials');
	Route::get('/ourteam', [WebOtherPagesController::class, 'ourteam'])->name('ourteam');
	// reservation
	Route::post('/reservation/store', [WebBookingsController::class, 'store']);
    // routes/web.php
    Route::post('/cart/add', [\App\Http\Controllers\front\CartController::class, 'addtocart'])->middleware('web');

    //subscribe
	Route::post('/subscribe', [WebOtherPagesController::class, 'subscribe'])->name('subscribe');

    Route::group(['middleware' => 'NoUserAuthMiddleware'], function () {
        // auth
        Route::get('/register', [WebUserController::class, 'register'])->name('register');

        // ide a rate limit → 3 próbálkozás / perc / IP
        Route::post('/adduser', [WebUserController::class, 'create'])
            ->middleware('throttle:3,1')
            ->name('adduser');

        Route::get('/verification', [WebUserController::class, 'verification'])->name('verification');
        Route::post('/verify-otp', [WebUserController::class, 'verifyotp'])->name('verifyotp');
        Route::get('/resend-otp', [WebUserController::class, 'resendotp']);
        //Route::get('/forgot-password', [WebUserController::class, 'forgotpassword'])->name('forgot-password');
        //Route::post('/send-pass', [WebUserController::class, 'sendpass'])->name('sendpass');
        Route::get('/login', [WebUserController::class, 'login'])->name('login');
        Route::post('/checklogin', [WebUserController::class, 'checklogin']);
    });



    // cart
	Route::get('/cart', [CartController::class, 'index'])->name('cart');
	Route::post('/cart/deleteitem', [CartController::class, 'deletecartitem']);
	Route::post('/cart/qtyupdate', [CartController::class, 'qtyupdate']);
	Route::post('addtocart', [CartController::class, 'addtocart']);
    Route::delete('/cart/{id}', [CartController::class, 'deletecartitem'])
        ->name('cart.delete')
        ->middleware('web');

// (Ha POST-ot használsz inkább, alternatíva)
    Route::post('/cart/delete', [CartController::class, 'deletecartitem'])
        ->name('cart.delete.post')->middleware('web');




	// checkout
    Route::post('/isopenclose', [CheckoutController::class, 'isopenclose']);


    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');

	Route::post('/placeorder', [CheckoutController::class, 'placeorder']);


    Route::post('/validate_data', [CheckoutController::class, 'validate_data'])
        ->middleware('delivery.guard'); // validálás során is bukjon el, ha közben tiltva

	Route::post('/timeslot', [CheckoutController::class, 'timeslot']);

	//Payment SUCCESS/FAIL

	Route::any('paymentsuccess', [CheckoutController::class, 'paymentsuccess']);
	Route::any('paymentfail', [CheckoutController::class, 'paymentfail']);

	// orders
	Route::get('/success-{order_number}', [WebOrderController::class, 'success'])->name('success');
	Route::get('/orders', [WebOrderController::class, 'index'])->name('order-history');
	Route::get('/orders-{order_number}', [WebOrderController::class, 'orderdetails'])->name('order-details');
	Route::post('/orders/cancel', [WebOrderController::class, 'statusupdate']);

	// address
	Route::get('/address', [AddressController::class, 'index'])->name('address');
	Route::get('/address/add', [AddressController::class, 'add'])->name('add-address');
	Route::post('/address/store', [AddressController::class, 'store']);
	Route::get('/address-{id}', [AddressController::class, 'show'])->name('update-address');
	Route::post('/address/update-{id}', [AddressController::class, 'update']);
	Route::post('/address/delete', [AddressController::class, 'deleteaddress']);
	Route::get('/address/delete', [AddressController::class, 'deleteaddress']);
	Route::post('/address/status', [AddressController::class, 'address_status']);
	Route::post('/getaddress', [AddressController::class, 'getaddress']);

	// promocode
	Route::post('/promocodes/apply', [WebPromocodeController::class, 'checkpromocode']);
	Route::post('/promocodes/remove', [WebPromocodeController::class, 'removepromocode']);


	Route::group(['middleware' => 'UserMiddleware'], function () {

		// user
		Route::get('/profile', [WebUserController::class, 'getprofile'])->name('user-profile');
		Route::post('/profile/update', [WebUserController::class, 'editprofile']);
		Route::get('/profile/send-email-status', [WebUserController::class, 'send_email_status']);
		Route::get('/refer-earn', [WebUserController::class, 'referearn'])->name('refer-earn');
		Route::get('/changepassword', [WebUserController::class, 'changepassword'])->name('user-changepassword');
		Route::post('/changepassword', [WebUserController::class, 'updatepassword']);
		Route::get('/logout', [WebUserController::class, 'logout'])->name('logout');

		// checkout


		// wallet
		Route::get('/wallet', [WalletController::class, 'index'])->name('user-wallet');
		Route::get('/wallet/addmoney', [WalletController::class, 'addform'])->name('add-money');
		Route::post('/wallet/recharge', [WalletController::class, 'addwallet']);

		Route::any('/addwalletsuccess', [WalletController::class, 'addsuccess']);
		Route::get('/addfail', [WalletController::class, 'addfail']);

		// favorite
		Route::get('/favouritelist', [FavoriteController::class, 'index'])->name('user-favouritelist');
		Route::post('/managefavorite', [FavoriteController::class, 'managefavorite']);
	});
});
//  -------------------------------   for admin  -----------------------------------------   //
Route::get('/auth', function () {
	return view('/auth');
});
Route::post('auth', 'HomeController@auth');
Route::group(['prefix' => 'admin', 'namespace' => 'admin'], function () {
	Route::get('/', function () {
		return view('admin.auth.login');
	});
	Route::post('check-login', [AdminController::class, 'check_admin']);
	Route::get('/forgot-password', function () {
		return view('admin.auth.forgot_password');
	});
	Route::post('send-pass', [AdminController::class, 'send_pass']);
	Route::post('auth', [AdminController::class, 'auth']);

	Route::group(['middleware' => 'AdminAuth'], function () {

		Route::group(['prefix' => 'language-settings'], function () {
			Route::get('/', [LangController::class, 'index']);
			Route::get('/{code}', [LangController::class, 'language']);
			Route::post('/update', [LangController::class, 'storeLanguageData']);
			Route::get('/language/edit-{id}', [LangController::class, 'edit']);
			Route::post('/update-{id}', [LangController::class, 'update']);
			Route::get('/layout/update-{id}/{status}', [LangController::class, 'layout']);
			Route::get('language/delete-{id}/{status}', [LangController::class, 'delete']);
		});

		//subscribe
		Route::get('/subscribe', [OtherPagesController::class, 'subscribe']);
		// our-team
		Route::get('our-team', [OtherPagesController::class, 'our_team_index']);
		Route::get('our-team/add', [OtherPagesController::class, 'our_team_add']);
		Route::post('our-team/store', [OtherPagesController::class, 'our_team_store']);
		Route::get('our-team-{id}', [OtherPagesController::class, 'our_team_show']);
		Route::post('our-team/update-{id}', [OtherPagesController::class, 'our_team_update']);
		Route::post('our-team/delete', [OtherPagesController::class, 'our_team_delete']);
		Route::post('our-team/reorder_our_team', [OtherPagesController::class, 'reorder_our_team']);
		// tutorial
		Route::get('tutorial', [OtherPagesController::class, 'tutorial_index']);
		Route::get('tutorial/add', [OtherPagesController::class, 'tutorial_add']);
		Route::post('tutorial/store', [OtherPagesController::class, 'tutorial_store']);
		Route::get('tutorial-{id}', [OtherPagesController::class, 'tutorial_show']);
		Route::post('tutorial/update-{id}', [OtherPagesController::class, 'tutorial_update']);
		Route::post('tutorial/delete', [OtherPagesController::class, 'tutorial_delete']);
		// faq
		Route::get('faq', [OtherPagesController::class, 'faq_index']);
		Route::get('faq/add', [OtherPagesController::class, 'faq_add']);
		Route::post('faq/store', [OtherPagesController::class, 'faq_store']);
		Route::get('faq-{id}', [OtherPagesController::class, 'faq_show']);
		Route::post('faq/update-{id}', [OtherPagesController::class, 'faq_update']);
		Route::post('faq/delete', [OtherPagesController::class, 'faq_delete']);
		Route::post('faq/reorder_faq', [OtherPagesController::class, 'reorder_faq']);
		// why_choose_us
		Route::get('choose_us', [WhyChooseUsController::class, 'index']);
		Route::get('choose_us/add', [WhyChooseUsController::class, 'add']);
		Route::post('choose_us/store', [WhyChooseUsController::class, 'store']);
		Route::get('choose_us-{id}', [WhyChooseUsController::class, 'show']);
		Route::post('choose_us/update-{id}', [WhyChooseUsController::class, 'update']);
		Route::post('choose_us/delete', [WhyChooseUsController::class, 'delete']);
		Route::post('choose_us/reorder_choose_us', [WhyChooseUsController::class, 'reorder_choose_us']);
		// gallery
		Route::get('gallery', [OtherPagesController::class, 'gallery_index']);
		Route::get('gallery/add', [OtherPagesController::class, 'gallery_add']);
		Route::post('gallery/store', [OtherPagesController::class, 'gallery_store']);
		Route::get('gallery-{id}', [OtherPagesController::class, 'gallery_show']);
		Route::post('gallery/update-{id}', [OtherPagesController::class, 'gallery_update']);
		Route::post('gallery/delete', [OtherPagesController::class, 'gallery_delete']);
		// others
		Route::get('home', [AdminController::class, 'home'])->name('dashboard');
		Route::post('change-password', [AdminController::class, 'changepassword']);
		Route::post('edit-profile', [AdminController::class, 'editprofile']);
		Route::get('getorder', [AdminController::class, 'getorder']);
		Route::get('change-status', [AdminController::class, 'changestatus']);
		// bookings
		Route::get('bookings', [BookingsController::class, 'bookings']);
		Route::post('bookings/status', [BookingsController::class, 'bookingstatus']);
		// slider
		Route::get('slider', [SliderController::class, 'index']);
		Route::get('slider/list', [SliderController::class, 'list']);
		Route::get('slider/add', [SliderController::class, 'add']);
		Route::post('slider/store', [SliderController::class, 'store']);
		Route::get('slider-{id}', [SliderController::class, 'show']);
		Route::post('slider/update-{id}', [SliderController::class, 'update']);
		Route::post('slider/status', [SliderController::class, 'status']);
		Route::post('slider/destroy', [SliderController::class, 'destroy']);
		Route::post('slider/reorder_slider', [SliderController::class, 'reorder_slider']);
		// category
		Route::get('category', [CategoryController::class, 'index']);
		Route::get('category/add', [CategoryController::class, 'add']);
		Route::post('category/store', [CategoryController::class, 'store']);
		Route::get('category-{id}', [CategoryController::class, 'show']);
		Route::post('category/update-{id}', [CategoryController::class, 'update']);
		Route::post('category/status', [CategoryController::class, 'status']);
		Route::post('category/delete', [CategoryController::class, 'delete']);
		Route::post('category/reorder_category', [CategoryController::class, 'reorder_category']);

		// Language Settings

		Route::group(['prefix' => 'language-settings'], function () {
			Route::get('/', [LangController::class, 'index']);
			Route::get('/{code}', [LangController::class, 'language']);
			Route::post('/update', [LangController::class, 'storeLanguageData']);
			Route::get('/language/edit-{id}', [LangController::class, 'edit']);
			Route::post('/update-{id}', [LangController::class, 'update']);
			Route::get('/layout/update-{id}/{status}', [LangController::class, 'layout']);
			Route::get('language/delete-{id}/{status}', [LangController::class, 'delete']);
		});


		// sub-category
		Route::get('sub-category', [CategoryController::class, 'subcategory_index']);
		Route::get('sub-category/add', [CategoryController::class, 'subcategory_add']);
		Route::post('sub-category/store', [CategoryController::class, 'subcategory_store']);
		Route::post('sub-category/status', [CategoryController::class, 'subcategory_status']);
		Route::post('sub-category/delete', [CategoryController::class, 'subcategory_delete']);
		Route::get('sub-category-{id}', [CategoryController::class, 'subcategory_show']);
		Route::post('sub-category/update-{id}', [CategoryController::class, 'subcategory_update']);
		Route::post('sub-category/reorder_subcategory', [CategoryController::class, 'reorder_subcategory']);
		// item
		Route::get('item', [ItemController::class, 'index']);
		Route::get('item/add', [ItemController::class, 'additem']);
		Route::post('item/store', [ItemController::class, 'store']);
		Route::get('item/list', [ItemController::class, 'list']);
		Route::post('item/update', [ItemController::class, 'update']);
		Route::post('item/showimage', [ItemController::class, 'showimage']);
		Route::post('item/updateimage', [ItemController::class, 'updateimage']);
		Route::post('item/storeimages', [ItemController::class, 'storeimages']);
		Route::post('item/destroyimage', [ItemController::class, 'destroyimage']);
		Route::post('item/status', [ItemController::class, 'status']);
		Route::post('item/featured', [ItemController::class, 'featured']);
		Route::post('item/delete', [ItemController::class, 'delete']);
		Route::get('item-{id}', [ItemController::class, 'edititem']);
		Route::get('item/subcategories', [ItemController::class, 'subcategories']);
		Route::get('/getextras', [ItemController::class, 'getextras']);
		Route::post('item/deleteextras', [ItemController::class, 'deleteextras']);
		Route::post('item/reorder_item', [ItemController::class, 'reorder_item']);


        Route::post('admin/item/today-unavailable', [ItemController::class, 'toggleTodayUnavailable'])
            ->name('admin.item.today-unavailable')
            ->middleware('auth'); // vagy a megszokott admin middleware-ed

		// payment
		Route::get('payment', [PaymentController::class, 'index']);
		Route::post('payment/update', [PaymentController::class, 'update']);
		Route::post('payment/reorder_payment', [PaymentController::class, 'reorder_payment']);
		// addons
		Route::get('addons', [AddonsController::class, 'index']);
		Route::get('addons/add', [AddonsController::class, 'add']);
		Route::post('addons/store', [AddonsController::class, 'store']);
		Route::get('addons-{id}', [AddonsController::class, 'show']);
		Route::post('addons/update-{id}', [AddonsController::class, 'update']);
		Route::post('addons/status', [AddonsController::class, 'status']);
		Route::post('addons/delete', [AddonsController::class, 'delete']);
		Route::post('addons/reorder_addons', [AddonsController::class, 'reorder_addons']);
		// ADDONS GROUP
		Route::get('addongroup', [AddonsController::class, 'addons_group_index']);
		Route::get('addongroup/add', [AddonsController::class, 'add_addons_group']);
		Route::post('addongroup/store', [AddonsController::class, 'store_addons_group']);
		Route::get('addongroup-{id}', [AddonsController::class, 'show_addons_group']);
		Route::post('addongroup/update-{id}', [AddonsController::class, 'update_addons_group']);
		Route::post('addongroup/status', [AddonsController::class, 'status_addons_group']);
		Route::post('addongroup/delete', [AddonsController::class, 'delete_addons_group']);
		Route::post('addongroup/reorder_addongroup', [AddonsController::class, 'reorder_addongroup']);
		//SHIPPING AREAS
		Route::get('shippingarea', [ShippingareaController::class, 'index']);
		Route::get('shippingarea/add', [ShippingareaController::class, 'add']);
		Route::post('shippingarea/store', [ShippingareaController::class, 'store']);
		Route::get('shippingarea-{id}', [ShippingareaController::class, 'Edit']);
		Route::post('shippingarea/delete', [ShippingareaController::class, 'delete']);
		Route::post('shippingarea/update-{id}', [ShippingareaController::class, 'update']);
		Route::post('shippingarea/reorder_shippingarea', [ShippingareaController::class, 'reorder_shippingarea']);
		// tax
		Route::get('tax', [TaxController::class, 'index']);
		Route::get('tax/add', [TaxController::class, 'add']);
		Route::post('tax/store', [TaxController::class, 'store']);
		Route::get('tax-{id}', [TaxController::class, 'show']);
		Route::post('tax/update-{id}', [TaxController::class, 'update']);
		Route::post('tax/status', [TaxController::class, 'status']);
		Route::post('tax/delete', [TaxController::class, 'delete']);
		Route::post('tax/reorder_tax', [TaxController::class, 'reorder_tax']);
		//Global Extras
		Route::get('global_extras', [GlobalExtrasController::class, 'index']);
		Route::get('global_extras/add', [GlobalExtrasController::class, 'add']);
		Route::post('global_extras/store', [GlobalExtrasController::class, 'store']);
		Route::get('global_extras-{id}', [GlobalExtrasController::class, 'edit']);
		Route::post('global_extras/delete', [GlobalExtrasController::class, 'delete']);
		Route::post('global_extras/update-{id}', [GlobalExtrasController::class, 'update']);
		Route::any('global_extras/status', [GlobalExtrasController::class, 'change_status']);
		Route::post('global_extras/reorder_global', [GlobalExtrasController::class, 'reorder_global']);
		// users
		Route::get('users', [UserController::class, 'index']);
		Route::get('users/add', [UserController::class, 'add_customers']);
		Route::post('users/store', [UserController::class, 'store_customers']);
		Route::get('users-{id}', [UserController::class, 'show_customers']);
		Route::post('users/update-{id}', [UserController::class, 'update_customers']);
		Route::post('users/status', [UserController::class, 'status']);
		Route::post('users/delete', [UserController::class, 'delete']);
		Route::get('users/details-{id}', [UserController::class, 'userdetails']);
		Route::post('users/change-wallet', [UserController::class, 'add_deduct']);

		Route::get('orders', [OrderController::class, 'index']);
		Route::get('invoice/{id}', [OrderController::class, 'invoice']);
		Route::get('print/{id}', [OrderController::class, 'print']);
		Route::get('generatepdf/{id}', [OrderController::class, 'generatepdf']);
		Route::post('orders/customerbillinfo', [OrderController::class, 'customerbillinfo']);
		Route::post('orders/order_note', [OrderController::class, 'order_note']);
		Route::post('orders/update', [OrderController::class, 'update']);
		Route::post('orders/assign-driver', [OrderController::class, 'assign_driver']);
		Route::get('report', [OrderController::class, 'get_reports']);
		Route::post('orders/payment_status-{status}', [OrderController::class, 'payment_status']);
		// banner
		Route::get('bannersection-1', [BannerController::class, 'index']);
		Route::get('bannersection-2', [BannerController::class, 'index']);
		Route::get('bannersection-3', [BannerController::class, 'index']);
		Route::get('bannersection-4', [BannerController::class, 'index']);
		Route::get('bannersection-1/add', [BannerController::class, 'add']);
		Route::get('bannersection-2/add', [BannerController::class, 'add']);
		Route::get('bannersection-3/add', [BannerController::class, 'add']);
		Route::get('bannersection-4/add', [BannerController::class, 'add']);
		Route::post('banner/store', [BannerController::class, 'store']);
		Route::get('bannersection-{section}-{id}', [BannerController::class, 'show']);
		Route::post('banner/update-{id}', [BannerController::class, 'update']);
		Route::post('banner/status', [BannerController::class, 'status']);
		Route::post('banner/destroy', [BannerController::class, 'destroy']);
		Route::post('banner/reorder_banner', [BannerController::class, 'reorder_banner']);
		// settings
		Route::get('settings', [SettingController::class, 'index']);
		Route::get('settings/delete-feature-{id}', [SettingController::class, 'delete_feature']);
		Route::get('settings/delete-social-link-{id}', [SettingController::class, 'delete_social_link']);
		Route::post('settings/update', [SettingController::class, 'settings_update']);
		// contact
		Route::get('contact', [ContactController::class, 'index']);
		Route::post('contact/destroy', [ContactController::class, 'destroy']);
		// driver
		Route::get('driver', [DriverController::class, 'index']);
		Route::get('driver/add', [DriverController::class, 'add']);
		Route::post('driver/store', [DriverController::class, 'store']);
		Route::get('driver-{id}', [DriverController::class, 'show']);
		Route::get('driver/details-{id}', [DriverController::class, 'driverdetails']);
		Route::post('driver/update-{id}', [DriverController::class, 'update']);
		Route::post('driver/status', [DriverController::class, 'status']);
		// time
		Route::get('time', [TimeController::class, 'index']);
		Route::post('time/store', [TimeController::class, 'store']);
		// CMS PAGES
		Route::get('privacypolicy', [CMSPagesController::class, 'privacypolicy']);
		Route::post('privacypolicy/update', [CMSPagesController::class, 'privacypolicy_update']);
		Route::get('termscondition', [CMSPagesController::class, 'termscondition']);
		Route::post('termscondition/update', [CMSPagesController::class, 'termscondition_update']);
		Route::get('refundpolicy', [CMSPagesController::class, 'refundpolicy']);
		Route::post('refundpolicy/update', [CMSPagesController::class, 'refundpolicy_update']);
		Route::get('aboutus', [CMSPagesController::class, 'aboutus']);
		Route::post('aboutus/update', [CMSPagesController::class, 'aboutus_update']);
		// notification
		Route::get('notification', [NotificationController::class, 'index']);
		Route::get('notification/add', [NotificationController::class, 'add']);
		Route::post('notification/store', [NotificationController::class, 'store']);
		// employee
		Route::get('employee', [UserController::class, 'employee']);
		Route::get('employee/add', [UserController::class, 'add_employee']);
		Route::post('employee/store', [UserController::class, 'store_employee']);
		Route::post('employee/status', [UserController::class, 'status_employee']);
		Route::get('employee-{id}', [UserController::class, 'show_employee']);
		Route::post('employee/update-{id}', [UserController::class, 'update_employee']);
		// clear-cache
		Route::get('clear-cache', function () {
			Artisan::call('cache:clear');
			Artisan::call('route:clear');
			Artisan::call('config:clear');
			Artisan::call('view:clear');
			return redirect()->back()->with('success', trans('messages.success'));
		});
		// systemaddons
		Route::get('systemaddons', [SystemAddonsController::class, 'index']);
		Route::get('createsystem-addons', [SystemAddonsController::class, 'createsystemaddons']);
		Route::post('systemaddons/store', [SystemAddonsController::class, 'store']);
		Route::post('systemaddons/update', [SystemAddonsController::class, 'update']);
	});
	Route::get('logout', [AdminController::class, 'logout']);


});
