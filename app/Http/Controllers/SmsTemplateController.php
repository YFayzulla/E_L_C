<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * CRUD for the reusable SMS wordings.
 */
class SmsTemplateController extends Controller
{
    public function index()
    {
        try {
            $templates = SmsTemplate::ordered()
                ->when(filled(request('event')), fn($q) => $q->where('event', request('event')))
                ->when(filled(request('q')), function ($q) {
                    $needle = request('q');
                    $q->where(fn($w) => $w->where('name', 'like', "%{$needle}%")
                        ->orWhere('body', 'like', "%{$needle}%"));
                })
                ->get();

            return view('admin.sms-template.index', [
                'templates' => $templates,
                'counts'    => SmsTemplate::selectRaw('event, COUNT(*) as total')
                    ->groupBy('event')
                    ->pluck('total', 'event'),
            ]);
        } catch (\Exception $e) {
            Log::error('SmsTemplateController@index error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Shablonlar ro‘yxatini yuklashda xatolik.');
        }
    }

    public function create()
    {
        return view('admin.sms-template.create', [
            'template' => new SmsTemplate(['event' => 'general', 'is_active' => true]),
            'sample'   => $this->sampleContext(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::beginTransaction();

        try {
            SmsTemplate::create([
                'name'       => $data['name'],
                'slug'       => SmsTemplate::uniqueSlug($data['name']),
                'event'      => $data['event'],
                'body'       => $data['body'],
                'is_active'  => $request->boolean('is_active'),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);

            DB::commit();

            return redirect()->route('sms-templates.index')->with('success', 'Shablon qo‘shildi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SmsTemplateController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Shablonni saqlashda xatolik yuz berdi.');
        }
    }

    public function edit(SmsTemplate $smsTemplate)
    {
        return view('admin.sms-template.edit', [
            'template' => $smsTemplate,
            'sample'   => $this->sampleContext(),
        ]);
    }

    public function update(Request $request, SmsTemplate $smsTemplate)
    {
        $data = $this->validated($request, $smsTemplate->id);

        DB::beginTransaction();

        try {
            $smsTemplate->update([
                'name'       => $data['name'],
                'slug'       => SmsTemplate::uniqueSlug($data['name'], $smsTemplate->id),
                'event'      => $data['event'],
                'body'       => $data['body'],
                'is_active'  => $request->boolean('is_active'),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);

            DB::commit();

            return redirect()->route('sms-templates.index')->with('success', 'Shablon yangilandi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SmsTemplateController@update error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Shablonni yangilashda xatolik yuz berdi.');
        }
    }

    public function destroy(SmsTemplate $smsTemplate)
    {
        try {
            $name = $smsTemplate->name;
            $smsTemplate->delete();

            return redirect()->route('sms-templates.index')
                ->with('success', "«{$name}» shabloni o‘chirildi.");
        } catch (\Exception $e) {
            Log::error('SmsTemplateController@destroy error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'O‘chirishda xatolik yuz berdi.');
        }
    }

    /** Quick on/off without opening the form. */
    public function toggle(SmsTemplate $smsTemplate)
    {
        try {
            $smsTemplate->is_active = ! $smsTemplate->is_active;
            $smsTemplate->save();

            return redirect()->back()->with(
                'success',
                "«{$smsTemplate->name}» " . ($smsTemplate->is_active ? 'yoqildi.' : 'o‘chirildi.')
            );
        } catch (\Exception $e) {
            Log::error('SmsTemplateController@toggle error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Holatni o‘zgartirishda xatolik.');
        }
    }

    /* ------------------------------------------------------------------ */

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'event'      => ['required', Rule::in(array_keys(SmsTemplate::EVENTS))],
            // 500 matches the send form; longer texts are split into several SMS
            // by the gateway and cost more, so the cap is deliberate.
            'body'       => ['required', 'string', 'min:5', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'name.required'  => 'Shablon nomini kiriting.',
            'event.required' => 'Turini tanlang.',
            'event.in'       => 'Noto‘g‘ri tur tanlandi.',
            'body.required'  => 'Xabar matnini kiriting.',
            'body.max'       => 'Matn 500 belgidan oshmasligi kerak.',
        ]);
    }

    /**
     * Placeholder values from a real student when there is one, so the preview
     * on the form is representative rather than a row of dashes.
     *
     * @return array<string, string>
     */
    private function sampleContext(): array
    {
        $student = User::role('student')->with('groups')->first();

        if ($student) {
            return SmsTemplate::contextForStudent($student);
        }

        return [
            'talaba' => 'Aliyev Javohir', 'guruh' => 'General English B1',
            'sana' => now()->format('d.m.Y'), 'vaqt' => '14:00',
            'baho' => '88', 'reyting' => '85', 'davomat' => '92',
            'qoldirgan' => '2', 'qarz' => '450 000', 'oy' => now()->translatedFormat('F'),
            'ota_ona' => 'Aliyev Sardor', 'oqituvchi' => 'Dilnoza Karimova',
            'markaz' => config('app.name', 'ALPHA'),
        ];
    }
}
