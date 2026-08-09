<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AttendanceAdminController;
use App\Http\Controllers\CentreChoiceController;
use App\Http\Controllers\CentreSettingsController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DeptStudentController;
use App\Http\Controllers\SuperAdmin\CentreAdminController;
use App\Http\Controllers\ExtraTeacherController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupExtraController;
use App\Http\Controllers\GroupLifecycleController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LessonSkillGradeController;
use App\Http\Controllers\ParentAdminController;
use App\Http\Controllers\ParentPanelController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\RefreshController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\StudentSmsController;
use App\Http\Controllers\StudentTransferController;
use App\Http\Controllers\TeacherAdminPanel;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherGroupController;
use App\Http\Controllers\TestResultController;
use App\Http\Controllers\WaitersController;
use App\Services\MessageService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/*
| Route::resource('teacher') binds {teacher}. Without this pattern the literal
| segments declared under teacher/ (teacher/groups, teacher/homework, ...) and
| the resource's {teacher} placeholder fight over the same URLs.
*/
Route::pattern('teacher', '[0-9]+');
Route::pattern('homework', '[0-9]+');

/*
| Eskiz delivery reports. Public and CSRF-exempt (see VerifyCsrfToken::$except)
| — the gateway is an external server and cannot carry a session token. The
| handler only logs and always answers 200, otherwise Eskiz retries forever.
*/
Route::post('sms/callback', [MessageService::class, 'receive'])->name('sms.callback');

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| APEX DOMEN — markazsiz sahifalar
|--------------------------------------------------------------------------
| Bular ataylab `centre` middleware'idan tashqarida: apexning o'zida markaz
| yo'q va bo'lishi ham shart emas. Sessiya cookie'si barcha subdomenlar
| bo'ylab umumiy, shuning uchun bu yerda tanlangan markazga o'tish qayta
| kirishni talab qilmaydi — lekin har bir subdomenda EnsureCentreMember
| a'zolikni qaytadan tekshiradi.
*/
Route::middleware('auth')->group(function () {
    Route::get('centres', [CentreChoiceController::class, 'index'])->name('centres.choose');

    Route::middleware('super-admin')->prefix('super')->name('super.')->group(function () {
        Route::controller(CentreAdminController::class)->group(function () {
            Route::get('centres', 'index')->name('centres.index');
            Route::get('centres/create', 'create')->name('centres.create');
            Route::post('centres', 'store')->name('centres.store');
            Route::get('centres/{centre}/edit', 'edit')->whereNumber('centre')->name('centres.edit');
            Route::put('centres/{centre}', 'update')->whereNumber('centre')->name('centres.update');
            Route::post('centres/{centre}/admin', 'attachAdmin')->whereNumber('centre')->name('centres.admin');
            Route::post('owners', 'toggleOwner')->name('owners.toggle');
        });
    });
});

// --- AUTHENTICATED USERS (Common) ---
Route::middleware('auth')->group(function () {
    Route::get('/', [Controller::class, 'index'])->name('dashboard');

    // Profile Routes
    Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::patch('/', 'update')->name('update');
        Route::delete('/', 'destroy')->name('destroy');
    });

    // Boshqa hisobdan chiqish. `auth` guruhida, chunki chaqiruvchi o'sha paytda
    // talaba/o'qituvchi sifatida kirgan bo'ladi — admin roli yo'q.
    Route::post('impersonate/stop', [ImpersonationController::class, 'stop'])
        ->name('impersonate.stop');

    // Sertifikatni yuklab olish. Rol bo'yicha emas, EGALIK bo'yicha tekshiriladi
    // (CertificateController::authorizeAccess): talaba faqat o'zinikini,
    // ota-ona faqat farzandinikini, o'qituvchi faqat o'z guruhidagini oladi.
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])
        ->whereNumber('certificate')
        ->name('certificates.download');
});

