<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Controllers\Api;

use DateTimeInterface;
use He4rt\Identity\Users\User;
use He4rt\Vault\Files\Actions\DeleteFileAction;
use He4rt\Vault\Files\Actions\StoreFileAction;
use He4rt\Vault\Files\File;
use He4rt\Vault\Http\Requests\StoreFileRequest;
use He4rt\Vault\Http\Resources\FileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class FileController
{
    public function store(StoreFileRequest $request, StoreFileAction $action): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        $expiresAt = null;
        if ($request->filled('expires_in')) {
            $expiresAt = $this->parseExpiresIn($request->string('expires_in')->toString());
        }

        $file = $action->handle(
            uploadedFile: $request->file('file'),
            user: $user,
            password: $request->string('password')->toString() ?: null,
            isOneTime: $request->boolean('is_one_time'),
            expiresAt: $expiresAt,
        );

        return new FileResource($file)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $token): FileResource
    {
        $file = File::query()->where('token', $token)->firstOrFail();

        return new FileResource($file);
    }

    public function update(Request $request, string $token): FileResource
    {
        $file = File::query()->where('token', $token)->firstOrFail();

        $this->authorizeFileOwner($request, $file);

        $data = [];

        if ($request->has('password')) {
            $password = $request->string('password')->toString();
            $data['password_hash'] = $password !== '' ? Hash::make($password) : null;
        }

        if ($request->filled('expires_in')) {
            $data['expires_at'] = $this->parseExpiresIn($request->string('expires_in')->toString());
        }

        if ($data !== []) {
            $file->update($data);
        }

        return new FileResource($file->fresh());
    }

    public function destroy(Request $request, string $token, DeleteFileAction $action): JsonResponse
    {
        $file = File::query()->where('token', $token)->firstOrFail();

        $this->authorizeFileOwner($request, $file);

        $action->handle($file);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function parseExpiresIn(string $expiresIn): DateTimeInterface
    {
        preg_match('/^(\d+)([hdwm])$/', $expiresIn, $matches);

        $value = (int) $matches[1];

        return match ($matches[2]) {
            'h' => now()->addHours($value),
            'd' => now()->addDays($value),
            'w' => now()->addWeeks($value),
            'm' => now()->addMonths($value),
        };
    }

    private function authorizeFileOwner(Request $request, File $file): void
    {
        if ($file->uploaded_by === null) {
            return;
        }

        abort_if($request->user()?->id !== $file->uploaded_by, Response::HTTP_FORBIDDEN);
    }
}
