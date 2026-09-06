<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SetInstanceOwnerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kasme:set-owner
                            {--email= : Email user yang akan ditetapkan sebagai instance owner}
                            {--force : Lewati konfirmasi interaktif pada lingkungan produksi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tetapkan satu pengguna sebagai Instance Owner aplikasi KasMe';

    public function handle(): int
    {
        $email = $this->option('email');

        if (! $email) {
            $users = User::query()->orderBy('id')->get(['id', 'name', 'email', 'is_instance_owner']);

            if ($users->isEmpty()) {
                $this->error('Tidak ada pengguna yang terdaftar pada sistem KasMe.');

                return self::FAILURE;
            }

            $choices = $users->mapWithKeys(function (User $u) {
                $tag = $u->is_instance_owner ? ' [Owner Saat Ini]' : '';

                return [$u->email => "ID: {$u->id} | {$u->name} <{$u->email}>{$tag}"];
            })->all();

            $email = $this->choice('Pilih pengguna yang akan ditetapkan sebagai Instance Owner:', array_keys($choices));
        }

        /** @var User|null $targetUser */
        $targetUser = User::query()->where('email', $email)->first();

        if (! $targetUser) {
            $this->error("Pengguna dengan email '{$email}' tidak ditemukan.");

            return self::FAILURE;
        }

        if ($targetUser->is_instance_owner) {
            $this->info("Pengguna {$targetUser->name} <{$targetUser->email}> sudah menjadi Instance Owner.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && $this->getLaravel()->isProduction()) {
            if (! $this->confirm("Apakah Anda yakin ingin menetapkan {$targetUser->name} <{$targetUser->email}> sebagai Instance Owner?", true)) {
                $this->warn('Operasi dibatalkan.');

                return self::SUCCESS;
            }
        }

        try {
            DB::transaction(function () use ($targetUser): void {
                // Ensure there is strictly only ONE instance owner
                User::query()->where('is_instance_owner', true)->update(['is_instance_owner' => false]);
                $targetUser->is_instance_owner = true;
                $targetUser->saveQuietly();
            });

            $this->info("Berhasil! Pengguna {$targetUser->name} <{$targetUser->email}> telah ditetapkan sebagai Instance Owner KasMe.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Gagal menetapkan Instance Owner: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
