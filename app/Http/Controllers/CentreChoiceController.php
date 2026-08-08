<?php

namespace App\Http\Controllers;

use App\Models\Centre;

/**
 * Apex domendagi markaz tanlash oynasi.
 *
 * Sessiya cookie'si barcha subdomenlar bo'ylab umumiy (SESSION_DOMAIN),
 * ya'ni bu yerda bir marta kirgan odam har bir markazda kim ekanini
 * isbotlagan bo'ladi. Lekin bu HUQUQ BERMAYDI: har bir subdomenda
 * EnsureCentreMember qayta tekshiradi.
 *
 * Bir odam bir nechta markazda ishlashi mumkin — A markazda admin,
 * B markazda o'qituvchi bo'lishi mumkin, chunki rol biriktirish
 * markazga bog'langan (Spatie teams).
 */
class CentreChoiceController extends Controller
{
    /**
     * Imzo parametrsiz: bazaviy Controller::index() dashboard amali bo'lib
     * turibdi va PHP bola sinfdan mos imzo talab qiladi.
     */
    public function index()
    {
        $user = auth()->user();

        // Platforma egasi hech qaysi markazga a'zo emas va bo'lishi
        // ham shart emas — u markazlarni boshqaradi.
        if ($user->is_super_admin) {
            return redirect()->route('super.centres.index');
        }

        $centres = $user->centres()
            ->wherePivot('status', Centre::MEMBER_ACTIVE)
            ->where('centres.status', Centre::STATUS_ACTIVE)
            ->orderBy('centres.name')
            ->get();

        // Bitta bo'lsa tanlashning ma'nosi yo'q — to'g'ridan-to'g'ri o'sha yerga.
        if ($centres->count() === 1) {
            return redirect()->away($centres->first()->url('/'));
        }

        return view('centres.choose', [
            'centres' => $centres,
            'suspended' => $user->centres()
                ->where('centres.status', '!=', Centre::STATUS_ACTIVE)
                ->get(),
        ]);
    }
}
