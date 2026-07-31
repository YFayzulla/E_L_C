<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lesson skills
    |--------------------------------------------------------------------------
    | Marks a teacher can give during a lesson. Stored in
    | lesson_skill_grades.skill as a short string (not an ENUM) precisely so
    | this list can grow without another migration.
    */

    'skills' => ['reading', 'listening', 'writing', 'speaking'],

    'skill_labels' => [
        'reading'   => "O‘qish",
        'listening' => 'Tinglash',
        'writing'   => 'Yozish',
        'speaking'  => 'Gapirish',
    ],

    'skill_icons' => [
        'reading'   => 'bx-book-open',
        'listening' => 'bx-headphone',
        'writing'   => 'bx-pencil',
        'speaking'  => 'bx-microphone',
    ],

    /*
    |--------------------------------------------------------------------------
    | Homework
    |--------------------------------------------------------------------------
    */

    'homework_status' => [
        0 => 'Topshirmagan',
        1 => 'Topshirgan',
        2 => 'Kech topshirgan',
    ],

    'homework_upload' => [
        'disk'       => 'public',
        'directory'  => 'homework',
        'max_kb'     => 5120,
        'mimes'      => ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'mp3', 'm4a'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress metric ("o‘zlashtirish ko‘rsatkichi")
    |--------------------------------------------------------------------------
    | A 0-100 score per calendar month. Weights are renormalised over whichever
    | components actually have data in the bucket, so a month with only
    | attendance is not dragged toward zero by three empty signals.
    */

    'progress_months' => 6,

    'progress_weights' => [
        'attendance' => 0.20,
        'tests'      => 0.35,
        'homework'   => 0.20,
        'skills'     => 0.25,
    ],

    // A late arrival costs this fraction of an absence when scoring attendance.
    'late_penalty' => 0.5,

    // Badge thresholds, matching the 80/60 convention already used in the views.
    'bands' => [
        'good' => 80,
        'ok'   => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email verification
    |--------------------------------------------------------------------------
    | When TRUE, students and teachers who have an e-mail address on file must
    | confirm it before they can use the app; they are redirected to the
    | "verify your e-mail" screen until they do.
    |
    | Accounts with NO e-mail address are never blocked — otherwise every
    | existing phone-only account would be locked out the moment this ships.
    | Populate addresses first (Ota-onalar/Talabalar formalari yoki
    | `php artisan users:verify-email --all`), then switch this on.
    */

    'require_email_verification' => env('REQUIRE_EMAIL_VERIFICATION', false),

    // Roles the requirement applies to.
    'verification_roles' => ['student', 'user'],

];
