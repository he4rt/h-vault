<?php

declare(strict_types=1);

namespace He4rt\Vault\Buckets\Actions;

use He4rt\Identity\Users\User;
use He4rt\Vault\Buckets\Bucket;
use He4rt\Vault\Events\BucketCreated;
use Illuminate\Support\Str;

class CreateBucketAction
{
    public function handle(User $user, ?string $name = null): Bucket
    {
        $bucket = Bucket::query()->create([
            'user_id' => $user->id,
            'token' => Str::uuid()->toString(),
            'share_token' => null,
            'name' => $name,
        ]);

        event(new BucketCreated($bucket));

        return $bucket;
    }
}
