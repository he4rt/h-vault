<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Controllers\Api;

use He4rt\Identity\Users\User;
use He4rt\Vault\Buckets\Actions\CreateBucketAction;
use He4rt\Vault\Buckets\Bucket;
use He4rt\Vault\Http\Requests\StoreBucketRequest;
use He4rt\Vault\Http\Resources\BucketResource;
use He4rt\Vault\Http\Resources\FileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BucketController
{
    public function store(StoreBucketRequest $request, CreateBucketAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $bucket = $action->handle(
            user: $user,
            name: $request->string('name')->toString() ?: null,
        );

        return new BucketResource($bucket)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $token): BucketResource
    {
        abort_unless(Str::isUuid($token), Response::HTTP_NOT_FOUND);

        $bucket = Bucket::query()->where('token', $token)->firstOrFail();

        $this->authorizeBucketOwner($bucket);

        return new BucketResource($bucket);
    }

    public function destroy(string $token): JsonResponse
    {
        abort_unless(Str::isUuid($token), Response::HTTP_NOT_FOUND);

        $bucket = Bucket::query()->where('token', $token)->firstOrFail();

        $this->authorizeBucketOwner($bucket);

        $bucket->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function files(string $token): AnonymousResourceCollection
    {
        abort_unless(Str::isUuid($token), Response::HTTP_NOT_FOUND);

        $bucket = Bucket::query()->where('token', $token)->firstOrFail();

        $this->authorizeBucketOwner($bucket);

        $files = $bucket->files()->get();

        return FileResource::collection($files);
    }

    private function authorizeBucketOwner(Bucket $bucket): void
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        abort_if($user?->id !== $bucket->user_id, Response::HTTP_FORBIDDEN);
    }
}
