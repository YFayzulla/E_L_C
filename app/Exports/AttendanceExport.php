<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Attendance;

class AttendanceExport implements FromArray, WithHeadings, WithStyles
{
    protected $groupId;
    protected $year;
    protected $month;

    public function __construct($group, $year, $month)
    {
        $this->groupId = $group;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);

        $data = Attendance::where('group_id', $this->groupId)
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->with('user')
            ->get()
            ->groupBy('user_id');

        $rows = [];
        foreach ($data as $userId => $attendances) {
            $userName = $attendances->first()->user->name;
            $days = array_fill(1, $daysInMonth, '');

            foreach ($attendances as $attendance) {
                $day = $attendance->created_at->format('d');
                $days[intval($day)] = $attendance->status;
            }

            $statusSum = array_sum(array_filter($days, 'is_numeric'));

            $rows[] = array_merge([$userName], array_values($days), [$statusSum]);
        }

        $header = array_merge(['Name'], array_map(fn($i) => str_pad($i, 2, '0', STR_PAD_LEFT), range(1, $daysInMonth)), ['Total']);

        return array_merge([$header], $rows);
    }

    public function headings(): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);
        return array_merge(['Name'], array_map(fn($i) => str_pad($i, 2, '0', STR_PAD_LEFT), range(1, $daysInMonth)), ['Total']);
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $sheet->getStyle('A1:' . $sheet->getCellByColumnAndRow($highestColumnIndex, 1)->getColumn() . '1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => 'center'],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'B7B7B7'],
            ],
        ]);
    }
}
