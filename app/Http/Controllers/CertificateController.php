<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\Certificate;
use App\Models\Group;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Graduation certificates.
 *
 * The centre issues one to a student who has finished a course; the student
 * downloads it from their own profile. A certificate is either an uploaded file
 * or, when none was uploaded, a PDF rendered on demand from a template.
 */
class CertificateController extends Controller
{
    use AuthorizesGroupAccess;

    private const DISK = 'public';
    private const DIR  = 'certificates';

    /** Admin register of every certificate issued. */
    public function index()
    {
        try {
            $certificates = Certificate::with(['student:id,name,phone', 'group:id,name', 'issuer:id,name'])
                ->when(filled(request('q')), function ($query) {
                    $q = request('q');
                    $query->where('serial', 'like', "%{$q}%")
                        ->orWhereHas('student', fn($s) => $s->where('name', 'like', "%{$q}%"));
                })
                ->when(filled(request('group_id')), fn($query) => $query->where('group_id', request('group_id')))
                ->orderByDesc('issued_at')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString();

            return view('admin.certificate.index', [
                'certificates' => $certificates,
                'groups'       => Group::orderBy('name')->get(['id', 'name']),
            ]);
        } catch (\Exception $e) {
            Log::error('CertificateController@index error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Sertifikatlar ro‘yxatini yuklashda xatolik.');
        }
    }

    public function create()
    {
        return view('admin.certificate.create', [
            'students'  => User::role('student')->orderBy('name')->get(['id', 'name', 'phone']),
            'groups'    => Group::orderBy('name')->get(['id', 'name']),
            'preselect' => (int) request('student_id') ?: null,
            'serial'    => Certificate::nextSerial(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $stored = null;

        try {
            if ($request->hasFile('file')) {
                $stored = $request->file('file')->store(self::DIR, self::DISK);
            }
        } catch (\Exception $e) {
            Log::error('CertificateController@store upload error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Faylni yuklashda xatolik.');
        }

        DB::beginTransaction();

        try {
            $certificate = Certificate::create([
                'user_id'     => $data['user_id'],
                'group_id'    => $data['group_id'] ?? null,
                // Recomputed here, not trusted from the form: two admins on the
                // create page at once would otherwise submit the same serial.
                'serial'      => Certificate::nextSerial(),
                'title'       => $data['title'],
                'level'       => $data['level'] ?? null,
                'final_score' => $data['final_score'] ?? null,
                'note'        => $data['note'] ?? null,
                'file'        => $stored,
                'issued_at'   => $data['issued_at'],
                'issued_by'   => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('certificates.index')
                ->with('success', "Sertifikat berildi — {$certificate->serial}.");
        } catch (\Exception $e) {
            DB::rollBack();

            if ($stored) {
                Storage::disk(self::DISK)->delete($stored);
            }

            Log::error('CertificateController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Sertifikatni saqlashda xatolik yuz berdi.');
        }
    }

    public function destroy(Certificate $certificate)
    {
        DB::beginTransaction();

        try {
            $file = $certificate->file;
            $certificate->delete();

            DB::commit();

            if ($file && Storage::disk(self::DISK)->exists($file)) {
                Storage::disk(self::DISK)->delete($file);
            }

            return redirect()->back()->with('success', 'Sertifikat o‘chirildi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CertificateController@destroy error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'O‘chirishda xatolik yuz berdi.');
        }
    }

    /**
     * The student's own certificates, shown on their profile.
     */
    public function mine()
    {
        $certificates = Certificate::with('group:id,name')
            ->where('user_id', auth()->id())
            ->orderByDesc('issued_at')
            ->get();

        return view('student.certificates', compact('certificates'));
    }

    /**
     * Download. A student may only ever fetch their OWN certificate; a teacher
     * only those of students in their groups; an admin anything.
     */
    public function download(Certificate $certificate)
    {
        $this->authorizeAccess($certificate);

        try {
            if ($certificate->hasFile()) {
                abort_unless(Storage::disk(self::DISK)->exists($certificate->file), 404, 'Fayl topilmadi.');

                return Storage::disk(self::DISK)->download(
                    $certificate->file,
                    $certificate->downloadName(pathinfo($certificate->file, PATHINFO_EXTENSION) ?: 'pdf')
                );
            }

            $certificate->loadMissing(['student', 'group', 'issuer']);

            return Pdf::loadView('admin.pdf.certificate', ['certificate' => $certificate])
                ->setPaper('a4', 'landscape')
                ->download($certificate->downloadName());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CertificateController@download error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Sertifikatni yuklab olishda xatolik.');
        }
    }

    /* ------------------------------------------------------------------ */

    private function authorizeAccess(Certificate $certificate): void
    {
        $user = auth()->user();

        abort_if(! $user, 403);

        if ($user->hasRole('admin')) {
            return;
        }

        // The owner.
        if ((int) $certificate->user_id === (int) $user->id) {
            return;
        }

        // A parent of the owner.
        if ($user->hasRole('parent')) {
            abort_unless(
                $user->children()->where('users.id', $certificate->user_id)->exists(),
                403,
                'Bu sertifikat sizning farzandingizga tegishli emas.'
            );

            return;
        }

        // A teacher of the owner.
        if ($user->hasRole('user')) {
            $this->assertTeachesStudent((int) $certificate->user_id);

            return;
        }

        abort(403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'user_id'     => ['required', 'integer', 'exists:users,id'],
            'group_id'    => ['nullable', 'integer', 'exists:groups,id'],
            'title'       => ['required', 'string', 'max:255'],
            'level'       => ['nullable', 'string', 'max:100'],
            'final_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'note'        => ['nullable', 'string', 'max:1000'],
            'issued_at'   => ['required', 'date'],
            'file'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'user_id.required'   => 'Talabani tanlang.',
            'title.required'     => 'Sertifikat nomini kiriting.',
            'issued_at.required' => 'Berilgan sanani kiriting.',
            'file.mimes'         => 'Fayl PDF yoki rasm (jpg, png) bo‘lishi kerak.',
            'file.max'           => 'Fayl hajmi 5 MB dan oshmasligi kerak.',
        ]);
    }
}
