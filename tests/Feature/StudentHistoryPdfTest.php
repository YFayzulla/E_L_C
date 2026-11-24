<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Centre;
use App\Models\Group;
use App\Models\HistoryPayments;
use App\Models\LessonAndHistory;
use App\Models\StudentInformation;
use App\Models\User;
use App\Services\CentreMembershipService;
use App\Services\DiscountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Talaba tarixi PDF'i sahifadagi bo'limlarni takrorlaydi: ma'lumotlar,
 * izohlar, chegirma, o'zlashtirish, to'lovlar, guruh tarixi, testlar, davomat.
 */
class StudentHistoryPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_history_pdf_contains_every_section(): void
    {
        Cache::flush();

        foreach (['admin', 'user', 'student', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first() ?? new Centre();
        $centre->forceFill(['slug' => 'alpha', 'name' => 'Alpha', 'status' => Centre::STATUS_ACTIVE])->save();

        $person = function (string $name, string $phone, string $role) use ($centre): User {
            $user = new User();
            $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret')])->save();
            app(CentreMembershipService::class)->attach($centre, $user, $role);

            return $user;
        };

        [$admin, $student] = Centre::for($centre, function () use ($person) {
            $admin = $person('Admin', '998900000090', 'admin');
            $group = Group::create(['name' => 'Matematika Kids', 'monthly_payment' => 500000]);
            $student = $person('Ravshanbekova Yasmina', '998930000091', 'student');
            $student->forceFill([
                'location'    => 'Urganch',
                'mark'        => 15,
                'description' => "05 Mar 2026 (Ustoz - Matematika Kids): Mantiqiy fikrlash uyga vazifani ishlamagan\n31 Mar 2026 (Ustoz): Uyga vazifani ishlab kelgan",
            ])->save();
            $student->groups()->attach($group->id, ['payment' => 500000, 'base_payment' => 500000]);

            StudentInformation::create(['user_id' => $student->id, 'group_id' => $group->id, 'group' => 'Matematika Kids']);
            HistoryPayments::create([
                'user_id' => $student->id, 'name' => $student->name, 'payment' => 500000,
                'group' => 'Matematika Kids', 'date' => '2026-03-10', 'type_of_money' => 'electronic', 'description' => 'ingliz matem',
            ]);
            $history = LessonAndHistory::create(['name' => 'Aprel oyi yakuniy testi', 'data' => 2, 'group' => $group->id]);
            Assessment::create(['user_id' => $student->id, 'get_mark' => 15, 'skill' => 'grammar', 'group' => 'Matematika Kids', 'history_id' => $history->id]);
            Attendance::create(['user_id' => $student->id, 'group_id' => $group->id, 'who_checked' => $admin->id, 'status' => 0, 'excused' => true, 'reason' => 'Kasal bo‘lgan']);
            app(DiscountService::class)->give($student, 20, $group->id, today(), null, 'Aka-uka');

            return [$admin, $student];
        });

        // Haqiqiy wrapper mock'dan oldin olinadi: HTML tekshiriladi, PDF baribir chiqadi.
        $real = app('dompdf.wrapper');
        $html = null;
        Pdf::shouldReceive('loadView')->once()->andReturnUsing(function ($view, $data) use (&$html, $real) {
            $html = view($view, $data)->render();

            return $real->loadHTML($html);
        });

        $response = $this->actingAs($admin)->get('/student/pdf/' . $student->id);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('student_history_' . $student->id, (string) $response->headers->get('content-disposition'));

        foreach ([
            'Ravshanbekova Yasmina', 'Urganch', 'Matematika Kids',
            'Mantiqiy fikrlash uyga vazifani ishlamagan',          // izoh
            'Chegirmalar', 'Aka-uka', '20%',                        // chegirma
            'O‘zlashtirish dinamikasi',
            'To‘lovlar tarixi', '500 000', 'ingliz matem', '10.03.2026',
            'Guruhlar tarixi',
            'Test natijalari', 'Aprel oyi yakuniy testi', '15/100',
            'Davomat: kelmagan va kechikkan kunlar', 'Sababli', 'Kasal bo‘lgan',
        ] as $text) {
            $this->assertStringContainsString(e($text), $html, "PDF'da yo'q: {$text}");
        }
    }
}
