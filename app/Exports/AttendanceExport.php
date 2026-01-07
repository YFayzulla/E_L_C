<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\LessonAndHistory;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
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
        // 1. Guruh va Talabalarni olish
        $group = Group::findOrFail($this->groupId);
        
        $students = User::role('student')
            ->whereHas('groups', function ($query) use ($group) {
                $query->where('groups.id', $group->id);
            })
            ->orderBy('name')
            ->get();

        // 2. Shu oydagi DARS BO'LGAN kunlarni olish (LessonAndHistory)
        // Diqqat: LessonAndHistory jadvalida 'group' ustuni ishlatiladi (migratsiyaga ko'ra)
        $lessonDays = LessonAndHistory::where('group', $this->groupId)
            ->where('data', 1) // 1 = Davomat qilingan dars
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->get()
            ->map(function ($lesson) {
                return (int) $lesson->created_at->format('d'); // Kunni raqamga aylantiramiz (masalan: 05 -> 5)
            })
            ->unique()
            ->toArray();

        // 3. Shu oydagi DAVOMAT (Absent/Late) yozuvlarini olish
        $attendances = Attendance::where('group_id', $this->groupId)
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->get();


        // Davomatni qulay formatga o'tkazish: [user_id][kun] = status
        $attendanceMap = [];
        foreach ($attendances as $att) {
            $day = (int) $att->created_at->format('d');
            $attendanceMap[$att->user_id][$day] = $att->status;
        }

        // 4. Jadvalni shakllantirish
        $daysInMonth = Carbon::createFromDate($this->year, $this->month)->daysInMonth;
        $collection = collect();

        foreach ($students as $student) {
            // Qator boshida talaba ismi
            $row = ['Student Name' => $student->name];

            // 1 dan 31 gacha (yoki oy oxirigacha) aylanamiz
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $statusText = ''; // Default bo'sh

                // 1-Tekshiruv: Aniq davomat yozuvi bormi? (NB yoki Kech)
                if (isset($attendanceMap[$student->id][$day])) {
                    $status = $attendanceMap[$student->id][$day];
                    if ($status == 0) {
                        $statusText = 'NB';
                    } elseif ($status == 2) {
                        $statusText = 'Kech';
                    } elseif ($status == 1) {
                        $statusText = '+'; // Ehtimol bazada 1 qolib ketgan bo'lsa
                    }
                } 
                // 2-Tekshiruv: Agar yozuv bo'lmasa, shu kuni dars bo'lganmi?
                elseif (in_array($day, $lessonDays)) {
                    // Dars bo'lgan va talaba "Absent/Late" emas -> Demak u BOR
                    $statusText = '+';
                }

                // Natijani yozamiz
                $row[$day] = $statusText;
            }

            $collection->push($row);
        }

        return $collection;
    }

    public function headings(): array
    {
        $daysInMonth = Carbon::createFromDate($this->year, $this->month)->daysInMonth;
        $headings = ['Student Name'];
        
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $headings[] = (string) $i;
        }

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => 'center'],
            ],
            // Barcha katakchalarni markazga tekislash (A ustunidan tashqari)
            'B:AG' => ['alignment' => ['horizontal' => 'center']],
        ];
    }
}
