<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Uzbek wording for the account-confirmation e-mail.
 *
 * Extends the framework notification so the signed URL, its expiry and the
 * `verification.verify` route binding all stay exactly as Laravel builds them.
 */
class VerifyEmailUz extends VerifyEmail
{
    /**
     * @param  \App\Models\User  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage())
            ->subject('Hisobingizni tasdiqlang — ' . config('app.name'))
            ->greeting('Assalomu alaykum, ' . $notifiable->name . '!')
            ->line("Pochta manzilingizni tasdiqlash uchun quyidagi tugmani bosing.")
            ->action('Tasdiqlash', $url)
            ->line('Havola ' . config('auth.verification.expire', 60) . ' daqiqadan so‘ng eskiradi.')
            ->line('Agar siz hisob ochmagan bo‘lsangiz, bu xatga e’tibor bermang.')
            ->salutation('Hurmat bilan, ' . config('app.name'));
    }
}
