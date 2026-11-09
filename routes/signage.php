<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Signage\DeviceController;
use App\Http\Middleware\SignageDeviceAuth;
use App\Http\Middleware\SignageEnabled;
Route::middleware(SignageEnabled::class)->group(function(){
    Route::get('/signage',[\App\Http\Controllers\Signage\PlayerController::class,'index']);
    Route::get('/signage/sw.js',[\App\Http\Controllers\Signage\PlayerController::class,'worker']);
});
Route::prefix('signage/api/v1')->middleware([\Illuminate\Routing\Middleware\SubstituteBindings::class,SignageEnabled::class])->group(function(){
    Route::post('pairing/start',[DeviceController::class,'start'])->middleware('throttle:signage-start');
    Route::post('pairing/status',[DeviceController::class,'pairingStatus'])->middleware('throttle:signage-status');
    Route::middleware([SignageDeviceAuth::class,'throttle:signage-device'])->group(function(){
        Route::get('manifest',[DeviceController::class,'manifest']);
        Route::get('catalog',[DeviceController::class,'catalog']);
        Route::get('media/{hash}',[DeviceController::class,'media']);
        Route::post('heartbeat',[DeviceController::class,'heartbeat']);
    });
});
Route::prefix('admin/signage')->middleware(['web',SignageEnabled::class])->group(function(){
    Route::get('',[\App\Http\Controllers\admin\SignageController::class,'index'])->name('admin.signage');
    Route::post('scenes',[\App\Http\Controllers\admin\SignageController::class,'scene']);
    Route::post('playlists',[\App\Http\Controllers\admin\SignageController::class,'playlist']);
    Route::post('publish',[\App\Http\Controllers\admin\SignageController::class,'publish']);
    Route::post('rollback',[\App\Http\Controllers\admin\SignageController::class,'rollback']);
    Route::get('preview/{id}',[\App\Http\Controllers\admin\SignageController::class,'preview'])->whereNumber('id');
    Route::post('schedules',[\App\Http\Controllers\admin\SignageController::class,'schedule']);
    Route::post('schedules/{id}/remove',[\App\Http\Controllers\admin\SignageController::class,'unschedule'])->whereNumber('id');
    Route::post('media/{id}/verify',[\App\Http\Controllers\admin\SignageMediaController::class,'verify'])->whereNumber('id');
    Route::post('media',[\App\Http\Controllers\admin\SignageMediaController::class,'upload']);
    Route::get('media/{id}',[\App\Http\Controllers\admin\SignageMediaController::class,'preview'])->whereNumber('id');
    Route::post('media/{id}/retry',[\App\Http\Controllers\admin\SignageMediaController::class,'retry'])->whereNumber('id');
    Route::post('media/{id}/archive',[\App\Http\Controllers\admin\SignageMediaController::class,'destroy'])->whereNumber('id');
    Route::post('screens',[\App\Http\Controllers\admin\SignageController::class,'screen']);
    Route::post('pair',[\App\Http\Controllers\admin\SignageController::class,'pair'])->middleware('throttle:signage-pair');
    Route::post('revoke',[\App\Http\Controllers\admin\SignageController::class,'revoke']);
});