/*
|--------------------------------------------------------------------------
| TEACHER ROUTES
|--------------------------------------------------------------------------
| `verified.role` is inert unless REQUIRE_EMAIL_VERIFICATION=true, and it never
| blocks an account that has no e-mail address. It is deliberately NOT on the
| plain `auth` group above: that group owns the dashboard and profile, and
| profile is the only screen where someone can correct a wrong address.
*/
Route::middleware(['auth', 'role:user', 'verified.role'])->group(function () {
    Route::controller(TeacherAdminPanel::class)->group(function () {
        Route::get('teacher/groups', 'groups')->name('teacher.groups');
        Route::get('teacher/attendance', 'attendanceGroups')->name('attendance');
        Route::get('teacher/assessment/groups', 'assessmentGroups')->name('assessment.teacher.groups');
        Route::post('teacher/student/{id}/comment', 'studentComment')->name('teacher.student.comment');
    });

    // O'qituvchilar davomatni tekshirishi va yuborishi mumkin
    Route::post('attendance/submit/{id}', [TeacherAdminPanel::class, 'attendance_submit'])->name('attendance.submit');
    // whereNumber: aks holda bu marshrut talabalarning `attendance/lists`
    // sahifasini ushlab qolib, 403 qaytaradi.
    Route::get('attendance/{id}', [TeacherAdminPanel::class, 'attendance'])
        ->whereNumber('id')
        ->name('attendance.check');

    // --- UY VAZIFALARI (o'qituvchi) ---
    Route::controller(HomeworkController::class)->prefix('teacher/homework')->name('homework.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{homework}', 'show')->name('show');
        Route::get('{homework}/edit', 'edit')->name('edit');
        Route::put('{homework}', 'update')->name('update');
        Route::delete('{homework}', 'destroy')->name('destroy');
        Route::get('{homework}/grade', 'grade')->name('grade');
        Route::post('{homework}/grade', 'storeGrades')->name('grade.store');
    });

    // --- KO'NIKMA BAHOLARI (reading / listening / writing / speaking) ---
    Route::controller(LessonSkillGradeController::class)->prefix('teacher/skills')->name('skills.')->group(function () {
        Route::get('/', 'groups')->name('groups');
        Route::get('{group}', 'grade')->whereNumber('group')->name('grade');
        Route::post('{group}', 'store')->whereNumber('group')->name('store');
    });
});
Route::delete('attendance/delete/{id}', [ExtraTeacherController::class, 'attendanceDelete'])
    ->middleware('auth', 'role:user|admin')
    ->name('attendance.delete');

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {

    // --- MARKAZ SOZLAMALARI ---
    // Super-admin panelidan farqi: u yerda platforma egasi markazning o'zini
    // boshqaradi, bu yerda esa markaz admini o'quv jarayoniga oid qarorni.
    Route::controller(CentreSettingsController::class)->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

    // --- TEST ROUTES (TUZATILDI) ---
    Route::controller(TestResultController::class)->prefix('Test')->group(function () {
        Route::get('/', 'index')->name('test');
        Route::get('/{id}/show', 'showResults')->name('test.show');
        Route::delete('/{id}/delete', 'destroyAll')->name('test.destroy.all');
    });

    // --- PDF REPORTS ---
    Route::controller(PdfController::class)->group(function () {
        Route::get('/student/pdf/{id}', 'history');
        Route::get('/dept/pdf', 'RoomListPDF');
        Route::get('/assessment/pdf/{date}', 'Assessment');
        Route::get('/teacher/pdf', 'teacher');
        Route::get('/group/pdf', 'group');
        Route::get('/student/pdf-list', 'student');
    });

    // (Excel export lives in the shared admin|user block — teachers export their
    //  own groups too, and GroupExtraController@export scopes it with
    //  assertTeachesGroup(), so the wider role gate does not widen access.)

    // --- GROUPS & WAITERS ---
    // `show` is excluded: GroupController@show only redirects to the index, and
    // `group/{id}/room` was removed entirely — GroupController::makeGroup has
    // never existed, so hitting that URL was a guaranteed 500.
    Route::resource('group', GroupController::class)->except(['show']);

    Route::controller(GroupExtraController::class)->group(function () {
        Route::delete('/delete-multiple', 'deleteMultiple')->name('deleteMultiple');
        Route::post('group/change/{id}', 'change_group')->name('student.change.group');
        Route::get('group/student/{id}', 'show')->name('group.students');
    });

    Route::get('waiters', [WaitersController::class, 'index'])->name('waiters.index');

    // --- STUDENTS & DEPT ---
    // Removed 'show' from resource to define it separately for shared access
    Route::resource('student', StudentController::class)->except(['show']);
    Route::post('student/dept', [Controller::class, 'search'])->name('student.search');

    // --- STUDENT EXPORT ROUTES ---
    Route::get('student/{id}/export', [StudentController::class, 'exportStudent'])->name('student.export');
    Route::get('students/export/all', [StudentController::class, 'exportAllStudents'])->name('students.export.all');

    // --- BOSHQA HISOBGA KIRISH (faqat admin boshlay oladi) ---
    Route::post('impersonate/{user}', [ImpersonationController::class, 'start'])
        ->whereNumber('user')
        ->name('impersonate.start');

    // --- GURUHNI YAKUNLASH / QAYTA OCHISH ---
    Route::controller(GroupLifecycleController::class)->prefix('group')->name('group.')->group(function () {
        Route::post('{group}/finish', 'finish')->whereNumber('group')->name('finish');
        Route::post('{group}/reopen', 'reopen')->whereNumber('group')->name('reopen');
    });

    // --- SMS SHABLONLARI ---
    Route::post('sms-templates/{sms_template}/toggle', [SmsTemplateController::class, 'toggle'])
        ->whereNumber('sms_template')
        ->name('sms-templates.toggle');
    Route::resource('sms-templates', SmsTemplateController::class)
        ->parameters(['sms-templates' => 'sms_template'])
        ->except(['show']);

    // --- SERTIFIKATLAR (bitiruv) ---
    Route::controller(CertificateController::class)->prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::delete('{certificate}', 'destroy')->whereNumber('certificate')->name('destroy');
    });

    // --- PARENTS (guardian accounts) ---
    Route::post('parents/backfill', [ParentAdminController::class, 'backfill'])->name('parents.backfill');
    Route::resource('parents', ParentAdminController::class)->except(['show']);

    Route::resource('dept', DeptStudentController::class);
    Route::get('payment-receipt/{paymentId}', [DeptStudentController::class, 'showReceipt'])->name('payment.receipt');
    Route::get('refresh/{id}/update', [RefreshController::class, 'update'])->name('refresh.update');

    // --- TEACHERS ---
    // Declared BEFORE Route::resource('teacher') so the literal `teacher/group`
    // segment is never captured by the resource's {teacher} placeholder.
    Route::controller(TeacherGroupController::class)->prefix('teacher/group')->name('teacher_group.')->group(function () {
        Route::delete('delete/{id}', 'detach')->whereNumber('id')->name('delete');
        Route::put('{teacher}/store', 'attach')->name('store');
    });
    Route::resource('teacher', TeacherController::class);

    // --- DAVOMAT (admin ko'rinishi) ---
    Route::controller(AttendanceAdminController::class)->prefix('admin/attendance')->name('attendance.')->group(function () {
        Route::get('/', 'overview')->name('overview');
        Route::get('log', 'log')->name('log');
    });

    // --- UY VAZIFALARI (admin ko'rinishi) ---
    Route::get('admin/homework', [HomeworkController::class, 'adminIndex'])->name('homework.admin.index');

    // --- FINANCE ---
    Route::controller(FinanceController::class)->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', 'index')->name('other');
        Route::post('/store', 'store')->name('store');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| SHARED ROUTES (Admin || User)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin|user'])->group(function () {
    Route::get('group/attendance/{id}', [GroupExtraController::class, 'attendance'])->name('group.attendance');

    // --- EXCEL EXPORT (davomat) ---
    Route::get('export-attendances/{id}', [GroupExtraController::class, 'export'])->name('export.attendances');

    // --- O'ZLASHTIRISH DINAMIKASI ---
    Route::controller(ProgressController::class)->prefix('progress')->name('progress.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('group/{group}', 'group')->whereNumber('group')->name('group');
        Route::get('student/{student}', 'student')->whereNumber('student')->name('student');
    });

    // --- KO'NIKMA HISOBOTI ---
    Route::get('skills/group/{group}', [LessonSkillGradeController::class, 'report'])
        ->whereNumber('group')
        ->name('skills.report');

    // --- TALABANI GURUHDAN GURUHGA KO'CHIRISH ---
    // MUST stay above `student/{student}` or the literal segment is swallowed.
    Route::controller(StudentTransferController::class)->group(function () {
        Route::get('student/{student}/transfer', 'create')->whereNumber('student')->name('student.transfer.form');
        Route::post('student/{student}/transfer', 'store')->whereNumber('student')->name('student.transfer');
    });

    // --- OTA-ONAGA SMS (kimga yuborish tanlanadi) ---
    Route::post('student/{student}/sms', [StudentSmsController::class, 'store'])
        ->whereNumber('student')
        ->name('student.sms');

    // Allow both admin and teacher (user) to view student details
    Route::get('student/{student}', [StudentController::class, 'show'])->name('student.show');
});


