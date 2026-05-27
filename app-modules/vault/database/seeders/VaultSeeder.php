<?php

declare(strict_types=1);

namespace He4rt\Vault\Database\Seeders;

use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\File;
use Illuminate\Database\Seeder;

class VaultSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        File::factory()
            ->count(5)
            ->create(['status' => FileStatus::Clean]);

        if ($user) {
            File::factory()
                ->count(3)
                ->create([
                    'uploaded_by' => $user->id,
                    'status' => FileStatus::Clean,
                ]);

            File::factory()
                ->protected()
                ->create([
                    'uploaded_by' => $user->id,
                    'status' => FileStatus::Clean,
                ]);

            File::factory()
                ->oneTime()
                ->create([
                    'uploaded_by' => $user->id,
                    'status' => FileStatus::Clean,
                ]);
        }
    }
}
