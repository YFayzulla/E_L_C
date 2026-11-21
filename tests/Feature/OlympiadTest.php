<?php

namespace Tests\Feature;

use App\Models\Centre;
use App\Models\Group;
use App\Models\Olympiad;
use App\Models\OlympiadParticipant;
use App\Models\ReceptionStudent;
use App\Models\StudentDiscount;
use App\Models\User;
use App\Services\CentreMembershipService;
use App\Tenancy\TenantQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Olimpiada: reception ishtirokchi va ball kiritadi; admin qaror, chegirma
 * va guruhga biriktirishni qiladi. Chegirma doimiy, guruhga bog'langan.
 */
class OlympiadTest extends TestCase
{
    use RefreshDatabase;

    private Centre $centre;
    private User $admin;
    private User $reception;
    private Group $group;
    private Group $other;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        foreach (['admin', 'reception', 'user', 'student', 'parent'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->centre = Centre::withoutGlobalScopes()->where('slug', 'alpha')->first() ?? new Centre();
        $this->centre->forceFill(['slug' => 'alpha', 'name' => 'Alpha', 'status' => Centre::STATUS_ACTIVE])->save();

        $this->inCentre(function () {
            $this->group = Group::create(['name' => '26-9 Olimpiada guruhi', 'monthly_payment' => 400000]);
            $this->other = Group::create(['name' => '26-1 Beginner', 'monthly_payment' => 300000]);
            $this->admin = $this->person('Admin', '998900000080', 'admin');
            $this->reception = $this->person('Reception', '998900000081', 'reception');
        });
    }

    private function inCentre(callable $fn)
    {
        return Centre::for($this->centre, $fn);
    }

    private function person(string $name, string $phone, string $role): User
    {
        $user = new User();
        $user->forceFill(['name' => $name, 'phone' => $phone, 'password' => Hash::make('secret')])->save();
        app(CentreMembershipService::class)->attach($this->centre, $user, $role);

        return $user;
    }

    private function olympiad(array $sections = [['key' => 'grammar', 'label' => 'Grammatika', 'max' => 40], ['key' => 'reading', 'label' => 'O‘qish', 'max' => 60]]): Olympiad
    {
        return $this->inCentre(fn () => Olympiad::create([
            'name'     => 'Qishki olimpiada',
            'status'   => Olympiad::STATUS_PLANNED,
            'sections' => $sections,
        ]));
    }

    private function participant(Olympiad $olympiad, array $attributes): OlympiadParticipant
    {
        return $this->inCentre(fn () => $olympiad->participants()->create($attributes));
    }

    public function test_reception_creates_olympiad_adds_participants_and_scores(): void
    {
        $this->actingAs($this->reception)
            ->post(route('olympiads.store'), [
                'name'     => 'Bahorgi olimpiada',
                'held_on'  => '2026-12-01',
                'sections' => [
                    ['key' => '', 'label' => 'Grammatika', 'max' => 50],
                    ['key' => '', 'label' => 'Tinglash', 'max' => 50],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $olympiad = $this->inCentre(fn () => Olympiad::where('name', 'Bahorgi olimpiada')->firstOrFail());
        $this->assertCount(2, $olympiad->sections);
        $this->assertSame(100, $olympiad->maxTotal());
        [$grammar, $listening] = array_column($olympiad->sections, 'key');

        $record = $this->inCentre(fn () => ReceptionStudent::create([
            'name' => 'Reception Bola', 'phone' => '901110001', 'parent_name' => 'Ota', 'parent_phone' => '901110002',
            'status' => ReceptionStudent::STATUS_NEW,
        ]));
        $student = $this->inCentre(function () {
            $s = $this->person('Markaz Talabasi', '998931110003', 'student');
            $s->groups()->attach($this->other->id, ['payment' => 300000, 'base_payment' => 300000]);

            return $s;
        });

        $this->actingAs($this->reception)
            ->post(route('olympiads.participants.store', $olympiad), ['name' => 'Yangi Bola', 'phone' => '901110004'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->reception)
            ->post(route('olympiads.participants.store', $olympiad), ['name' => 'Toʻrtinchi', 'phone' => '901110005'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->reception)
            ->post(route('olympiads.participants.reception', $olympiad), ['reception_ids' => [$record->id]])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->reception)
            ->post(route('olympiads.participants.students', $olympiad), ['student_ids' => [$student->id]])
            ->assertSessionHasNoErrors();

        $byName = $this->inCentre(fn () => $olympiad->participants()->get()->keyBy('name'));
        $this->assertCount(4, $byName);
        $this->assertSame($record->id, $byName['Reception Bola']->reception_student_id);
        $this->assertSame($student->id, $byName['Markaz Talabasi']->user_id);

        // Max dan oshgan ball rad etiladi.
        $this->actingAs($this->reception)
            ->post(route('olympiads.scores', $olympiad), ['scores' => [$byName['Yangi Bola']->id => [$grammar => 51]]])
            ->assertSessionHasErrors();

        // 90%, 80%, 80%, 50% → o'rinlar 1, 2, 2, 4.
        $this->actingAs($this->reception)
            ->post(route('olympiads.scores', $olympiad), ['scores' => [
                $byName['Yangi Bola']->id       => [$grammar => 45, $listening => 45],
                $byName['Reception Bola']->id   => [$grammar => 40, $listening => 40],
                $byName['Markaz Talabasi']->id  => [$grammar => 50, $listening => 30],
                $byName['Toʻrtinchi']->id       => [$grammar => 50, $listening => ''],
            ]])
            ->assertSessionHasNoErrors();

        $ranks = $this->inCentre(fn () => $olympiad->participants()->pluck('rank', 'name')->all());
        $this->assertSame(1, $ranks['Yangi Bola']);
        $this->assertSame(2, $ranks['Reception Bola']);
        $this->assertSame(2, $ranks['Markaz Talabasi']);
        $this->assertSame(4, $ranks['Toʻrtinchi']);
        $this->assertSame(Olympiad::STATUS_HELD, $this->inCentre(fn () => $olympiad->fresh()->status));

        // Reception sahifani ko'radi, lekin admin bo'limlarisiz.
        $this->actingAs($this->reception)
            ->get(route('olympiads.show', $olympiad))
            ->assertOk()
            ->assertSee('Ballarni saqlash')
            ->assertDontSee('Qarorlarni saqlash')
            ->assertDontSee('Chegirma qoidalari')
            ->assertDontSee('Guruhga biriktirish');

        $this->actingAs($this->reception)->get(route('olympiads.index'))->assertOk()->assertSee('Bahorgi olimpiada');
        $this->actingAs($this->reception)->get(route('olympiads.edit', $olympiad))->assertOk();
    }

    public function test_reception_cannot_decide_discounts_or_enroll(): void
    {
        $olympiad = $this->olympiad();
        $p = $this->participant($olympiad, ['name' => 'Bola', 'phone' => '901110010']);

        $this->actingAs($this->reception)
            ->post(route('olympiads.rules', $olympiad), ['rules' => [['from' => 1, 'to' => 3, 'percent' => 50]], 'apply' => 1])
            ->assertForbidden();
        $this->actingAs($this->reception)
            ->post(route('olympiads.decisions', $olympiad), ['decisions' => [$p->id => ['status' => 'accepted', 'discount' => 100]]])
            ->assertForbidden();
        $this->actingAs($this->reception)
            ->post(route('olympiads.enroll', $olympiad), ['participant_ids' => [$p->id], 'group_id' => $this->group->id])
            ->assertForbidden();
        $this->actingAs($this->reception)
            ->delete(route('olympiads.destroy', $olympiad))
            ->assertForbidden();

        $fresh = $this->inCentre(fn () => $p->fresh());
        $this->assertSame(OlympiadParticipant::STATUS_PENDING, $fresh->status);
        $this->assertNull($fresh->discount_percent);
    }

    public function test_rules_suggest_discount_and_admin_can_override(): void
    {
        $olympiad = $this->olympiad();
        $first = $this->participant($olympiad, ['name' => 'Birinchi', 'scores' => ['grammar' => 40, 'reading' => 60]]);
        $second = $this->participant($olympiad, ['name' => 'Ikkinchi', 'scores' => ['grammar' => 30, 'reading' => 50]]);
        $fifth = $this->participant($olympiad, ['name' => 'Oxirgi', 'scores' => ['grammar' => 5, 'reading' => 5]]);
        $this->inCentre(fn () => app(\App\Services\OlympiadService::class)->recalculate($olympiad));

        $this->actingAs($this->admin)
            ->post(route('olympiads.rules', $olympiad), [
                'rules' => [
                    ['from' => 2, 'to' => 2, 'percent' => 50],
                    ['from' => 1, 'to' => 1, 'percent' => 100],
                    ['from' => '', 'to' => '', 'percent' => ''],
                ],
                'apply' => 1,
            ])
            ->assertSessionHasNoErrors();

        $olympiad = $this->inCentre(fn () => $olympiad->fresh());
        $this->assertSame([['from' => 1, 'to' => 1, 'percent' => 100], ['from' => 2, 'to' => 2, 'percent' => 50]], $olympiad->rules);
        $this->assertSame(100, $this->inCentre(fn () => $first->fresh()->discount_percent));
        $this->assertSame(50, $this->inCentre(fn () => $second->fresh()->discount_percent));
        $this->assertNull($this->inCentre(fn () => $fifth->fresh()->discount_percent));

        // Qo'lda o'zgartirish: ikkinchiga 30%, oxirgisi rad etildi.
        $this->actingAs($this->admin)
            ->post(route('olympiads.decisions', $olympiad), ['decisions' => [
                $first->id  => ['status' => 'accepted', 'discount' => 100],
                $second->id => ['status' => 'accepted', 'discount' => 30],
                $fifth->id  => ['status' => 'rejected', 'discount' => ''],
            ]])
            ->assertSessionHasNoErrors();

        $this->assertSame(30, $this->inCentre(fn () => $second->fresh()->discount_percent));
        $this->assertSame(OlympiadParticipant::STATUS_REJECTED, $this->inCentre(fn () => $fifth->fresh()->status));

        $this->actingAs($this->admin)
            ->get(route('olympiads.show', $olympiad))
            ->assertOk()
            ->assertSee('Chegirma qoidalari')
            ->assertSee('tavsiya: 50%');
    }

    public function test_admin_enrolls_new_participant_with_permanent_group_discount(): void
    {
        $olympiad = $this->olympiad();
        $record = $this->inCentre(fn () => ReceptionStudent::create([
            'name' => 'Olim Bola', 'phone' => '901110020', 'status' => ReceptionStudent::STATUS_NEW,
        ]));
        $p = $this->participant($olympiad, [
            'name' => 'Olim Bola', 'phone' => '+998 90 111 00 20', 'parent_name' => 'Ota', 'parent_phone' => '901110021',
            'reception_student_id' => $record->id,
            'scores' => ['grammar' => 40, 'reading' => 55], 'rank' => 1, 'status' => 'accepted', 'discount_percent' => 25,
        ]);

        $this->actingAs($this->admin)
            ->post(route('olympiads.enroll', $olympiad), ['participant_ids' => [$p->id], 'group_id' => $this->group->id, 'parent_account' => 1])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        $p = $this->inCentre(fn () => $p->fresh());
        $this->assertTrue($p->isEnrolled());
        $this->assertSame($this->group->id, (int) $p->group_id);

        $student = $this->inCentre(fn () => User::findOrFail($p->user_id));
        $this->assertSame('998901110020', (string) $student->phone);
        $this->assertTrue($this->inCentre(fn () => $student->hasRole('student')));

        $discount = $this->inCentre(fn () => StudentDiscount::findOrFail($p->discount_id));
        $this->assertSame(25, $discount->percent);
        $this->assertSame($this->group->id, (int) $discount->group_id);
        $this->assertNull($discount->ends_on);
        $this->assertSame('Olimpiada: Qishki olimpiada, 1-o‘rin', $discount->reason);

        $payment = $this->inCentre(fn () => (int) TenantQuery::table('group_user')
            ->where('user_id', $student->id)->where('group_id', $this->group->id)->value('payment'));
        $this->assertSame(300000, $payment);

        $this->assertSame($student->id, (int) $this->inCentre(fn () => $record->fresh()->student_id));
        $this->assertSame(ReceptionStudent::STATUS_ASSIGNED, $this->inCentre(fn () => $record->fresh()->status));

        // Ikkinchi marta biriktirib bo'lmaydi.
        $this->actingAs($this->admin)
            ->post(route('olympiads.enroll', $olympiad), ['participant_ids' => [$p->id], 'group_id' => $this->group->id])
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->get(route('olympiads.show', $olympiad))
            ->assertOk()
            ->assertSee('Biriktirildi')
            ->assertSee('26-9 Olimpiada guruhi');
    }

    public function test_existing_student_is_added_to_group_and_bad_rows_report_errors(): void
    {
        $olympiad = $this->olympiad();
        $student = $this->inCentre(function () {
            $s = $this->person('Eski Talaba', '998931110030', 'student');
            $s->groups()->attach($this->other->id, ['payment' => 300000, 'base_payment' => 300000]);
            $s->forceFill(['should_pay' => 300000])->save();
            $s->deptStudent()->create(['dept' => 300000, 'payed' => 0, 'status_month' => 0]);

            return $s;
        });

        $existing = $this->participant($olympiad, ['name' => 'Eski Talaba', 'user_id' => $student->id, 'status' => 'accepted', 'rank' => 2, 'discount_percent' => 50]);
        $noPhone = $this->participant($olympiad, ['name' => 'Telefonsiz', 'status' => 'accepted']);
        $pending = $this->participant($olympiad, ['name' => 'Kutilayotgan', 'phone' => '901110031']);

        $this->actingAs($this->admin)
            ->post(route('olympiads.enroll', $olympiad), [
                'participant_ids' => [$existing->id, $noPhone->id, $pending->id],
                'group_id'        => $this->group->id,
            ])
            ->assertSessionHas('success')
            ->assertSessionHas('error');

        $groups = $this->inCentre(fn () => $student->fresh()->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->sort()->values()->all());
        $this->assertSame(collect([$this->group->id, $this->other->id])->sort()->values()->all(), $groups);

        $payment = $this->inCentre(fn () => (int) TenantQuery::table('group_user')
            ->where('user_id', $student->id)->where('group_id', $this->group->id)->value('payment'));
        $this->assertSame(200000, $payment);

        $this->assertTrue($this->inCentre(fn () => $existing->fresh()->isEnrolled()));
        $this->assertFalse($this->inCentre(fn () => $noPhone->fresh()->isEnrolled()));
        $this->assertFalse($this->inCentre(fn () => $pending->fresh()->isEnrolled()));

        $error = session('error');
        $this->assertStringContainsString('Telefonsiz', $error);
        $this->assertStringContainsString('Kutilayotgan', $error);

        // Biriktirilgan ishtirokchini olib tashlab bo'lmaydi, olimpiadani ham.
        $this->actingAs($this->admin)->delete(route('olympiads.participants.destroy', [$olympiad, $existing->id]))->assertSessionHas('error');
        $this->actingAs($this->admin)->delete(route('olympiads.destroy', $olympiad))->assertSessionHas('error');
        $this->assertNotNull($this->inCentre(fn () => $existing->fresh()));
    }

    public function test_pdf_report_downloads(): void
    {
        $olympiad = $this->olympiad();
        $this->participant($olympiad, ['name' => 'PDF Bola', 'scores' => ['grammar' => 30, 'reading' => 30], 'total' => 60, 'percent' => 60, 'rank' => 1]);

        $response = $this->actingAs($this->admin)->get(route('olympiads.pdf', $olympiad));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }
}
