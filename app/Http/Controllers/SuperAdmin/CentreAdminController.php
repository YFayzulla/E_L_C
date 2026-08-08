<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Centre;
use App\Models\User;
use App\Services\CentreProvisioner;
use App\Tenancy\CentreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Platforma egasining paneli.
 *
 * Bu yerga faqat `users.is_super_admin` bayrog'i bor odam kiradi
 * (`super-admin` middleware). Spatie roli ishlatilmaydi va ishlatib
 * bo'lmaydi ham: `model_has_roles.centre_id` NOT NULL va birlamchi kalit
 * tarkibida, ya'ni markazsiz rol biriktirish imkonsiz — super-admin esa
 * aynan markazlardan yuqorida turadi.
 *
 * Markaz ochish CentreProvisioner orqali: konsol buyrug'i ham shuni
 * chaqiradi, shunda panel va CLI bir-biridan uzoqlashib ketmaydi.
 */
class CentreAdminController extends Controller
{
    public function __construct(
        private CentreProvisioner $provisioner,
        private CentreContext $context,
    ) {
    }

    public function index()
    {
        $centres = Centre::withCount('users')->orderBy('id')->get();

        // Rol biriktirishlar markazga bog'langan, shuning uchun sanoq
        // har birining o'z kontekstida olinadi.
        $counts = [];

        foreach ($centres as $centre) {
            $counts[$centre->id] = $this->context->for($centre, fn () => [
                'admin'   => User::role('admin')->count(),
                'teacher' => User::role('user')->count(),
                'student' => User::role('student')->count(),
            ]);
        }

        return view('super.centres.index', [
            'centres' => $centres,
            'counts'  => $counts,
            'owners'  => User::where('is_super_admin', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('super.centres.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug'               => 'required|string|max:63',
            'name'               => 'required|string|max:255',
            'certificate_prefix' => 'nullable|string|max:8',
            'timezone'           => 'nullable|string|max:64',
            'admin_name'         => 'required|string|max:255',
            'admin_phone'        => 'required|string|max:20',
            'admin_email'        => 'nullable|email|max:255',
            'admin_password'     => 'required|string|min:6',
        ]);

        // Slug qoidasi provisioner'da — panel va CLI bitta manbadan
        // tekshirishi kerak, aks holda biri o'tkazib yuborgan nom
        // ikkinchisida yiqilardi.
        if ($problem = $this->provisioner->slugProblem($data['slug'])) {
            throw ValidationException::withMessages(['slug' => $problem]);
        }

        try {
            $centre = $this->provisioner->create([
                'slug'               => $data['slug'],
                'name'               => $data['name'],
                'certificate_prefix' => $data['certificate_prefix'] ?? null,
                'timezone'           => $data['timezone'] ?? null,
                'admin' => [
                    'name'     => $data['admin_name'],
                    'phone'    => $data['admin_phone'],
                    'email'    => $data['admin_email'] ?? null,
                    'password' => $data['admin_password'],
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('CentreAdminController@store: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Markaz ochishda xatolik: ' . $e->getMessage());
        }

        return redirect()->route('super.centres.index')
            ->with('success', "«{$centre->name}» ochildi — {$centre->host()}");
    }

    public function edit(Centre $centre)
    {
        return view('super.centres.edit', compact('centre'));
    }

    public function update(Request $request, Centre $centre)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'legal_name'         => 'nullable|string|max:255',
            'phone'              => 'nullable|string|max:32',
            'address'            => 'nullable|string|max:255',
            'certificate_prefix' => 'nullable|string|max:8',
            'timezone'           => 'nullable|string|max:64',
            'brand_color'        => 'nullable|string|max:9',
            'sms_email'          => 'nullable|email|max:255',
            'sms_password'       => 'nullable|string|max:255',
            'sms_from'           => 'nullable|string|max:32',
            'sms_enabled'        => 'nullable|boolean',
            'status'             => ['required', Rule::in([
                Centre::STATUS_ACTIVE, Centre::STATUS_SUSPENDED, Centre::STATUS_ARCHIVED,
            ])],
        ]);

        // Bo'sh qoldirilgan parol — "o'zgartirmang" degani, "o'chiring" emas.
        if (blank($data['sms_password'] ?? null)) {
            unset($data['sms_password']);
        }

        $data['sms_enabled'] = $request->boolean('sms_enabled');

        // saved() hodisasi ResolveCentre keshini tozalaydi, ya'ni holat
        // o'zgarishi darhol kuchga kiradi.
        $centre->update($data);

        return redirect()->route('super.centres.index')
            ->with('success', "«{$centre->name}» yangilandi.");
    }

    /**
     * Markazga admin biriktirish. Telefon bo'yicha odam bor bo'lsa yangi
     * hisob yaratilmaydi — u shu markazda ham admin bo'ladi.
     */
    public function attachAdmin(Request $request, Centre $centre)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|max:20',
            'email'    => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
        ]);

        try {
            $user = $this->provisioner->attachAdmin($centre, [
                'name'     => $data['name'],
                'phone'    => $data['phone'],
                'email'    => $data['email'] ?? null,
                'password' => $data['password'] ?? '',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        return back()->with('success', "{$user->name} «{$centre->name}» markaziga admin qilib biriktirildi.");
    }

    /**
     * Super-admin huquqini berish yoki olib tashlash.
     */
    public function toggleOwner(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'grant' => 'required|boolean',
        ]);

        $phone = User::normalizePhone($data['phone']);
        $user = $phone ? User::where('phone', $phone)->first() : null;

        if ($user === null) {
            return back()->with('error', 'Bunday raqamli foydalanuvchi topilmadi.');
        }

        $grant = (bool) $data['grant'];

        // Oxirgi super-adminni olib tashlash — platformani qulflab qo'yish:
        // markaz ochadigan hech kim qolmaydi va buni faqat bazadan qaytarib
        // bo'ladi.
        if (! $grant && User::where('is_super_admin', true)->where('id', '!=', $user->id)->doesntExist()) {
            return back()->with('error',
                'Bu — oxirgi super-admin. Olib tashlansa, markaz ochadigan hech kim qolmaydi.');
        }

        $user->forceFill(['is_super_admin' => $grant])->save();

        return back()->with('success', $user->name . ' — '
            . ($grant ? 'super-admin qilindi.' : 'super-adminlikdan olindi.'));
    }
}
