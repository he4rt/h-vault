<?php

declare(strict_types=1);

namespace He4rt\Vault\Files\Actions;

use DateTimeInterface;
use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileUploaded;
use He4rt\Vault\Files\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreFileAction
{
    public function handle(
        UploadedFile $uploadedFile,
        ?User $user = null,
        ?string $password = null,
        bool $isOneTime = false,
        ?DateTimeInterface $expiresAt = null,
    ): File {
        $token = Str::uuid()->toString();
        $extension = $uploadedFile->getClientOriginalExtension() ?: 'bin';
        $storagePath = sprintf(
            '%s/%s/%s.%s',
            config('vault.storage.path_prefix'),
            now()->format('Y/m'),
            $token,
            $extension,
        );

        Storage::disk(config('vault.storage.disk'))->putFileAs(
            dirname($storagePath),
            $uploadedFile,
            basename($storagePath),
        );

        $file = File::query()->create([
            'token' => $token,
            'uploaded_by' => $user?->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'storage_path' => $storagePath,
            'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
            'file_size' => $uploadedFile->getSize(),
            'password_hash' => $password !== null ? Hash::make($password) : null,
            'status' => FileStatus::Pending,
            'is_one_time' => $isOneTime,
            'expires_at' => $expiresAt ?? now()->addDays($this->calculateDefaultTtlDays($uploadedFile, $user)),
        ]);

        event(new FileUploaded($file));

        return $file;
    }

    private function calculateDefaultTtlDays(UploadedFile $file, ?User $user): int
    {
        $isAuthenticated = $user instanceof User;
        $configKey = $isAuthenticated ? 'vault.ttl.authenticated' : 'vault.ttl.anonymous';

        $minDays = config($configKey.'.min_days');
        $maxDays = config($configKey.'.max_days');

        $maxSizeMb = $isAuthenticated
            ? config('vault.upload.max_size_auth_mb')
            : config('vault.upload.max_size_mb');

        $maxSizeBytes = $maxSizeMb * 1024 * 1024;
        $ratio = min($file->getSize() / $maxSizeBytes, 1.0);

        $ttlDays = $minDays + ($maxDays - $minDays) * ((1 - $ratio) ** 3);

        return (int) round($ttlDays);
    }
}
