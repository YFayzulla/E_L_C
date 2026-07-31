<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O'quv markazlari — the tenants.
 *
 * Anything the app filters, indexes, encrypts or LIKEs gets a real column:
 * `slug` and `status` are read on every single request, `certificate_prefix`
 * goes into a LIKE, and the Eskiz password needs an encrypted cast and has to
 * be rotatable. Only the grading knobs live in `settings`, because they are
 * never queried and the set will keep growing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centres', function (Blueprint $table) {
            $table->id();

            // The subdomain label: `alpha` -> alpha.domen.uz
            $table->string('slug', 63)->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();

            // 0 = faol, 1 = to'xtatilgan, 2 = arxiv
            $table->unsignedTinyInteger('status')->default(0)->index();

            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('locale', 8)->default('uz');
            $table->string('timezone', 64)->default('Asia/Tashkent');

            // Branding
            $table->string('logo_path')->nullable();
            $table->string('brand_color', 9)->nullable();

            // Certificates. The counter lives here so two centres issuing on the
            // same day cannot race for one serial — see Certificate::nextSerial().
            $table->string('certificate_prefix', 8)->default('CRT');
            $table->unsignedInteger('certificate_counter')->default(0);

            // Each centre has its own Kutish zali. Nullable because the group is
            // created after the centre row exists.
            $table->unsignedBigInteger('waiting_room_group_id')->nullable();

            // Eskiz. Empty means "use the platform account from config/eskiz.php".
            $table->string('sms_email')->nullable();
            $table->text('sms_password')->nullable();   // cast: encrypted
            $table->string('sms_from', 32)->nullable();
            $table->boolean('sms_enabled')->default(false);

            // Grading overrides only — never credentials.
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('centres', function (Blueprint $table) {
            $table->foreign('waiting_room_group_id')
                ->references('id')->on('groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centres');
    }
};
