<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership: which people belong to which centre.
 *
 * This is deliberately NOT derived from `model_has_roles.centre_id`. That table
 * answers "what may this person do here"; this one answers "is this person here
 * at all", and the two are not the same question:
 *
 *  - revoking a role must not silently delete the membership;
 *  - an invited-but-not-yet-active member has no role yet;
 *  - `percent` is a per-centre fact about a teacher, not a permission;
 *  - the shared session cookie is gated on membership, and a gate that runs the
 *    same query as the permission check is not a second line of defence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centre_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('centre_id')->constrained('centres')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // 0 = taklif qilingan, 1 = faol, 2 = to'xtatilgan
            $table->unsignedTinyInteger('status')->default(1);

            // Teacher payout share, moved off users.percent: a teacher may work
            // at two centres on different terms.
            $table->unsignedTinyInteger('percent')->nullable();

            // Which centre the picker opens on when somebody belongs to several.
            $table->boolean('is_default')->default(false);

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();

            $table->unique(['centre_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centre_user');
    }
};
