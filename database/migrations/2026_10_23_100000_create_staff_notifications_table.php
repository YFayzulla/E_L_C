<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Ichki bildirishnomalar (navbardagi qo'ng'iroqcha).
|
| Laravel'ning `notifications` jadvali ishlatilmadi: u markazni bilmaydi, bitta
| odam esa ikki markazda admin bo'lishi mumkin — ikkinchi markazning xabari
| birinchisida ko'rinib qolardi. Bu jadval centre_id bilan, BelongsToCentre
| orqali boshqa tenant jadvallar kabi filtrlanadi.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('centre_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('type', 64);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('centre_id')->references('id')->on('centres')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['centre_id', 'user_id', 'read_at'], 'staff_notifications_inbox_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_notifications');
    }
};
