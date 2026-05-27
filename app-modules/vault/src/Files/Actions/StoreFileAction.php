<?php

declare(strict_types=1);

namespace He4rt\Vault\Files\Actions;

use DateTimeInterface;
use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileUploaded;
use He4rt\Vault\Files\File;
use He4rt\Vault\TTL\TtlCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreFileAction
{
    public function __construct(
        private readonly TtlCalculator $ttlCalculator,
    ) {}

    public function handle(
        UploadedFile $uploadedFile,
        ?User $user = null,
        ?string $password = null,
        bool $isOneTime = false,
        ?DateTimeInterface $expiresAt = null,
        ?string $bucketId = null,
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
            'bucket_id' => $bucketId,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'storage_path' => $storagePath,
            'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
            'file_size' => $uploadedFile->getSize(),
            'password_hash' => $password !== null ? Hash::make($password) : null,
            'status' => FileStatus::Pending,
            'is_one_time' => $isOneTime,
            'expires_at' => $expiresAt ?? $this->ttlCalculator->calculateExpiresAt($uploadedFile->getSize(), $user),
        ]);

        event(new FileUploaded($file));

        return $file;
    }
}
