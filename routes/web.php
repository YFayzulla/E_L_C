<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\DeptStudentController;
use App\Http\Controllers\ExtraTeacherController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupExtraController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherAdminPanel;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\WaitersController;
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
Route::get('/1', function () {
    $student=\App\Models\User::find(3);
    return view('user.pdf.student_show',compact('student'));
});
//profil
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [Controller::class, 'auth'])->name('user');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*                          Admin                */
Route::group(['middleware' => ['auth', 'role:admin']], function () {
//    Route::get('payment', [Controller::class, 'index'])->name('dashboard');


//    PDF
    Route::get('/student/pdf/{id}', [PdfController::class, 'history']);
    Route::get('/dept/pdf/{date}', [PdfController::class, 'RoomListPDF']);
    Route::get('/assessment/pdf/{date}', [PdfController::class, 'Assessment']);

//    group
    Route::resource('group', GroupController::class);
    Route::delete('/delete-multiple', [GroupExtraController::class, 'deleteMultiple'])->name('deleteMultiple');
    Route::get('waiters', [WaitersController::class, 'index'])->name('waiters.index');
    Route::post('group/change/{id}',[GroupExtraController::class,'change_group'])->name('student.change.group');
    Route::get('group/assessment/{id}',[GroupExtraController::class,'attendance'])->name('group.attendance');
    Route::get('/attendance/filter', [GroupExtraController::class, 'filter'])->name('attendance.filter');


//    student
    Route::resource('student', StudentController::class);
    Route::post('student/dept', [Controller::class, 'search'])->name('student.search');
    Route::resource('dept', DeptStudentController::class);

//    teacher
    Route::resource('teacher', TeacherController::class);
    Route::delete('teacher/group/delete/{id}', [ExtraTeacherController::class, 'group_delete'])->name('teacher_group.delete');
    Route::put('teacher/group/{id}/store', [ExtraTeacherController::class, 'add_group'])->name('teacher_group.store');

});

//Teachers
Route::group(['middleware' => ['auth', 'role:user']], function () {
//teacher panel
    Route::get('groups', [TeacherAdminPanel::class, 'group'])->name('attendance');
    Route::get('attendance/{id}', [TeacherAdminPanel::class, 'attendance'])->name('attendance.check');
    Route::post('attendance/submit/{id}', [TeacherAdminPanel::class, 'attendance_submit'])->name('attendance.submit');
    Route::resource('assessment',AssessmentController::class);

});

require __DIR__ . '/auth.php';
