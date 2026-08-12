<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Uzbek wording for the password-reset e-mail.
 *
 * Extends the framework notification so the token, its expiry and the
 * `password.reset` route binding all stay exactly as Laravel builds them —
 * only the wording changes.
 *
 * `config('app.name')` markazning nomiga teng: ResolveCentre uni har bir
 * so'rovda o'rnatadi. Ya'ni xatni qaysi markazdan so'ralgan bo'lsa,
 * o'sha markaz nomidan keladi.
 */
class ResetPasswordUz extends ResetPassword
{
    /**
     * @param  \App\Models\User  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $minutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage())
            ->subject('Parolni tiklash — ' . config('app.name'))
            ->greeting('Assalomu alaykum, ' . $notifiable->name . '!')
            ->line('Hisobingiz uchun parolni tiklash so‘ralgan. Yangi parol '
                . 'o‘rnatish uchun quyidagi tugmani bosing.')
            ->action('Parolni tiklash', $url)
            ->line('Havola ' . $minutes . ' daqiqadan so‘ng eskiradi.')
            ->line('Agar siz parolni tiklashni so‘ramagan bo‘lsangiz, bu xatga '
                . 'e’tibor bermang — parolingiz o‘zgarmaydi.')
            ->salutation('Hurmat bilan, ' . config('app.name'));
    }
}
