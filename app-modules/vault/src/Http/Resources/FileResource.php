<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Resources;

use He4rt\Vault\Files\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin File
 */
class FileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'url' => sprintf('/f/%s/%s', $this->token, $this->original_name),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'status' => $this->status->value,
            'is_protected' => $this->isProtected(),
            'is_one_time' => $this->is_one_time,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
