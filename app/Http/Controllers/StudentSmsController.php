<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Sending an SMS about a student, with an explicit choice of who receives it —
 * the father, the mother, a guardian, the student, or several at once.
 *
 * Before this, the only SMS path was a console command that always messaged the
 * student's own number.
 */
class StudentSmsController extends Controller
{
    use AuthorizesGroupAccess;

    public function __construct(private MessageService $sms)
    {
    }

    public function store(Request $request, int $student)
    {
        // Outside the try: abort() is an \Exception and the catch would turn a
        // 403 into a friendly redirect, hiding the authorisation failure.
        $this->assertTeachesStudent($student);

        $data = $request->validate([
            'message'      => ['required', 'string', 'min:3', 'max:500'],
            'audiences'    => ['required', 'array', 'min:1'],
            'audiences.*'  => ['string', Rule::in(MessageService::AUDIENCES)],
        ], [
            'message.required'   => 'Xabar matnini kiriting.',
            'message.max'        => 'Xabar 500 belgidan oshmasligi kerak.',
            'audiences.required' => 'Kimga yuborilishini tanlang.',
            'audiences.min'      => 'Kamida bitta qabul qiluvchini tanlang.',
        ]);

        $model = User::findOrFail($student);

        abort_unless($model->hasRole('student'), 404, 'Talaba topilmadi.');

        try {
            $result = $this->sms->sendAboutStudent($model, $data['message'], $data['audiences']);
        } catch (\Exception $e) {
            Log::error('StudentSmsController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()
                ->with('error', 'SMS yuborishda xatolik yuz berdi.');
        }

        // Say exactly why nothing went out — "Eskiz sozlanmagan" and "raqam
        // yo'q" need completely different fixes, and a vague message sends the
        // admin hunting in the wrong place.
        $why = collect($result['reasons'] ?? [])
            ->map(fn(int $count, string $reason) => MessageService::reasonLabel($reason) . " ({$count})")
            ->implode(', ');

        if ($result['sent'] === 0) {
            return redirect()->back()->withInput()->with(
                'error',
                'Hech kimga yuborilmadi' . ($why ? ' — ' . $why : '') . '.'
            );
        }

        $message = $result['sent'] . ' ta raqamga yuborildi: ' . implode(', ', $result['recipients']) . '.';

        if ($result['skipped'] > 0) {
            $message .= ' ' . $result['skipped'] . ' tasi yuborilmadi' . ($why ? ' — ' . $why : '') . '.';
        }

        return redirect()->back()->with('success', $message);
    }
}