/*
|--------------------------------------------------------------------------
| SHARED ROUTES (Student || Admin || User)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:student|admin|user'])->group(function () {
    // AssessmentController only implements index/show/update. The other four
    // resource verbs pointed at methods that do not exist, so those URLs were a
    // guaranteed 500; excluded so they 404 instead.
    // NOTE: {assessment} here is a GROUP id, not an assessment id — do not add
    // route-model binding, it would change the meaning of every existing link.
    Route::resource('assessment', AssessmentController::class)
        ->only(['index', 'show', 'update']);
});

/*
|--------------------------------------------------------------------------
| STUDENT ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:student', 'verified.role'])->group(function () {
    // Studentlarga tegishli marshrutlar
    // Masalan, o'z test natijalarini ko'rish, davomatini ko'rish
    Route::controller(TeacherAdminPanel::class)->group(function () {
        Route::get('attendance/lists', 'attendanceIndex')->name('attendance.index');
        Route::get('groups', 'group')->name('student.attendance');
    });

    /*
    | Everything below is prefixed `my/` on purpose: a bare `homework` or
    | `progress` segment would collide with the shared/admin routes above.
    */
    Route::prefix('my')->name('student.')->group(function () {
        Route::get('certificates', [CertificateController::class, 'mine'])->name('certificates');
        Route::get('homework', [HomeworkController::class, 'studentIndex'])->name('homework');
        Route::get('homework/{homework}', [HomeworkController::class, 'studentShow'])->name('homework.show');
        Route::post('homework/{homework}', [HomeworkController::class, 'submit'])->name('homework.submit');
        Route::get('skills', [LessonSkillGradeController::class, 'studentIndex'])->name('skills');
        Route::get('progress', [ProgressController::class, 'mine'])->name('progress');
    });
});

/*
|--------------------------------------------------------------------------
| PARENT (OTA-ONA) ROUTES
|--------------------------------------------------------------------------
| Read-only portal. Every action is scoped to the children attached to the
| signed-in guardian via the `parent_student` table.
*/
Route::middleware(['auth', 'role:parent'])->group(function () {
    Route::controller(ParentPanelController::class)->prefix('parent')->name('parent.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('attendance', 'attendance')->name('attendance');
        Route::get('payments', 'payments')->name('payments');
        Route::get('child/{id}', 'child')->name('child');
    });
});
