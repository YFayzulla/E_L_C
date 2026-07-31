<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ParentAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Admin-side CRUD for guardian accounts.
 */
class ParentAdminController extends Controller
{
    public function __construct(private ParentAccountService $parents)
    {
    }

    public function index()
    {
        try {
            $parents = User::role('parent')
                ->withCount('children')
                ->with('children:id,name')
                ->orderBy('name')
                ->get();

            return view('admin.parent.index', compact('parents'));
        } catch (\Exception $e) {
            Log::error('ParentAdminController@index error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', "Ota-onalar ro'yxatini yuklashda xatolik.");
        }
    }

    public function create()
    {
        $students = User::role('student')->orderBy('name')->get(['id', 'name', 'phone']);

        return view('admin.parent.create', compact('students'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::beginTransaction();

        try {
            $parent = User::create([
                'name' => $data['name'],
                'phone' => User::normalizePhone($data['phone']),
                'password' => Hash::make($data['password']),
                'location' => $data['location'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $parent->assignRole('parent');
            $parent->children()->sync($this->childPivot($data['children'] ?? []));

            DB::commit();

            return redirect()->route('parents.index')->with('success', "Ota-ona hisobi yaratildi.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ParentAdminController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Saqlashda xatolik yuz berdi.');
        }
    }

    public function edit($id)
    {
        $parent = User::role('parent')->with('children:id,name')->findOrFail($id);
        $students = User::role('student')->orderBy('name')->get(['id', 'name', 'phone']);

        return view('admin.parent.edit', compact('parent', 'students'));
    }

    public function update(Request $request, $id)
    {
        $parent = User::role('parent')->findOrFail($id);
        $data = $this->validated($request, $parent->id);

        DB::beginTransaction();

        try {
            $parent->name = $data['name'];
            $parent->phone = User::normalizePhone($data['phone']);
            $parent->location = $data['location'] ?? null;
            $parent->description = $data['description'] ?? null;

            if (! empty($data['password'])) {
                $parent->password = Hash::make($data['password']);
            }

            $parent->save();
            $parent->children()->sync($this->childPivot($data['children'] ?? []));

            DB::commit();

            return redirect()->route('parents.index')->with('success', "Ota-ona ma'lumotlari yangilandi.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ParentAdminController@update error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Yangilashda xatolik yuz berdi.');
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $parent = User::role('parent')->findOrFail($id);
            $parent->children()->detach();
            $parent->delete();

            DB::commit();

            return redirect()->route('parents.index')->with('success', "Ota-ona hisobi o'chirildi.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ParentAdminController@destroy error: ' . $e->getMessage());

            return redirect()->back()->with('error', "O'chirishda xatolik yuz berdi.");
        }
    }

    /**
     * Walk every student that has guardian details filled in and make sure a
     * matching parent account exists. Lets an existing database catch up in
     * one click instead of re-saving each student by hand.
     */
    public function backfill()
    {
        $created = 0;
        $linked = 0;
        $skipped = 0;

        try {
            User::role('student')
                ->whereNotNull('parents_tel')
                ->where('parents_tel', '!=', '')
                ->chunkById(200, function ($students) use (&$created, &$linked, &$skipped) {
                    foreach ($students as $student) {
                        $before = User::where('phone', User::normalizePhone($student->parents_tel))->exists();

                        $parent = $this->parents->syncForStudent(
                            $student,
                            $student->parents_name,
                            $student->parents_tel
                        );

                        if (! $parent) {
                            $skipped++;
                        } elseif ($before) {
                            $linked++;
                        } else {
                            $created++;
                        }
                    }
                });

            $message = "{$created} ta yangi hisob yaratildi, {$linked} ta mavjud hisobga bog'landi.";

            if ($skipped) {
                $message .= " {$skipped} ta o'tkazib yuborildi (raqam boshqa rolga tegishli).";
            }

            return redirect()->route('parents.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('ParentAdminController@backfill error: ' . $e->getMessage());

            return redirect()->route('parents.index')->with('error', 'Import jarayonida xatolik yuz berdi.');
        }
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        // Phones are stored normalised (998XXXXXXXXX); compare in that form so
        // the unique rule catches "901234567" vs "+998 90 123 45 67".
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')
                    ->ignore($ignoreId)
                    ->where(fn($q) => $q->whereNotNull('phone')),
            ],
            'password' => [$ignoreId ? 'nullable' : 'required', 'string', 'min:4'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'children' => ['nullable', 'array'],
            'children.*' => ['integer', 'exists:users,id'],
        ], [], [
            'name' => 'Ism',
            'phone' => 'Telefon',
            'password' => 'Parol',
            'children' => 'Farzandlar',
        ]);
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, array{relation: null}>
     */
    private function childPivot(array $ids): array
    {
        return collect($ids)
            ->filter()
            ->unique()
            ->mapWithKeys(fn($id) => [(int) $id => ['relation' => null]])
            ->all();
    }
}
