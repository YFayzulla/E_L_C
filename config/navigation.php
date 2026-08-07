<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page titles
    |--------------------------------------------------------------------------
    |
    | Fallback heading shown in the top bar, keyed by route name. A view can
    | always override it with @section('title') / @section('subtitle'); this
    | map only exists so the older screens get a sensible heading without
    | having to touch every Blade file.
    |
    */

    'titles' => [
        'dashboard' => ['Boshqaruv paneli', null],

        // Teachers
        'teacher.index'  => ["O'qituvchilar", "O'quv markazi o'qituvchilari"],
        'teacher.create' => ["Yangi o'qituvchi", null],
        'teacher.edit'   => ["O'qituvchini tahrirlash", null],
        'teacher.show'   => ["O'qituvchi ma'lumotlari", null],
        'teacher.groups' => ['Mening guruhlarim', null],

        // Groups
        'group.index'      => ['Guruhlar', "Barcha o'quv guruhlari"],
        'group.create'     => ['Yangi guruh', null],
        'group.edit'       => ['Guruhni tahrirlash', null],
        'group.students'   => ['Guruh talabalari', null],
        'group.attendance' => ['Guruh davomati', null],

        // Students
        'student.index'      => ['Talabalar', "Ro'yxatdagi barcha talabalar"],
        'student.create'     => ['Yangi talaba', null],
        'student.edit'       => ['Talabani tahrirlash', null],
        'student.show'       => ["Talaba ma'lumotlari", null],
        'student.attendance' => ['Guruhlarim', null],

        // Attendance
        'attendance'       => ['Davomat', "Guruhni tanlab davomat oling"],
        'attendance.check' => ['Davomat olish', null],
        'attendance.index' => ['Davomatim', "Qoldirilgan va kechikilgan darslar"],

        // Assessment
        'assessment.index'          => ['Oylik test natijalari', null],
        'assessment.show'           => ['Oylik test', null],
        'assessment.create'         => ['Yangi oylik test', null],
        'assessment.edit'           => ['Oylik testni tahrirlash', null],
        'assessment.teacher.groups' => ['Oylik test', "Guruhni tanlang"],
        'test'                      => ['Oylik test', "Test natijalari"],
        'test.show'                 => ['Test natijalari', null],

        // Finance
        'dept.index'      => ["To'lovlar", "Talabalar to'lovlari va qarzdorlik"],
        'dept.create'     => ["Yangi to'lov", null],
        'dept.edit'       => ["To'lovni tahrirlash", null],
        'dept.show'       => ["To'lov ma'lumotlari", null],
        'payment.receipt' => ['Kvitansiya', null],
        'finance.other'   => ['Xarajatlar', "O'quv markazi xarajatlari"],

        // Attendance (admin surface)
        'attendance.overview' => ['Davomat', "Barcha guruhlar bo'yicha oylik holat"],
        'attendance.log'      => ['Davomat jurnali', "Qoldirilgan va kechikilgan darslar"],

        // Homework
        'homework.index'       => ['Uy vazifalari', "Guruhlaringizga berilgan vazifalar"],
        'homework.create'      => ['Yangi uy vazifasi', null],
        'homework.edit'        => ['Uy vazifasini tahrirlash', null],
        'homework.show'        => ['Uy vazifasi', null],
        'homework.grade'       => ['Vazifani tekshirish', "Har bir talabaga baho qo'ying"],
        'homework.admin.index' => ['Uy vazifalari', "Barcha guruhlar bo'yicha"],

        // Skills
        'skills.groups' => ["Ko'nikma baholari", 'Guruhni tanlang'],
        'skills.grade'  => ['Baho qo\'yish', "O'qish · Tinglash · Yozish · Gapirish"],
        'skills.report' => ["Ko'nikma hisoboti", null],

        // Progress
        'progress.index'   => ["O'zlashtirish", 'Guruhni tanlang'],
        'progress.group'   => ["Guruh o'zlashtirishi", null],
        'progress.student' => ["Talaba o'zlashtirishi", null],

        // Transfer
        'student.transfer.form' => ['Guruhni o\'zgartirish', "Talabani boshqa guruhga ko'chirish"],

        // Certificates
        'certificates.index'  => ['Sertifikatlar', 'Bitiruvchilarga berilgan hujjatlar'],
        'certificates.create' => ['Sertifikat berish', null],

        // SMS templates
        'sms-templates.index'  => ['SMS shablonlari', "Ota-onalarga yuboriladigan tayyor matnlar"],
        'sms-templates.create' => ['Yangi SMS shabloni', null],
        'sms-templates.edit'   => ['SMS shablonini tahrirlash', null],

        // Student portal
        'student.certificates'  => ['Sertifikatlarim', "Bitirgan kurslaringiz bo'yicha hujjatlar"],
        'student.homework'      => ['Uy vazifalarim', null],
        'student.homework.show' => ['Uy vazifasi', null],
        'student.skills'        => ["Ko'nikmalarim", "O'qish · Tinglash · Yozish · Gapirish"],
        'student.progress'      => ["O'zlashtirishim", "Oylar bo'yicha dinamika"],

        // Misc
        'teacher.show'          => ["O'qituvchi", null],
        'waiters.index'         => ['Kutish xonasi', "Guruh kutayotgan talabalar"],
        'student.search'        => ["To'lovlar tarixi", null],
        'profile.edit'          => ['Profil', "Hisob ma'lumotlari va xavfsizlik"],
        'verification.notice'   => ['Pochtani tasdiqlash', null],
    ],

];
