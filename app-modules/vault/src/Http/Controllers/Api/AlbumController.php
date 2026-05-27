<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Controllers\Api;

use He4rt\Identity\Users\User;
use He4rt\Vault\Albums\Album;
use He4rt\Vault\Buckets\Bucket;
use He4rt\Vault\Http\Requests\StoreAlbumRequest;
use He4rt\Vault\Http\Resources\AlbumResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AlbumController
{
    public function store(StoreAlbumRequest $request, string $bucket): JsonResponse
    {
        $bucketModel = $this->resolveBucket($bucket);

        $this->authorizeBucketOwner($bucketModel);

        $album = Album::query()->create([
            'bucket_id' => $bucketModel->id,
            'name' => $request->string('name')->toString(),
            'is_shared' => false,
            'share_token' => null,
        ]);

        return new AlbumResource($album->load('bucket'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $bucket, string $album): AlbumResource
    {
        $bucketModel = $this->resolveBucket($bucket);

        $this->authorizeBucketOwner($bucketModel);

        $albumModel = Album::query()
            ->where('bucket_id', $bucketModel->id)
            ->where('id', $album)
            ->firstOrFail();

        return new AlbumResource($albumModel->load('bucket'));
    }

    public function destroy(string $bucket, string $album): JsonResponse
    {
        $bucketModel = $this->resolveBucket($bucket);

        $this->authorizeBucketOwner($bucketModel);

        $albumModel = Album::query()
            ->where('bucket_id', $bucketModel->id)
            ->where('id', $album)
            ->firstOrFail();

        $albumModel->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function resolveBucket(string $token): Bucket
    {
        abort_unless(Str::isUuid($token), Response::HTTP_NOT_FOUND);

        return Bucket::query()->where('token', $token)->firstOrFail();
    }

    private function authorizeBucketOwner(Bucket $bucket): void
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        abort_if($user?->id !== $bucket->user_id, Response::HTTP_FORBIDDEN);
    }
}
