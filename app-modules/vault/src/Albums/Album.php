<?php

declare(strict_types=1);

namespace He4rt\Vault\Albums;

use App\Models\BaseModel;
use He4rt\Vault\Buckets\Bucket;
use He4rt\Vault\Database\Factories\Albums\AlbumFactory;
use He4rt\Vault\Files\File;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property string $bucket_id
 * @property string $name
 * @property bool $is_shared
 * @property string|null $share_token
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Bucket $bucket
 * @property-read Collection|File[] $files
 */
#[UsePolicy(AlbumPolicy::class)]
#[UseFactory(AlbumFactory::class)]
final class Album extends BaseModel
{
    protected $table = 'vault_albums';

    /**
     * @return BelongsTo<Bucket, $this>
     */
    public function bucket(): BelongsTo
    {
        return $this->belongsTo(Bucket::class, 'bucket_id');
    }

    /**
     * @return HasMany<File, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'album_id');
    }

    public function isShared(): bool
    {
        return $this->is_shared && $this->share_token !== null;
    }

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
        ];
    }
}
