<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

/**
 * Wildcard subdomen (*.domen.uz) ishlatilgani uchun bu himoya majburiy.
 *
 * Host sarlavhasi — foydalanuvchi bergan ma'lumot, va ResolveCentre aynan
 * shundan qaysi markaz ekanini aniqlaydi. Ro'yxatsiz bo'lsa, ixtiyoriy
 * `Host:` bilan kelgan so'rov parolni tiklash havolasi kabi absolyut
 * URL'larni begona domenga yo'naltirishi mumkin edi.
 *
 * Laravel buni `local` muhitda va testlarda o'zi o'chirib qo'yadi
 * (`shouldSpecifyTrustedHosts()`), shuning uchun 127.0.0.1 dagi ishlanma
 * serveri va phpunit zarar ko'rmaydi.
 */
class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts()
    {
        // APP_URL apex bo'lishi kerak (masalan https://domen.uz) — bu naqsh
        // apexning o'zini ham, uning barcha subdomenlarini ham qamrab oladi.
        $patterns = [$this->allSubdomainsOfApplicationUrl()];

        // APP_DOMAIN APP_URL dan farq qilishi mumkin (masalan APP_URL
        // markazning o'z manzili bo'lsa) — o'shani ham qo'shamiz.
        $domain = (string) config('app.domain');

        if ($domain !== '' && $domain !== 'localhost') {
            $patterns[] = '^(.+\.)?' . preg_quote($domain, '#') . '$';
        }

        return array_values(array_unique(array_filter($patterns)));
    }
}
