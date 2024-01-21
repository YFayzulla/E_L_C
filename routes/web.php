<?php

use App\Http\Controllers\DeptStudentController;
use App\Http\Controllers\ExtraTeacherController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherAdminPanel;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [Controller::class, 'auth'])->name('user');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*                          USER                */
Route::group(['middleware' => ['auth', 'role:admin']], function () {
//    Route::get('payment', [Controller::class, 'index'])->name('dashboard');
    Route::resource('teacher', TeacherController::class);
    Route::resource('group', GroupController::class);
    Route::resource('student', StudentController::class);
    Route::resource('dept', DeptStudentController::class);
    Route::put('teacher/group/{id}/store', [ExtraTeacherController::class, 'add_group'])->name('teacher_group.store');
    Route::delete('teacher/group/delete/{id}', [ExtraTeacherController::class, 'group_delete'])->name('teacher_group.delete');
    Route::post('student/dept', [Controller::class, 'search'])->name('student.search');
    Route::get('/roomList/pdf/{date}', [PdfController::class, 'RoomListPDF']);
    // Room
});

//Teachers
Route::group(['middleware' => ['auth', 'role:user']], function () {
    Route::get('groups', [TeacherAdminPanel::class, 'group'])->name('attendance');
    Route::get('attendance/{id}', [TeacherAdminPanel::class, 'attendance'])->name('attendance.check');
    Route::get('attendance/submit', [TeacherAdminPanel::class, 'attendance_submit'])->name('attendance.submit');
});

require __DIR__ . '/auth.php';
