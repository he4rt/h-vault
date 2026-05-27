<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Resources;

use He4rt\Vault\Albums\Album;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Album
 */
class AlbumResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bucket_token' => $this->bucket->token,
            'name' => $this->name,
            'is_shared' => $this->is_shared,
            'share_token' => $this->share_token,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
