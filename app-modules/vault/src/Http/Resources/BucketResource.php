<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Resources;

use He4rt\Vault\Buckets\Bucket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Bucket
 */
class BucketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'share_token' => $this->share_token,
            'name' => $this->name,
            'is_shared' => $this->isShared(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
