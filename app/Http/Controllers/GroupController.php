<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    public function index()
    {
        try {
            $groups = Group::where('id', '!=', 1) // Assuming 1 is the "Unassigned" group
            ->orderBy('name')
                ->get(); // Added pagination
            return view('admin.group.index', compact('groups'));
        } catch (\Exception $e) {
            Log::error('GroupController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhlarni yuklashda xatolik yuz berdi.');
        }
    }

    public function create()
    {
        return view('admin.group.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'nullable|string|max:255',
            'finish_time' => 'nullable|string|max:255',
            'monthly_payment' => 'required|numeric|min:0',
        ]);

        try {
            $group = Group::create([
                'name' => $request->name,
                'start_time' => $request->start_time,
                'finish_time' => $request->finish_time,
                'monthly_payment' => (int)$request->monthly_payment,
            ]);

            if (method_exists($group, 'hasTeacher')) {
                $teacherId = $group->hasTeacher();
                if ($teacherId) {
                    GroupTeacher::create([
                        'group_id' => $group->id,
                        'teacher_id' => $teacherId,
                    ]);
                }
            }

            return redirect()->route('group.index')
                ->with('success', 'Guruh muvaffaqiyatli qo\'shildi va o\'qituvchiga biriktirildi.');

        } catch (\Exception $e) {
            Log::error('GroupController@store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Guruhni saqlashda xatolik yuz berdi.');
        }
    }

    public function show($id)
    {
        // This method seems redundant if index shows all groups, but keeping it for compatibility if needed
        // Or maybe it's used to show a specific group details?
        // Based on previous code, it was showing groups for a room.
        // Now we might want to show details of a single group or just redirect to index.

        // If the intention is to show details of a specific group:
        try {
            $group = Group::findOrFail($id);
            // You might want a specific view for showing group details
            // For now, let's just return the index view with all groups, or maybe filter?
            // But usually show($id) is for a single resource.

            // Let's assume we want to list groups, similar to index.
            // If the previous logic was listing groups in a room, now we list all groups.
            return redirect()->route('group.index');

        } catch (\Exception $e) {
            return redirect()->route('group.index');
        }
    }

    public function edit(Group $group)
    {
        return view('admin.group.edit', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'nullable|string|max:255',
            'finish_time' => 'nullable|string|max:255',
            'monthly_payment' => 'required|numeric|min:0',
        ]);

        try {
            $group->update($request->all());

            return redirect()->route('group.index')->with('success', 'Ma\'lumotlar muvaffaqiyatli yangilandi.');

        } catch (\Exception $e) {
            Log::error('GroupController@update error: ' . $e->getMessage());
            return redirect()->route('group.index')->with('error', 'Yangilashda xatolik yuz berdi.');
        }
    }

    public function destroy(Group $group)
    {
        DB::beginTransaction();
        try {
            // 1. O'qituvchi bog'lanishini o'chirish (Pivot jadvaldan)
            // This removes the relationship but keeps the teacher user
            GroupTeacher::where('group_id', $group->id)->delete();

            // 2. Talabalar bog'lanishini o'chirish (Pivot jadvaldan)
            // detach() metodi pivot jadvaldan (group_user) yozuvlarni o'chiradi
            // This removes the relationship but keeps the student user
            $group->students()->detach();

            // 3. Guruhni o'chirish
            $group->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Guruh muvaffaqiyatli o\'chirildi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GroupController@destroy error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhni o\'chirishda xatolik yuz berdi.');
        }
    }
}
