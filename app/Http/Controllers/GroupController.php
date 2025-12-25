<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    public function index()
    {
        try {
            $rooms = Room::orderBy('room')->get();
            return view('admin.group.room', compact('rooms'));
        } catch (\Exception $e) {
            Log::error('GroupController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Xonalarni yuklashda xatolik yuz berdi.');
        }
    }

    public function makeGroup($id)
    {
        // This method simply shows the form to create a group for a specific room.
        return view('admin.group.create', ['id' => $id]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'finish_time' => 'required|date_format:H:i|after:start_time',
            'monthly_payment' => 'required|numeric|min:0',
            'room' => 'required|exists:rooms,id',
        ]);

        try {
            // The GroupObserver will automatically handle assigning the teacher
            // after the group is created.
            $group = Group::create([
                'name' => $request->name,
                'start_time' => $request->start_time,
                'finish_time' => $request->finish_time,
                'monthly_payment' => (int)$request->monthly_payment,
                'room_id' => $request->room,
            ]);
             // Agar guruh modelida hasTeacher() metodi bo'lsa va u ID qaytarsa
            // (Bu mantiq sizning modelingizda borligiga tayandim)
            if (method_exists($group, 'hasTeacher')) {
                $teacherId = $group->hasTeacher();
                if ($teacherId) {
                    GroupTeacher::create([
                        'group_id' => $group->id,
                        'teacher_id' => $teacherId,
                    ]);
                }
            }

            return redirect()->route('group.show', $group->room_id)
                ->with('success', 'Guruh muvaffaqiyatli qo\'shildi va o\'qituvchiga biriktirildi.');

        } catch (\Exception $e) {
            Log::error('GroupController@store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Guruhni saqlashda xatolik yuz berdi.');
        }
    }

    public function show($id)
    {
        try {
            $groups = Group::where('room_id', $id)
                ->where('id', '!=', 1) // Assuming 1 is the "Unassigned" group
                ->orderBy('start_time')
                ->get();

            return view('admin.group.index', compact('groups', 'id'));

        } catch (\Exception $e) {
            Log::error('GroupController@show error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhlarni yuklashda xatolik.');
        }
    }

    public function edit(Group $group)
    {
        $rooms = Room::orderBy('room')->get();
        return view('admin.group.edit', compact('group', 'rooms'));
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'finish_time' => 'required|date_format:H:i|after:start_time',
            'monthly_payment' => 'required|numeric|min:0',
            'room' => 'required|exists:rooms,id',
        ]);

        try {
            $group->update($request->all());
            // Note: If the room_id changes, the teacher assignment should also be updated.
            // This logic can be added to the GroupObserver's "updated" method.

            return redirect()->back()->with('success', 'Ma\'lumotlar muvaffaqiyatli yangilandi.');

        } catch (\Exception $e) {
            Log::error('GroupController@update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Yangilashda xatolik yuz berdi.');
        }
    }

    public function destroy(Group $group)
    {
        DB::beginTransaction();
        try {
            // 1. O'qituvchi bog'lanishini o'chirish
            GroupTeacher::where('group_id', $group->id)->delete();
            
            // 2. Talabalar bog'lanishini o'chirish (Pivot jadvaldan)
            // detach() metodi pivot jadvaldan (group_user) yozuvlarni o'chiradi
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
