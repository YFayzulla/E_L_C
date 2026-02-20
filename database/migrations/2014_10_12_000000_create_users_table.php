<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('password');
            $table->unsignedBigInteger('phone')->nullable()->unique();
            $table->string('passport',9)->nullable()->unique();
            $table->date('date_born')->nullable();
            $table->string('location')->nullable();
            $table->string('description')->nullable();
            $table->string('parents_name')->nullable();
            // $table->unsignedBigInteger('parents_tel')->unique()->nullable();
            // $table->integer('group_id')->nullable(); // Removed group_id
            $table->string('photo')->nullable();
            $table->mediumInteger('should_pay')->nullable();
            $table->tinyinteger('status')->nullable();
            $table->integer('percent')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->integer('mark')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
