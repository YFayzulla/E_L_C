<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Http\Requests\StudentTransferRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\StudentGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Moving a student between groups.
 *
 * Membership carries money — group_user.payment feeds the teacher's salary, and
 * users.should_pay / dept_students.dept drive the debt screens — so every write
 * goes through App\Services\StudentGroupService, never through a bare sync().
 *
 * A teacher may only move a student between groups they themselves teach; an
 * admin may move anywhere.
 */
class StudentTransferController extends Controller
{
    use AuthorizesGroupAccess;

    public function __construct(private StudentGroupService $membership)
    {
    }

    public function create(Request $request, int $student)
    {
        $this->assertTeachesStudent($student);

        $model = User::inCurrentCentre()->with('groups')->findOrFail($student);

        abort_unless($model->hasRole('student'), 404, 'Talaba topilmadi.');

        try {
            $isAdmin = auth()->user()->hasRole('admin');
            $waitingRoomId = Group::waitingRoomId();

            // Markazning BARCHA guruhlari — o'qituvchiga ham, adminga ham.
            //
            // Ilgari o'qituvchi faqat o'zi dars beradigan guruhlarni ko'rardi,
            // ya'ni talabani hamkasbining guruhiga o'tkaza olmasdi va buning
            // uchun har safar administratorni chaqirishga majbur edi. Global
            // scope ro'yxatni baribir shu markaz bilan cheklaydi.
            $targets = Group::query()
                ->with('teachers:id,name')
                ->withCount(['students as members_count'])
                // Kutish zali sorts last: it is a holding pen, not a destination.
                ->orderByRaw('CASE WHEN id = ? THEN 1 ELSE 0 END, name', [$waitingRoomId])
                ->get();

            $selectableIds = $targets->pluck('id')->map(fn ($id) => (int) $id)->all();

            // Hamma guruh tanlanadigan bo'lgach, "qulflangan a'zolik" degan
            // tushuncha yo'qoldi: forma talabaning haqiqiy holatini to'liq
            // ko'rsatadi va yuborilgani ayni o'sha holatga aylanadi.
            $lockedGroups = $model->groups->reject(
                fn ($group) => in_array((int) $group->id, $selectableIds, true)
            );

            $currentIds = $model->groups->pluck('id')->map(fn ($id) => (int) $id)->all();

            return view('student.transfer', [
                'student' => $model,
                'targets' => $targets,
                'currentIds' => $currentIds,
                'selectableIds' => $selectableIds,
                'lockedIds' => $lockedGroups->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'lockedGroups' => $lockedGroups,
                'isAdmin' => $isAdmin,
                'waitingRoomId' => $waitingRoomId,
            ]);
        } catch (\Exception $e) {
            Log::error('StudentTransferController@create error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Ko‘chirish sahifasini yuklashda xatolik yuz berdi.');
        }
    }

    public function store(StudentTransferRequest $request, int $student)
    {
        $this->assertTeachesStudent($student);

        $model = User::inCurrentCentre()->findOrFail($student);

        abort_unless($model->hasRole('student'), 404, 'Talaba topilmadi.');

        // O'qituvchi endi markazning istalgan guruhiga ko'chira oladi, shu
        // jumladan hamkasbinikiga ham — bu ataylab, chunki formada ham
        // hammasi ko'rinadi.
        //
        // Qolgan yagona chegara — yuqoridagi assertTeachesStudent(): o'qituvchi
        // faqat O'ZI dars beradigan talabani ko'chira oladi. Guruhlar
        // ro'yxatini esa global scope markaz bilan cheklab turadi, ya'ni
        // boshqa markazning guruhi bu yerga tusha olmaydi.
        //
        // Rolga bog'liq "saqlab qolish" mantiqi olib tashlandi: forma haqiqiy
        // holatni to'liq ko'rsatgach, yuborilgan ro'yxat ayni o'sha holatga
        // aylanishi kerak. Aks holda belgisi olingan guruh jimgina qaytib
        // qo'shilardi.
        $targets = $request->targetGroupIds();

        // Ammo formada UMUMAN ko'rinmagan a'zolik saqlanadi. Bu odatda bo'sh:
        // ro'yxatda markazning barcha guruhi bor. Faqat talaba ro'yxatga
        // tushmaydigan guruhga (masalan o'chirilganiga) a'zo bo'lib qolgan
        // bo'lsa ishlaydi — yuborilgan ma'lumot uni "olib tashlandi" deb
        // talqin qilinmasligi kerak, chunki o'qituvchi uni ko'rmagan ham.
        $visible = Group::query()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $invisible = array_diff(
            $model->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->all(),
            $visible
        );

        if ($invisible !== []) {
            $targets = array_values(array_unique(array_merge($targets, $invisible)));
        }

        try {
            $change = $this->membership->preview($model, $targets);

            $this->membership->transfer($model, $targets, $request->payments(), auth()->id());
        } catch (\Exception $e) {
            Log::error('StudentTransferController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()
                ->with('error', 'Talabani ko‘chirishda xatolik yuz berdi. O‘zgarishlar saqlanmadi.');
        }

        return redirect()->to($this->destinationAfter($model))
            ->with('success', $this->summary($model, $change));
    }

    /**
     * Ko'chirishdan keyin qayerga qaytish.
     *
     * O'qituvchi talabani o'zi dars bermaydigan guruhga o'tkazishi mumkin —
     * va shu zahoti unga kirish huquqini yo'qotadi. Talaba sahifasiga
     * qaytarish o'sha holatda 403 berardi: ko'chirish muvaffaqiyatli
     * o'tgan bo'lsa ham, foydalanuvchi xato ko'rardi.
     */
    private function destinationAfter(User $model): string
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return route('student.show', $model->id);
        }

        // Hamon o'qitayotgan bo'lsa — talaba sahifasi ochiladi.
        $stillTeaches = $model->groups()
            ->whereIn('groups.id', $this->accessibleGroupIds())
            ->exists();

        return $stillTeaches
            ? route('student.show', $model->id)
            : route('attendance');
    }

    /**
     * Uzbek flash naming the groups joined and left.
     *
     * @param  array{joined: array<int, int>, left: array<int, int>}  $change
     */
    private function summary(User $model, array $change): string
    {
        $names = Group::whereIn('id', array_merge($change['joined'], $change['left']))
            ->pluck('name', 'id');

        $label = fn (array $ids) => collect($ids)
            ->map(fn ($id) => $names[$id] ?? ('#' . $id))
            ->implode(', ');

        $parts = [];

        if ($change['joined']) {
            $parts[] = 'qo‘shildi — ' . $label($change['joined']);
        }

        if ($change['left']) {
            $parts[] = 'chiqarildi — ' . $label($change['left']);
        }

        if (! $parts) {
            return $model->name . ' guruhlari o‘zgarmadi.';
        }

        return $model->name . ': ' . implode('; ', $parts)
            . '. Oylik to‘lov va qarzdorlik qayta hisoblandi.';
    }
}
