<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** Every user needs a distinct phone: `users.phone` is unique. */
    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition()
    {
        $n = ++self::$sequence;

        return [
            'name'              => $this->faker->name(),
            // 998900000001, ...002 — normalised exactly as the app stores it.
            'phone'             => User::normalizePhone(str_pad((string) $n, 9, '0', STR_PAD_LEFT)),
            'email'             => Str::lower(Str::random(10)) . '@example.test',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified()
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** No address on file — such an account is never blocked by verification. */
    public function withoutEmail()
    {
        return $this->state(fn(array $attributes) => [
            'email'             => null,
            'email_verified_at' => null,
        ]);
    }
}
