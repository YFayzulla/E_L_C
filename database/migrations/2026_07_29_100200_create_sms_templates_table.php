<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Reusable SMS wordings, so staff do not retype the same message about a
     * missed lesson or a mark twenty times a day.
     *
     * The body carries {placeholders} which are substituted from the student's
     * own data at send time — see App\Models\SmsTemplate::render().
     */
    public function up()
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 64)->unique();
            // absence | late | grade | homework | payment | general
            $table->string('event', 32)->default('general');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'event'], 'sms_templates_active_event_idx');
        });

        $now = now();

        // Seeded here rather than in a seeder so a fresh `php artisan migrate`
        // leaves the centre with something usable on day one. They are ordinary
        // rows — edit or delete them freely.
        DB::table('sms_templates')->insert([
            [
                'name' => 'Darsga kelmadi',
                'slug' => 'absence',
                'event' => 'absence',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} {sana} kuni {guruh} guruhidagi darsga kelmadi. {markaz}',
                'is_active' => true, 'sort_order' => 10,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Darsga kechikdi',
                'slug' => 'late',
                'event' => 'late',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} {sana} kuni {guruh} guruhidagi darsga kechikib keldi. {markaz}',
                'is_active' => true, 'sort_order' => 20,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Bir necha darsni qoldirdi',
                'slug' => 'absence-repeated',
                'event' => 'absence',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} so‘nggi paytda {qoldirgan} ta darsni qoldirdi. Iltimos, biz bilan bog‘laning. {markaz}',
                'is_active' => true, 'sort_order' => 30,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Test natijasi',
                'slug' => 'grade',
                'event' => 'grade',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} {sana} kuni bo‘lib o‘tgan testdan {baho} ball to‘pladi. {markaz}',
                'is_active' => true, 'sort_order' => 40,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Oylik o‘zlashtirish',
                'slug' => 'progress',
                'event' => 'grade',
                'body' => 'Hurmatli ota-ona! {talaba}ning {guruh} guruhidagi o‘zlashtirishi: {reyting}%, davomati {davomat}%. {markaz}',
                'is_active' => true, 'sort_order' => 50,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Uy vazifasini bajarmadi',
                'slug' => 'homework-missing',
                'event' => 'homework',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} uy vazifasini bajarmadi. Iltimos, e’tibor qarating. {markaz}',
                'is_active' => true, 'sort_order' => 60,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'To‘lov eslatmasi',
                'slug' => 'payment-reminder',
                'event' => 'payment',
                'body' => 'Hurmatli ota-ona! {talaba} uchun {oy} oyi to‘lovi kutilmoqda. Qarzdorlik: {qarz} so‘m. {markaz}',
                'is_active' => true, 'sort_order' => 70,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Umumiy xabar',
                'slug' => 'general',
                'event' => 'general',
                'body' => 'Hurmatli ota-ona! ',
                'is_active' => true, 'sort_order' => 90,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('sms_templates');
    }
};
