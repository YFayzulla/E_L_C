<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\CommentStudentController;
use App\Http\Controllers\DeptStudentController;
use App\Http\Controllers\ExtraTeacherController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupExtraController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RefreshController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherAdminPanel;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TestResultController;
use App\Http\Controllers\WaitersController;
use Illuminate\Support\Facades\Route;


Route::delete('attendance/delete/{id}', [ExtraTeacherController::class, 'attendanceDelete'])->name('attendance.delete');

Route::middleware('auth')->group(function () {
    Route::get('/', [Controller::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


//attendance

    Route::get('group/assessment/{id}', [GroupExtraController::class, 'attendance'])->name('group.attendance');

});

/*                          Admin                */

Route::group(['middleware' => ['auth', 'role:admin']], function () {


    Route::get('Test', [TestResultController::class, 'index'])->name('test');
    Route::get('Test/{id}/show', [TestResultController::class, 'showResults'])->name('test.show');

//    Route::get('dashboard',

//    PDF

    Route::get('/student/pdf/{id}', [PdfController::class, 'history']);
    Route::get('/dept/pdf', [PdfController::class, 'RoomListPDF']);
    Route::get('/assessment/pdf/{date}', [PdfController::class, 'Assessment']);
    Route::get('/teacher/pdf', [PdfController::class, 'teacher']);
    Route::get('/group/pdf', [PdfController::class, 'group']);
    Route::get('/student/pdf', [PdfController::class, 'student']);
    Route::get('/student/payment/pdf', [PdfController::class, 'studentPayment']);

//    Excel
    Route::get('export-attendances/{id}', [GroupExtraController::class, 'export'])->name('export.attendances');

//    group

    Route::resource('group', GroupController::class);
    Route::delete('/delete-multiple', [GroupExtraController::class, 'deleteMultiple'])->name('deleteMultiple');
    Route::get('waiters', [WaitersController::class, 'index'])->name('waiters.index');
    Route::post('group/change/{id}', [GroupExtraController::class, 'change_group'])->name('student.change.group');

    //    Route::get('group/attendance/filter/{id}', [GroupExtraController::class, 'filter'])->name('attendance.filter');


    Route::get('group/student/{id}', [GroupExtraController::class, 'show'])->name('group.students');

//    student

    Route::resource('student', StudentController::class);
    Route::post('student/dept', [Controller::class, 'search'])->name('student.search');
    Route::resource('dept', DeptStudentController::class);
    Route::get('refresh/{id}/update', [RefreshController::class, 'update'])->name('refresh.update');

//    teacher

    Route::resource('teacher', TeacherController::class);
    Route::delete('teacher/group/delete/{id}', [ExtraTeacherController::class, 'group_delete'])->name('teacher_group.delete');
    Route::put('teacher/group/{id}/store', [ExtraTeacherController::class, 'add_group'])->name('teacher_group.store');

//     finance

    Route::get('finance', [FinanceController::class, 'index'])->name('finance.other');
    Route::post('finance/store', [FinanceController::class, 'store'])->name('finance.store');
    Route::put('finance/update/{id}', [FinanceController::class, 'update'])->name('finance.update');
    Route::delete('finance/delete/{id}', [FinanceController::class, 'destroy'])->name('finance.destroy');

});

//Teachers

Route::group(['middleware' => ['auth', 'role:user']], function () {
//teacher panel
    Route::get('attendance/lists', [TeacherAdminPanel::class, 'attendanceIndex'])->name('attendance.index');
    Route::get('groups', [TeacherAdminPanel::class, 'group'])->name('attendance');
    Route::get('attendance/{id}', [TeacherAdminPanel::class, 'attendance'])->name('attendance.check');
    Route::post('attendance/submit/{id}', [TeacherAdminPanel::class, 'attendance_submit'])->name('attendance.submit');
    Route::resource('assessment', AssessmentController::class);
    Route::get('comment', [CommentStudentController::class, 'index'])->name('comment');
    Route::post('comment/store', [CommentStudentController::class, 'store'])->name('comment.store');

});

require __DIR__ . '/auth.php';
