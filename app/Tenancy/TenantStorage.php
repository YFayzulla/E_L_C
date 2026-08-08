<?php

namespace App\Tenancy;

use App\Models\Centre;

/**
 * Yuklangan fayllarni markaz ichiga joylash.
 *
 * Ilgari hamma narsa tekis `Photo/`, `homework/`, `certificates/`
 * kataloglariga tushardi: ikkita markaz bir xil nomli fayl yuklasa
 * biri ikkinchisini bosib ketishi mumkin edi, va operator uchun
 * "qaysi fayl qaysi markazniki" degan savolga javob yo'q edi.
 *
 * Endi: `centres/{id}/Photo/...`
 *
 * Mavjud yozuvlarga tegilmaydi — ular yo'lni bazadan o'qiydi, ya'ni
 * eski fayllar o'z joyida ochilaveradi. Faqat yangi yuklamalar
 * prefiks oladi. Migratsiya shart emas.
 *
 * DIQQAT: bu ruxsatni ALMASHTIRMAYDI. `public` diskdagi fayllar
 * hamon autentifikatsiyasiz ochiq — bu alohida, mavjud kamchilik
 * va uni prefiks tuzatmaydi.
 */
class TenantStorage
{
    /**
     * Katalog yo'li, markaz aniq bo'lsa uning prefiksi bilan.
     *
     * Konsolda yoki markazsiz kontekstda eski tekis yo'l qaytadi —
     * shunda seeder va migratsiyalar avvalgidek ishlayveradi.
     */
    public static function path(string $directory): string
    {
        $directory = trim($directory, '/');
        $id = Centre::currentId();

        return $id === null ? $directory : "centres/{$id}/{$directory}";
    }
}
