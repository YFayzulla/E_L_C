<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * Grandfathers existing accounts: marks e-mail addresses as confirmed WITHOUT
 * sending anything, so `REQUIRE_EMAIL_VERIFICATION=true` can be switched on
 * without locking out people who were created before verification existed.
 *
 *   php artisan users:verify-email 42            # one account by id
 *   php artisan users:verify-email 901234567     # one account by phone
 *   php artisan users:verify-email --all         # everyone who has an address
 *   php artisan users:verify-email --role=user   # only teachers
 */
class VerifyUserEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:verify-email
                            {user? : Foydalanuvchi ID raqami yoki telefon raqami}
                            {--all : Pochta manzili bor barcha hisoblarni tasdiqlash}
                            {--role= : Faqat shu rolga ega hisoblar (admin|user|student|parent)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark user e-mail addresses as verified without sending any mail';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = $this->argument('user');
        $role = $this->option('role');
        $all = (bool) $this->option('all');

        if (! $identifier && ! $all && ! $role) {
            $this->error('Hech narsa tanlanmadi.');
            $this->line('Foydalanish: php artisan users:verify-email {id|telefon} | --all | --role=student');

            return self::INVALID;
        }

        try {
            $users = $this->resolveUsers($identifier, $role);
        } catch (RoleDoesNotExist $e) {
            $this->error('Bunday rol mavjud emas: ' . $role);

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Ma\'lumotlar bazasiga ulanib bo\'lmadi: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($users->isEmpty()) {
            $this->warn('Mos keladigan hisob topilmadi.');

            return self::SUCCESS;
        }

        $rows = [];
        $verified = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($users as $user) {
            if (blank($user->email)) {
                $rows[] = [$user->id, $user->name, $user->phone, '—', 'Pochtasi yo\'q'];
                $skipped++;
                continue;
            }

            if ($user->email_verified_at) {
                $rows[] = [$user->id, $user->name, $user->phone, $user->email, 'Avvaldan tasdiqlangan'];
                $skipped++;
                continue;
            }

            try {
                // `email_verified_at` is intentionally not fillable.
                $user->forceFill(['email_verified_at' => now()])->save();

                $rows[] = [$user->id, $user->name, $user->phone, $user->email, 'Tasdiqlandi'];
                $verified++;
            } catch (\Throwable $e) {
                $rows[] = [$user->id, $user->name, $user->phone, $user->email, 'Xatolik'];
                $failed++;
            }
        }

        $this->newLine();
        $this->table(['ID', 'Ism', 'Telefon', 'Pochta', 'Natija'], $rows);

        $this->info(sprintf(
            'Jami: %d | Tasdiqlandi: %d | O\'tkazib yuborildi: %d | Xatolik: %d',
            count($rows),
            $verified,
            $skipped,
            $failed
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Work out which accounts the operator meant.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\User>
     */
    private function resolveUsers(?string $identifier, ?string $role)
    {
        $query = User::query();

        if ($identifier !== null && $identifier !== '') {
            $phone = User::normalizePhone($identifier);

            return $query->where(function (Builder $q) use ($identifier, $phone) {
                if (ctype_digit($identifier)) {
                    $q->orWhere('id', (int) $identifier);
                }
                if ($phone !== null) {
                    $q->orWhere('phone', $phone);
                }
                $q->orWhere('phone', $identifier);
            })->get();
        }

        if ($role) {
            $query->role($role);
        }

        // Bulk mode only touches accounts that actually carry an address and are
        // still unconfirmed — re-running the command is therefore harmless.
        return $query->whereNotNull('email')
            ->whereNull('email_verified_at')
            ->orderBy('id')
            ->get();
    }
}
