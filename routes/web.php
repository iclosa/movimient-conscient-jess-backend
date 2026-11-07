<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\PassController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\RatingController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\ScheduleTemplateController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserPassController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\WellnessItemController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/admin/acces', [AuthController::class, 'showLogin'])->name('login');
Route::post('/admin/acces', [AuthController::class, 'login'])->name('login.attempt')->middleware('throttle:10,1');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('sortir', [AuthController::class, 'logout'])->name('logout');
    Route::get('calendari', [CalendarController::class, 'index'])->name('calendar');

    // Classes y reservas
    Route::post('classes/{id}/cancella', [ClassSessionController::class, 'cancel'])->name('sessions.cancel');
    Route::post('classes/{id}/reactiva', [ClassSessionController::class, 'reactivate'])->name('sessions.reactivate');
    Route::post('classes/{sessionId}/reserves', [BookingController::class, 'store'])->name('bookings.store');
    Route::patch('reserves/{id}/assistencia', [BookingController::class, 'attendance'])->name('bookings.attendance');
    Route::post('reserves/{id}/cancella', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::resource('classes', ClassSessionController::class)->names('sessions')->parameters(['classes' => 'id']);

    Route::post('horari/genera', [ScheduleTemplateController::class, 'generate'])->name('templates.generate');
    Route::resource('horari', ScheduleTemplateController::class)->names('templates')->parameters(['horari' => 'id'])->except('show');
    Route::resource('activitats', ActivityController::class)->names('activities')->parameters(['activitats' => 'id'])->except('show');
    Route::resource('sales', RoomController::class)->names('rooms')->parameters(['sales' => 'id'])->except('show');
    Route::resource('professorat', InstructorController::class)->names('instructors')->parameters(['professorat' => 'id'])->except('show');

    // Alumnado
    Route::post('alumnes/{userId}/fills', [StudentController::class, 'storeChild'])->name('students.children.store');
    Route::delete('fills/{childId}', [StudentController::class, 'destroyChild'])->name('children.destroy');
    Route::post('alumnes/{userId}/bons', [UserPassController::class, 'store'])->name('user-passes.store');
    Route::put('bons-alumne/{id}', [UserPassController::class, 'update'])->name('user-passes.update');
    Route::delete('bons-alumne/{id}', [UserPassController::class, 'destroy'])->name('user-passes.destroy');
    Route::resource('alumnes', StudentController::class)->names('students')->parameters(['alumnes' => 'id']);
    Route::resource('bons', PassController::class)->names('passes')->parameters(['bons' => 'id'])->except('show');
    Route::resource('cobraments', PaymentController::class)->names('payments')->parameters(['cobraments' => 'id'])->except('show');
    Route::get('valoracions', [RatingController::class, 'index'])->name('ratings.index');

    // Contenido
    Route::resource('videos', VideoController::class)->names('videos')->parameters(['videos' => 'id'])->except('show');
    Route::resource('blog', PostController::class)->names('posts')->parameters(['blog' => 'id'])->except('show');
    Route::resource('promocions', PromotionController::class)->names('promotions')->parameters(['promocions' => 'id'])->except('show');
    Route::get('esdeveniments/{id}/inscrits', [EventController::class, 'registrations'])->name('events.registrations');
    Route::post('esdeveniments/{id}/inscrits', [EventController::class, 'register'])->name('events.register');
    Route::delete('inscripcions/{registrationId}', [EventController::class, 'unregister'])->name('events.unregister');
    Route::resource('esdeveniments', EventController::class)->names('events')->parameters(['esdeveniments' => 'id'])->except('show');
    Route::resource('mes', WellnessItemController::class)->names('wellness')->parameters(['mes' => 'id'])->except('show');

    // Comunicación y centro
    Route::post('avisos/{id}/envia', [AnnouncementController::class, 'send'])->name('announcements.send');
    Route::resource('avisos', AnnouncementController::class)->names('announcements')->parameters(['avisos' => 'id'])->except('show');
    Route::get('ajustos', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('ajustos', [SettingController::class, 'update'])->name('settings.update');
    Route::resource('equip', TeamController::class)->names('team')->parameters(['equip' => 'id'])->except('show');
});
