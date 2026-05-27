<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Controllers\Api;

use He4rt\Vault\Albums\Album;
use He4rt\Vault\Buckets\Bucket;
use He4rt\Vault\Http\Resources\AlbumResource;
use He4rt\Vault\Http\Resources\BucketResource;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SharedController
{
    public function bucket(string $share_token): BucketResource
    {
        abort_unless(Str::isUuid($share_token), Response::HTTP_NOT_FOUND);

        $bucket = Bucket::query()
            ->where('share_token', $share_token)
            ->firstOrFail();

        return new BucketResource($bucket);
    }

    public function album(string $share_token): AlbumResource
    {
        abort_unless(Str::isUuid($share_token), Response::HTTP_NOT_FOUND);

        $album = Album::query()
            ->where('share_token', $share_token)
            ->where('is_shared', true)
            ->firstOrFail();

        return new AlbumResource($album->load('bucket'));
    }
}
