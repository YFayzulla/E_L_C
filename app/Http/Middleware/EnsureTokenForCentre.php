<?php

namespace App\Http\Middleware;

use App\Tenancy\CentreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanctum tokenini bergan markaz bilan cheklaydi.
 *
 * Markaz host'dan aniqlanadi va a'zolik EnsureCentreMember da qayta
 * tekshiriladi, ya'ni bu — uchinchi qatlam, oqishning o'zi emas. Lekin
 * ikkita markazda ishlaydigan odamning A markazidagi telefonidan olingan
 * token B markazining ma'lumotini ham ocha olardi. Token qaysi markazda
 * berilgan bo'lsa, o'sha markazda ishlashi kerak.
 *
 * Eski tokenlar `*` qobiliyati bilan yaratilgan va `tokenCan()` ular uchun
 * hamisha true qaytaradi — ya'ni ilova yangilanmagan telefonlar ishlayveradi
 * va majburiy qayta kirish talab qilinmaydi.
 */
class EnsureTokenForCentre
{
    public function handle(Request $request, Closure $next): Response
    {
        $centre = app(CentreContext::class)->centre();
        $token  = $request->user()?->currentAccessToken();

        // Sessiya orqali kelgan so'rovda token bo'lmaydi — bu middleware
        // faqat token bilan kelganlarga tegishli.
        if ($centre === null || $token === null) {
            return $next($request);
        }

        if (! $token->can('centre:' . $centre->id)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Bu token boshqa o‘quv markazi uchun berilgan. '
                    . 'Shu markaz manzilidan qaytadan kiring.',
            ], 403);
        }

        return $next($request);
    }
}
