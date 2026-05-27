<?php

declare(strict_types=1);

namespace He4rt\Vault\Buckets;

use App\Models\BaseModel;
use He4rt\Identity\Users\User;
use He4rt\Vault\Albums\Album;
use He4rt\Vault\Database\Factories\Buckets\BucketFactory;
use He4rt\Vault\Files\File;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property string $user_id
 * @property string $token
 * @property string|null $share_token
 * @property string|null $name
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read User $user
 * @property-read Collection|Album[] $albums
 * @property-read Collection|File[] $files
 */
#[UsePolicy(BucketPolicy::class)]
#[UseFactory(BucketFactory::class)]
final class Bucket extends BaseModel
{
    protected $table = 'vault_buckets';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Album, $this>
     */
    public function albums(): HasMany
    {
        return $this->hasMany(Album::class, 'bucket_id');
    }

    /**
     * @return HasMany<File, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'bucket_id');
    }

    public function isShared(): bool
    {
        return $this->share_token !== null;
    }
}
