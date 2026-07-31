<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A graduation certificate issued to a student.
 *
 * Either the centre uploaded a designed file, or `file` is null and the PDF is
 * rendered on demand from resources/views/admin/pdf/certificate.blade.php.
 */
class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_id',
        'serial',
        'title',
        'level',
        'final_score',
        'note',
        'file',
        'issued_at',
        'issued_by',
    ];

    protected $casts = [
        'issued_at'   => 'date',
        'final_score' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** True when a designed file was uploaded rather than generated. */
    public function hasFile(): bool
    {
        return filled($this->file);
    }

    /**
     * Serial numbers look like ALP-2026-0007 — year plus a zero-padded counter,
     * so they read as a register entry rather than a random string.
     */
    public static function nextSerial(?int $year = null): string
    {
        $year = $year ?: (int) now()->format('Y');

        // The prefix is a per-centre identifier, not a constant of the product —
        // two centres must not share a serial register.
        $prefix = static::serialPrefix();

        $last = static::where('serial', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->value('serial');

        $counter = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return sprintf('%s-%d-%04d', $prefix, $year, $counter);
    }

    public static function serialPrefix(): string
    {
        return (string) config('app.certificate_prefix', 'ALP');
    }

    /** Filename offered to the browser on download. */
    public function downloadName(string $extension = 'pdf'): string
    {
        $name = Str::slug($this->student?->name ?? 'sertifikat');

        return "{$this->serial}-{$name}.{$extension}";
    }
}
