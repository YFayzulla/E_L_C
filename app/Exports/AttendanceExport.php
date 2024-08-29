<?php

namespace App\Exports;

use App\Models\Group;
use App\Models\User;
use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $groupId;
    protected $year;
    protected $month;

    public function __construct($groupId, $year, $month)
    {
        $this->groupId = $groupId;
        $this->year = $year;
        $this->month = $month;
    }

    public function collection()
    {
        $group = Group::find($this->groupId);
        $students = User::role('student')->where('group_id', $group->id)->get();
        $attendances = Attendance::where('group_id', $this->groupId)
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->get();

        $data = [];

        foreach ($students as $student) {
            $data[$student->name] = [];

            for ($i = 1; $i <= 31; $i++) {
                $data[$student->name][str_pad($i, 2, '0', STR_PAD_LEFT)] = '';
            }
        }

        foreach ($attendances as $attendance) {
            $day = $attendance->created_at->format('d');
            $data[$attendance->user->name][$day] = $attendance->status;
        }

        $collection = collect();

        foreach ($data as $studentName => $attendanceDays) {
            $total = array_reduce($attendanceDays, function ($sum, $status) {
                return $sum + ($status ? 1 : 0);
            }, 0);

            $collection->push(array_merge(['Student Name' => $studentName], $attendanceDays, ['Total' => $total]));
        }

        return $collection;
    }

    public function map($row): array
    {
        return array_values($row);
    }

    public function headings(): array
    {
        $days = range(1, 31);
        $daysFormatted = array_map(function ($day) {
            return str_pad($day, 2, '0', STR_PAD_LEFT);
        }, $days);

        return array_merge(['Student Name'], $daysFormatted, ['Total']);
    }
}
