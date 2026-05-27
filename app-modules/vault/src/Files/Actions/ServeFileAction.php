<?php

declare(strict_types=1);

namespace He4rt\Vault\Files\Actions;

use He4rt\Vault\Events\FileDownloaded;
use He4rt\Vault\Files\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServeFileAction
{
    public function handle(string $token, ?string $password = null): StreamedResponse
    {
        $file = Cache::remember(
            'file:'.$token,
            300,
            fn () => File::withTrashed()->where('token', $token)->first(),
        );

        abort_if($file === null, 404, 'File not found');

        $fileIsSoftDeleted = $file->trashed();
        abort_if($fileIsSoftDeleted, 410, 'File no longer available');

        $fileIsExpired = $file->isExpired();
        abort_if($fileIsExpired, 404, 'File not found');

        $fileRequiresPassword = $file->isProtected();
        if ($fileRequiresPassword) {
            $passwordIsMissing = $password === null || $password === '';
            abort_if($passwordIsMissing, 403, 'Password required');
            $passwordIsIncorrect = !Hash::check($password, $file->password_hash);
            abort_if($passwordIsIncorrect, 403, 'Password required');
        }

        event(new FileDownloaded($file));

        if ($file->is_one_time) {
            $file->delete();
            Cache::forget('file:'.$token);
        }

        $stream = Storage::disk(config('vault.storage.disk'))->readStream($file->storage_path);

        $response = new StreamedResponse(function () use ($stream): void {
            fpassthru($stream);
        });

        $response->headers->set('Content-Type', $file->mime_type);
        $response->headers->set('Content-Disposition', 'inline; filename="'.$file->original_name.'"');

        return $response;
    }
}
