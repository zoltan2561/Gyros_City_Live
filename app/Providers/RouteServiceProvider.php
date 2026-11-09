<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::group([], base_path('routes/signage.php'));
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // pos Route file
            Route::prefix('admin/pos')
                ->middleware('web')
                ->group(base_path('routes/pos.php'));

            // otp Route file
            Route::prefix('admin')->middleware('web')->group(base_path('routes/otp.php'));

            //toyyibpay
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/toyyibpay.php'));

            //paytab
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/paytab.php'));

            //Paypal
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/paypal.php'));

            //MyFatoorah
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/myfatoorah.php'));

            //MercadoPago
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/mercadopago.php'));

            //phonepe
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/phonepe.php'));

            //mollie
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/mollie.php'));

            //khalti
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/khalti.php'));

            //Language
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/language.php'));

            //Import
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/import.php'));

            //Whatsapp message
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/whatsapp.php'));


            //Product Reviews
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/productreview.php'));

            //Store Reviews
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/storereview.php'));

            //PWA
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/pwa.php'));

            //Google
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/googlelogin.php'));

            //Facebook
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/facebooklogin.php'));

            //Email
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/emailsettings.php'));

            //Pixel
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/pixelsettings.php'));

            //Blog
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/blog.php'));

            //Coupon
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/coupon.php'));

            //Role
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/role.php'));

            //Recaptcha
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/recaptcha.php'));

            //Custom Status
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/custom_status.php'));

            //Top Deals
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/top_deals.php'));

            //Age verification
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/age_verification.php'));

            // Quick call Route file
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/quick_call.php'));

            //Product Fake notification
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/fake_sales_notification.php'));

            // Wizzchat Route file
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/wizz_chat.php'));

            // Tawk Route file
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/tawk.php'));

            //Product Fake view
            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/product_fake_view.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('signage-start',fn(Request $request)=>Limit::perMinute(5)->by('signage-start:'.$request->ip()));
        RateLimiter::for('signage-status',fn(Request $request)=>[
            Limit::perMinute(30)->by('signage-status:'.hash('sha256',(string)$request->bearerToken())),
            Limit::perMinute(240)->by('signage-status-ip:'.$request->ip()),
        ]);
        RateLimiter::for('signage-device',fn(Request $request)=>[
            Limit::perMinute(240)->by('signage-device:'.hash('sha256',(string)$request->bearerToken())),
            Limit::perMinute(2000)->by('signage-device-ip:'.$request->ip()),
        ]);
        RateLimiter::for('signage-pair',fn(Request $request)=>Limit::perMinute(5)->by('signage-pair:'.($request->user()?->id??$request->ip())));
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
