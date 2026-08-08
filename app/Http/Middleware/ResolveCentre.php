<?php

namespace App\Http\Middleware;

use App\Models\Centre;
use App\Tenancy\CentreContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Works out which o'quv markazi this request is for, from the subdomain.
 *
 * Reading the Host rather than declaring `Route::domain(...)` keeps all 135 web
 * routes and 23 API routes exactly as they are: Laravel builds relative route
 * URLs from the current request's host, so route('group.index') already yields
 * the right subdomain, and signed links sign and verify against it too.
 *
 * Runs before StartSession and SubstituteBindings — route-model binding has to
 * happen inside the centre context or it would resolve other centres' rows.
 */
class ResolveCentre
{
    /**
     * Markazga xos qiymatlar yozilishidan OLDINGI baholash sozlamalari.
     *
     * Bir marta — birinchi so'rovda — olinadi va shundan keyin har bir
     * markaz uchun boshlang'ich nuqta bo'lib xizmat qiladi.
     */
    private static ?array $pristineGrading = null;

    public function handle(Request $request, Closure $next): Response
    {
        $centre = $this->resolve($request);

        // Har doim ulashiladi, null bo'lsa ham: apex sahifalari ham shu
        // o'zgaruvchiga qaraydi va uni faqat markaz bor paytda ulashish
        // "Undefined variable $centre" ga olib kelardi.
        View::share('centre', $centre);

        if ($centre !== null) {
            app(CentreContext::class)->set($centre);

            // The app's own name and clock become the centre's.
            config([
                'app.name'     => $centre->name,
                'app.timezone' => $centre->timezone,
            ]);
            date_default_timezone_set($centre->timezone);

            // Markazga xos baholash sozlamalari config ustiga yoziladi.
            //
            // Shu bitta blok tufayli kod bo'ylab tarqalgan 41 ta
            // config('grading.*') chaqiruvi o'zgarishsiz qoladi va
            // ularning har birini Centre::setting() ga o'tkazish shart
            // emas — bitta unutilgan joy jimgina eski qiymatni olardi.
            //
            // Faqat OVERRIDABLE_SETTINGS ro'yxatidagilar: markazga
            // `skills` ni qayta ta'riflashga ruxsat berilsa, o'sha paytda
            // yozilgan har bir lesson_skill_grades qatori qaysi to'plam
            // kuchda bo'lganini bilmasdan o'qib bo'lmasdi.
            // Config jarayon davomida global. Bitta jarayonda ikkita markaz
            // ishlansa (test, konsol, Octane) oldingisining qiymati qolib
            // ketardi — shuning uchun har safar ASL holatdan boshlanadi.
            self::$pristineGrading ??= config('grading');
            config(['grading' => self::$pristineGrading]);

            foreach (Centre::OVERRIDABLE_SETTINGS as $key) {
                $value = data_get($centre->settings, $key);

                if ($value === null) {
                    continue;
                }

                // Massivlar QO'SHILADI, almashtirilmaydi: markaz faqat
                // `bands.good` ni o'zgartirsa, `bands.ok` yo'qolib ketmasligi
                // kerak — aks holda undan o'qiydigan kod jimgina null oladi.
                if (is_array($value) && is_array(config("grading.{$key}"))) {
                    $value = array_replace_recursive(config("grading.{$key}"), $value);
                }

                config(["grading.{$key}" => $value]);
            }
        }

        return $next($request);
    }

    /**
     * Null on the apex domain, which carries login, the centre picker and the
     * super-admin area and legitimately has no centre.
     */
    private function resolve(Request $request): ?Centre
    {
        $host = Str::lower($request->getHost());
        $root = Str::lower((string) config('app.domain'));

        if ($host === $root || $host === 'www.' . $root) {
            return $this->default();
        }

        if (! Str::endsWith($host, '.' . $root)) {
            // Not a host we serve. On a single-centre installation this is the
            // normal case — it is still running on whatever address it always
            // had — so fall back before refusing.
            return $this->default() ?? abort(404);
        }

        $slug = Str::beforeLast($host, '.' . $root);

        // Sub-sub-domains are not a thing here.
        if (str_contains($slug, '.')) {
            abort(404);
        }

        // Wildcard DNS (*.domen.uz) hamma narsani shu yerga olib keladi,
        // shu jumladan pochta va platformaning o'z subdomenlarini ham.
        // Ular markaz emas — apex kabi ishlanadi, ya'ni markazsiz.
        // Tekshiruv bazaga qadar: kimdir qatorni qo'lda yozib qo'ygan
        // bo'lsa ham `www.domen.uz` markazga aylanib qolmasligi kerak.
        if (Centre::isReservedSlug($slug)) {
            return $this->default();
        }

        $centre = Cache::remember(
            Centre::cacheKey($slug),
            now()->addSeconds(60),
            fn() => Centre::where('slug', $slug)->first()
        );

        // 404 rather than a redirect to the apex: a redirect would turn the
        // app into an oracle for which centres exist.
        abort_if($centre === null, 404);

        abort_if(
            $centre->status === Centre::STATUS_ARCHIVED,
            404
        );

        abort_if(
            $centre->status === Centre::STATUS_SUSPENDED,
            503,
            'Bu o‘quv markazi vaqtincha to‘xtatilgan.'
        );

        return $centre;
    }

    /**
     * The centre to assume when the host names none.
     *
     * This is what makes the conversion invisible to an existing installation:
     * it keeps running on the address it always had, and that address means
     * centre #1. Set APP_DEFAULT_CENTRE to its slug for the rollout, then clear
     * it once real subdomains are in use — with it empty, the apex becomes the
     * login and centre-picker page it is meant to be.
     */
    private function default(): ?Centre
    {
        $slug = config('app.default_centre');

        if (blank($slug)) {
            return null;
        }

        return Cache::remember(
            Centre::cacheKey($slug),
            now()->addSeconds(60),
            fn() => Centre::where('slug', $slug)->first()
        );
    }
}
