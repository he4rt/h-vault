<?php

declare(strict_types=1);

namespace He4rt\Vault\TTL;

use He4rt\Vault\Events\FileExpired;
use He4rt\Vault\Files\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanExpiredFilesCommand extends Command
{
    protected $signature = 'vault:clean-expired';

    protected $description = 'Delete files that have passed their expiration date';

    public function handle(): int
    {
        $count = 0;
        $disk = Storage::disk(config('vault.storage.disk'));

        File::query()
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($files) use ($disk, &$count): void {
                foreach ($files as $file) {
                    $disk->delete($file->storage_path);

                    if ($file->thumbnail_path) {
                        $disk->delete($file->thumbnail_path);
                    }

                    event(new FileExpired($file));
                    $file->forceDelete();
                    $count++;
                }
            });

        $this->info(sprintf('Cleaned %d expired files.', $count));

        return self::SUCCESS;
    }
}
