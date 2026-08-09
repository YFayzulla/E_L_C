<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Support teacher" roli.
 *
 * Vazifasi tor: oylik testni va dars jarayonidagi ko'nikmalarni baholash.
 * Davomat, uy vazifasi, to'lov va talaba boshqaruvi unga tegishli emas.
 *
 * Guruhga oddiy o'qituvchi kabi `group_teachers` orqali biriktiriladi —
 * AuthorizesGroupAccess rolga qaramaydi, shuning uchun huquq avtomatik
 * to'g'ri ishlaydi. Farqi: admin uni istalgan va istalgancha guruhga
 * qo'sha oladi, va oyligi guruh to'lovlaridan hisoblanmaydi.
 *
 * Xom INSERT, Role modeli emas: Spatie teams yoqilgan bo'lsa model joriy
 * markazni talab qiladi, migratsiya esa markazsiz ishlaydi. Bu aynan
 * `add_parent_role` migratsiyasini yiqitgan tuzoq edi.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.roles', 'roles');
        $now = now();

        // insertOrIgnore: takror yurgizish unique indeksda yiqilmasin.
        DB::table($table)->insertOrIgnore([
            'name'       => 'support',
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $roles = config('permission.table_names.roles', 'roles');
        $assignments = config('permission.table_names.model_has_roles', 'model_has_roles');

        $id = DB::table($roles)->where('name', 'support')->where('guard_name', 'web')->value('id');

        if ($id === null) {
            return;
        }

        // Biriktirishlar avval: aks holda FK to'sib qoladi va rolni
        // o'chirib bo'lmaydi.
        DB::table($assignments)->where('role_id', $id)->delete();
        DB::table($roles)->where('id', $id)->delete();
    }
};
