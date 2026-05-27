<?php

declare(strict_types=1);

namespace He4rt\Vault\Files\Actions;

use He4rt\Vault\Events\FileDeleted;
use He4rt\Vault\Files\File;
use Illuminate\Support\Facades\Storage;

class DeleteFileAction
{
    public function handle(File $file): void
    {
        $disk = Storage::disk(config('vault.storage.disk'));

        $disk->delete($file->storage_path);

        if ($file->thumbnail_path !== null) {
            $disk->delete($file->thumbnail_path);
        }

        event(new FileDeleted($file));

        $file->forceDelete();
    }
}
