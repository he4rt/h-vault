<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBucketRequest extends FormRequest
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
        return [
            'name' => ['nullable', 'string', 'min:1', 'max:255'],
        ];
    }
}
