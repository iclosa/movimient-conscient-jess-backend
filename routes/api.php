<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PassController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\VideoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('settings', [ContentController::class, 'settings'])->name('settings');

    Route::prefix('public')->name('public.')->middleware(['throttle:60,1', 'cache.headers:public;max_age=60;etag'])->group(function () {
        Route::get('settings', [ContentController::class, 'settings'])->name('settings');
        Route::get('activities', [PublicController::class, 'activities'])->name('activities');
        Route::get('sessions', [PublicController::class, 'sessions'])->name('sessions');
        Route::get('passes', [PublicController::class, 'passes'])->name('passes');
        Route::get('events', [PublicController::class, 'events'])->name('events');
        Route::get('promotions', [PublicController::class, 'promotions'])->name('promotions');
        Route::get('posts', [PublicController::class, 'posts'])->name('posts');
        Route::get('videos', [PublicController::class, 'videos'])->name('videos');
        Route::get('videos/{id}', [PublicController::class, 'video'])->name('videos.show');
    });

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    });

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('me', [ProfileController::class, 'show'])->name('me');
        Route::patch('me', [ProfileController::class, 'update'])->name('me.update');
        Route::put('me/password', [ProfileController::class, 'updatePassword'])->name('me.password');
        Route::delete('me', [ProfileController::class, 'destroy'])->name('me.destroy');
        Route::get('me/streak', [ProfileController::class, 'streak'])->name('me.streak');
        Route::post('me/children', [ProfileController::class, 'storeChild'])->name('children.store');
        Route::patch('me/children/{child}', [ProfileController::class, 'updateChild'])->name('children.update');
        Route::delete('me/children/{child}', [ProfileController::class, 'destroyChild'])->name('children.destroy');
        Route::post('me/devices', [ProfileController::class, 'storeDeviceToken'])->name('devices.store');
        Route::delete('me/devices', [ProfileController::class, 'destroyDeviceToken'])->name('devices.destroy');

        Route::get('home', HomeController::class)->name('home');

        Route::get('activities', [ScheduleController::class, 'activities'])->name('activities');
        Route::get('sessions', [ScheduleController::class, 'sessions'])->name('sessions.index');
        Route::get('sessions/{id}', [ScheduleController::class, 'show'])->name('sessions.show');

        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::delete('bookings/{id}', [BookingController::class, 'destroy'])->name('bookings.destroy');
        Route::post('bookings/{id}/rating', [BookingController::class, 'rate'])->name('bookings.rate');

        Route::get('passes', [PassController::class, 'catalogue'])->name('passes.index');
        Route::get('me/passes', [PassController::class, 'mine'])->name('passes.mine');
        Route::post('passes/{id}/purchase', [PassController::class, 'purchase'])->name('passes.purchase');

        Route::get('videos', [VideoController::class, 'index'])->name('videos.index');
        Route::get('videos/{id}', [VideoController::class, 'show'])->name('videos.show');
        Route::post('videos/{id}/purchase', [VideoController::class, 'purchase'])->name('videos.purchase');

        Route::get('promotions', [ContentController::class, 'promotions'])->name('promotions');
        Route::get('events', [ContentController::class, 'events'])->name('events.index');
        Route::get('event-registrations', [ContentController::class, 'myEventRegistrations'])->name('events.registrations');
        Route::post('events/{id}/registration', [ContentController::class, 'registerForEvent'])->name('events.register');
        Route::delete('events/{id}/registration', [ContentController::class, 'cancelEventRegistration'])->name('events.unregister');
        Route::get('wellness', [ContentController::class, 'wellness'])->name('wellness');
        Route::get('wellness/sunrise', [ContentController::class, 'sunrise'])->name('wellness.sunrise');
        Route::get('announcements', [ContentController::class, 'announcements'])->name('announcements');
        Route::post('announcements/read-all', [ContentController::class, 'markAllAnnouncementsRead'])->name('announcements.read-all');
        Route::post('announcements/{id}/read', [ContentController::class, 'markAnnouncementRead'])->name('announcements.read');

        Route::get('gratitude', [ContentController::class, 'gratitudeIndex'])->name('gratitude.index');
        Route::post('gratitude', [ContentController::class, 'gratitudeStore'])->name('gratitude.store');
        Route::patch('gratitude/{id}', [ContentController::class, 'gratitudeUpdate'])->name('gratitude.update');
        Route::delete('gratitude/{id}', [ContentController::class, 'gratitudeDestroy'])->name('gratitude.destroy');
    });
});
