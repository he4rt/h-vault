<?php

declare(strict_types=1);

namespace He4rt\Vault\Files;

use App\Models\BaseModel;
use He4rt\Identity\Users\User;
use He4rt\Vault\Database\Factories\Files\FileFactory;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileStatusChanged;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property string $token
 * @property string|null $uploaded_by
 * @property string $original_name
 * @property string $storage_path
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $password_hash
 * @property FileStatus $status
 * @property string|null $thumbnail_path
 * @property bool $is_one_time
 * @property Carbon|null $expires_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Carbon|null $deleted_at
 * @property-read User|null $uploader
 */
#[UsePolicy(FilePolicy::class)]
#[UseFactory(FileFactory::class)]
final class File extends BaseModel
{
    use SoftDeletes;

    protected $table = 'vault_files';

    protected $hidden = [
        'password_hash',
        'storage_path',
        'deleted_at',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isProtected(): bool
    {
        return $this->password_hash !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function changeStatus(FileStatus $newStatus): void
    {
        $oldStatus = $this->status;
        $this->update(['status' => $newStatus]);
        event(new FileStatusChanged($this, $oldStatus, $newStatus));
    }

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'status' => FileStatus::class,
            'is_one_time' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }
}
