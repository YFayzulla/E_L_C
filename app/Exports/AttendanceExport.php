<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceExport implements FromArray, WithHeadings
{
    protected $group;
    protected $year;
    protected $month;
    protected $data;

    public function __construct($group, $year, $month, $data)
    {
        $this->group = $group;
        $this->year = $year;
        $this->month = $month;
        $this->data = $data;
    }

    public function array(): array
    {
        $exportData = [];

        foreach ($this->data as $userName => $userData) {
            $row = [$userName];
            for ($i = 1; $i <= 31; $i++) {
                $day = str_pad($i, 2, '0', STR_PAD_LEFT);
                $status = $userData['days'][$day] ?? '1'; // Default to '1' if not set
                $row[] = $status === '1' ? 'X' : ''; // Display 'X' for present
            }
            // Добавляем столбец created_at
            $row[] = $userData['created_at'] ?? ''; // Default to empty if not set
            $exportData[] = $row;
        }

        return $exportData;
    }

    public function headings(): array
    {
        $headings = ['Name'];
        for ($i = 1; $i <= 31; $i++) {
            $headings[] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        $headings[] = 'Created At';

        return $headings;
    }
}
