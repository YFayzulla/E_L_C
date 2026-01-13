<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $studentId;

    public function __construct($studentId = null)
    {
        $this->studentId = $studentId;
    }

    public function collection()
    {
        if ($this->studentId) {
            // Export single student
            $student = User::role('student')->findOrFail($this->studentId);
            return collect([
                $this->formatStudentData($student)
            ]);
        } else {
            // Export all students
            $students = User::role('student')->with('groups')->orderBy('name', 'desc')->get();
            return $students->map(function ($student) {
                return $this->formatStudentData($student);
            });
        }
    }

    private function formatStudentData($student)
    {
        // Parse description/comments into a list format
        $comments = [];
        if ($student->description) {
            $commentLines = explode("\n", $student->description);
            foreach ($commentLines as $comment) {
                if (trim($comment) !== '') {
                    $comments[] = trim($comment);
                }
            }
        }

        return [
            'Name' => $student->name,
            'Phone' => $student->phone,
            'Parents Name' => $student->parents_name,
            'Parents Tel' => $student->parents_tel,
            'Location' => $student->location,
            'Groups' => $student->groups->pluck('name')->implode(', ') ?: 'No Group',
            'Should Pay' => number_format($student->should_pay, 0, '.', ' '),
            'Comments' => implode(' | ', $comments) ?: 'No comments',
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Parents Name',
            'Parents Tel',
            'Location',
            'Groups',
            'Should Pay',
            'Comments & Description',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getStyle('1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');

        // Wrap text for comments column (column H)
        $sheet->getStyle('H')->getAlignment()->setWrapText(true);

        return $sheet;
    }
}
