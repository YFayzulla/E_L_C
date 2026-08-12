<?php

namespace Tests\Feature\Auth;

use App\Models\Centre;
use App\Models\User;
use App\Notifications\ResetPasswordUz;
use App\Services\CentreMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordUz::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordUz::class, function ($notification) {
            $response = $this->get('/reset-password/' . $notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordUz::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasNoErrors();

            return true;
        });
    }

    /**
     * Xat o'zbekcha bo'lishi kerak: bu yerdagi foydalanuvchilarning
     * ko'pchiligi inglizcha "Reset Password Notification" ni tushunmaydi.
     */
    public function test_the_e_mail_is_in_uzbek_and_names_the_centre(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordUz::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $body = collect($mail->introLines)->concat($mail->outroLines)->implode(' ');

            $this->assertStringContainsString('Parolni tiklash', $mail->subject);
            $this->assertStringContainsString(config('app.name'), $mail->subject);
            $this->assertStringContainsString('Assalomu alaykum', $mail->greeting);
            $this->assertStringContainsString('Parolni tiklash', $mail->actionText);
            $this->assertStringContainsString('daqiqadan', $body);

            return true;
        });
    }

    /**
     * Hisoblarning ko'pchiligida pochta manzili yo'q — kirish telefon
     * raqami bilan. Ularga yuboradigan joy yo'q, va bu istisno emas,
     * shunchaki jimgina o'tib ketishi kerak.
     */
    public function test_an_account_without_an_e_mail_is_not_notified(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => null]);

        $user->sendPasswordResetNotification('any-token');

        Notification::assertNothingSent();
    }

    /**
     * Eng muhim tekshiruv. `password_resets` jadvali pochta manzili
     * bo'yicha kalitlanadi va unda centre_id YO'Q — ya'ni markazlar
     * orasidagi devor butunlay kontrollerdagi `inCurrentCentre()` ga
     * tayanadi. O'sha tekshiruv olib tashlansa, beta subdomeni alpha
     * foydalanuvchisining parolini tiklab yubora olardi.
     */
    public function test_a_centre_cannot_reset_another_centres_password(): void
    {
        Notification::fake();

        $beta = Centre::withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'beta'],
            ['name' => 'Beta', 'status' => Centre::STATUS_ACTIVE]
        );

        // Faqat alpha ga a'zo (factory shunday qiladi).
        $alphaUser = User::factory()->create();

        $this->post('http://beta.localhost/forgot-password', ['email' => $alphaUser->email])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();

        // Beta ning o'z a'zosi esa bemalol so'ray oladi — ya'ni yuqoridagi
        // rad etish subdomen umuman ishlamayotganidan emas.
        $betaUser = Centre::for($beta, function () use ($beta) {
            $user = new User();
            $user->forceFill([
                'name'     => 'Beta Foydalanuvchi',
                'phone'    => '998901239944',
                'email'    => 'beta.user@beta.test',
                'password' => Hash::make('secret'),
            ])->save();

            app(CentreMembershipService::class)->attach($beta, $user, 'student');

            return $user;
        });

        $this->post('http://beta.localhost/forgot-password', ['email' => $betaUser->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($betaUser, ResetPasswordUz::class);

        // Xat beta nomidan ketishi kerak. `mail.from.name` MAIL_FROM_NAME
        // dan keladi, ya'ni app.name ni almashtirish yetmaydi — buni
        // ResolveCentre alohida yozadi.
        $this->assertSame('Beta', config('mail.from.name'));
    }

    /**
     * Havolaning o'zi ham boshqa markazda tugallanmasligi kerak: token
     * bazada markazga bog'lanmagan, shuning uchun `POST /reset-password`
     * ham xuddi so'rov kabi tekshirilishi shart.
     */
    public function test_a_token_issued_by_one_centre_does_not_work_on_another(): void
    {
        Notification::fake();

        Centre::withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'beta'],
            ['name' => 'Beta', 'status' => Centre::STATUS_ACTIVE]
        );

        $alphaUser = User::factory()->create();
        $oldHash = $alphaUser->password;

        $this->post('/forgot-password', ['email' => $alphaUser->email]);

        Notification::assertSentTo($alphaUser, ResetPasswordUz::class, function ($notification) use ($alphaUser, $oldHash) {
            $this->post('http://beta.localhost/reset-password', [
                'token' => $notification->token,
                'email' => $alphaUser->email,
                'password' => 'BoshqaMarkaz123',
                'password_confirmation' => 'BoshqaMarkaz123',
            ])->assertSessionHasErrors('email');

            $this->assertSame($oldHash, $alphaUser->fresh()->password, 'Parol o‘zgarmagan bo‘lishi kerak');

            return true;
        });
    }
}
