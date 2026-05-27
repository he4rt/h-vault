<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = auth('sanctum')->user();

        $maxSizeMb = $user
            ? config('vault.upload.max_size_auth_mb')
            : config('vault.upload.max_size_mb');

        $maxSizeKb = $maxSizeMb * 1024;

        return [
            'file' => ['required', 'file', 'max:'.$maxSizeKb],
            'password' => ['nullable', 'string', 'min:1', 'max:255'],
            'expires_in' => ['nullable', 'string', 'regex:/^\d+[hdwm]$/'],
            'is_one_time' => ['nullable', 'boolean'],
        ];
    }
}
