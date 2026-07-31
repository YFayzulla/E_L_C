<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\GroupLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Finishing a course: the group is marked tugagan and its students become
 * bitirgan, which takes them off the billing screens.
 */
class GroupLifecycleController extends Controller
{
    public function __construct(private GroupLifecycleService $lifecycle)
    {
    }

    public function finish(Request $request, Group $group)
    {
        abort_if($group->isWaitingRoom(), 403, 'Kutish zalini yakunlab bo‘lmaydi.');

        $data = $request->validate([
            'finished_at' => ['nullable', 'date'],
        ], [
            'finished_at.date' => 'Tugash sanasi noto‘g‘ri.',
        ]);

        if ($group->isFinished()) {
            return redirect()->back()->with('warning', 'Bu guruh allaqachon yakunlangan.');
        }

        try {
            $result = $this->lifecycle->finish($group, $data['finished_at'] ?? null);
        } catch (\Exception $e) {
            Log::error('GroupLifecycleController@finish error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Guruhni yakunlashda xatolik yuz berdi.');
        }

        $message = "«{$group->name}» yakunlandi. "
            . count($result['graduated']) . ' ta talaba bitirgan deb belgilandi';

        if ($result['kept']) {
            $message .= ', ' . count($result['kept'])
                . ' tasi boshqa faol guruhda bo‘lgani uchun faol qoldi';
        }

        return redirect()->back()->with('success', $message . '.');
    }

    public function reopen(Group $group)
    {
        if (! $group->isFinished()) {
            return redirect()->back()->with('warning', 'Bu guruh allaqachon faol.');
        }

        try {
            $reactivated = $this->lifecycle->reopen($group);
        } catch (\Exception $e) {
            Log::error('GroupLifecycleController@reopen error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Guruhni qayta ochishda xatolik yuz berdi.');
        }

        return redirect()->back()->with(
            'success',
            "«{$group->name}» qayta ochildi. {$reactivated} ta talaba yana faol holatga qaytdi."
        );
    }
}
