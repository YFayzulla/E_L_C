<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\TeacherApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API (elc_mobile)
|--------------------------------------------------------------------------
|
| Sanctum bearer tokens. Only teachers (`user`) and students may authenticate
| here — AuthController::login refuses everyone else, so an admin token can
| never end up on a handset.
|
| Group-scoped teacher actions additionally check the RELATIONSHIP through
| AuthorizesGroupAccess: the role says "a teacher", the trait says "this
| teacher's group".
|
*/

Route::prefix('v1')->group(function () {

    // ---------------------------------------------------------------- public
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.login');

    // Cheap reachability probe for the app's "server unavailable" screen.
    Route::get('ping', fn() => response()->json([
        'ok'   => true,
        'data' => ['app' => config('app.name'), 'time' => now()->toIso8601String()],
    ]))->name('api.ping');

    // ------------------------------------------------------------- protected
    // `centre.member` cannot live in the `api` middleware group: Sanctum
    // resolves the user in `auth:sanctum`, which is route middleware and
    // therefore runs after the group.
    Route::middleware(['auth:sanctum', 'centre.token', 'centre.member'])->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.me');

        // ---------------------------------------------------------- teacher
        Route::middleware('role:user')->prefix('teacher')->group(function () {
            Route::get('groups', [TeacherApiController::class, 'groups']);
            Route::get('groups/{group}/students', [TeacherApiController::class, 'groupStudents'])->whereNumber('group');

            Route::get('groups/{group}/attendance', [TeacherApiController::class, 'attendanceSheet'])->whereNumber('group');
            Route::post('groups/{group}/attendance', [TeacherApiController::class, 'submitAttendance'])->whereNumber('group');

            Route::get('groups/{group}/lessons', [TeacherApiController::class, 'lessons'])->whereNumber('group');
            Route::get('groups/{group}/skills', [TeacherApiController::class, 'skillSheet'])->whereNumber('group');
            Route::post('groups/{group}/skills', [TeacherApiController::class, 'storeSkills'])->whereNumber('group');

            Route::get('groups/{group}/progress', [TeacherApiController::class, 'groupProgress'])->whereNumber('group');

            Route::get('homework', [TeacherApiController::class, 'homework']);
            Route::post('homework', [TeacherApiController::class, 'storeHomework']);
            Route::get('homework/{homework}/submissions', [TeacherApiController::class, 'homeworkSubmissions'])->whereNumber('homework');
            Route::post('homework/{homework}/grade', [TeacherApiController::class, 'gradeHomework'])->whereNumber('homework');
        });

        // ---------------------------------------------------------- student
        Route::middleware('role:student')->prefix('student')->group(function () {
            Route::get('dashboard', [StudentApiController::class, 'dashboard']);
            Route::get('attendance', [StudentApiController::class, 'attendance']);
            Route::get('homework', [StudentApiController::class, 'homework']);
            Route::post('homework/{homework}/submit', [StudentApiController::class, 'submitHomework'])->whereNumber('homework');
            Route::get('skills', [StudentApiController::class, 'skills']);
            Route::get('progress', [StudentApiController::class, 'progress']);
            Route::get('certificates', [StudentApiController::class, 'certificates']);
        });
    });
});
