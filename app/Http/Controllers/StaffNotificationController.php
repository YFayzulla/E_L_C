<?php

namespace App\Http\Controllers;

use App\Models\StaffNotification;

/**
 * Navbardagi qo'ng'iroqcha ortidagi sahifa.
 *
 * Har bir amal faqat o'z bildirishnomalarida ishlaydi: `for(auth()->user())`
 * boshqa odamning yozuvini 404 ga aylantiradi, markazni esa global scope
 * cheklaydi.
 */
class StaffNotificationController extends Controller
{
    public function index()
    {
        $notifications = StaffNotification::for(auth()->user())
            ->with('actor:id,name')
            ->latest()
            ->paginate(30);

        return view('notifications.index', compact('notifications'));
    }

    /** Bildirishnomani o'qilgan qilib, unga tegishli sahifaga o'tadi. */
    public function open(int $notification)
    {
        $model = StaffNotification::for(auth()->user())->findOrFail($notification);

        if ($model->isUnread()) {
            $model->update(['read_at' => now()]);
        }

        return $model->url
            ? redirect()->to($model->url)
            : redirect()->route('notifications.index');
    }

    public function readAll()
    {
        StaffNotification::for(auth()->user())
            ->unread()
            ->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'Barcha bildirishnomalar o‘qildi deb belgilandi.');
    }
}
