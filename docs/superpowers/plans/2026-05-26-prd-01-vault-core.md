# PRD-01: Vault Core Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the `vault` module with the File model, upload/CRUD API, S3 storage, Sanctum auth, and event contracts.

**Architecture:** Modular monolith via `internachi/modular`. The vault module lives in `app-modules/vault/` with namespace `He4rt\Vault`. Models extend `App\Models\BaseModel` (UUID primary keys, activity logging). Events are dispatched with no listeners yet (ADR-0001). Migrations are incremental — no bucket/album FKs in this PRD (ADR-0002).

**Tech Stack:** Laravel 13, PHP 8.4+, Pest 4, PostgreSQL 18, MinIO S3, Laravel Sanctum, internachi/modular 3.x

---

### Task 1: Install Sanctum and Configure API Auth

**Files:**

- Modify: `composer.json` (add sanctum dependency)
- Modify: `.env.example` (add vault env vars)
- Modify: `docker-compose.yml` (add MinIO service)
- Modify: `app/Enums/FilamentPanel.php` (add App panel)

- [ ] **Step 1: Install Sanctum**

Run:

```bash
composer require laravel/sanctum
php artisan install:api
```

This creates `routes/api.php` and the `personal_access_tokens` migration.

- [ ] **Step 2: Add MinIO service to docker-compose.yml**

Add after the `sycorax-mailpit` service:

```yaml
sycorax-minio:
    image: minio/minio:latest
    container_name: sycorax-minio
    restart: no
    hostname: dev
    command: server /data --console-address ":9001"
    environment:
        MINIO_ROOT_USER: minioadmin
        MINIO_ROOT_PASSWORD: minioadmin
    ports:
        - '9000:9000'
        - '9001:9001'
    volumes:
        - sycorax-minio:/data
    networks:
        - dev-sycorax
    healthcheck:
        test: ['CMD', 'mc', 'ready', 'local']
        interval: 10s
        retries: 5
        timeout: 10s
        start_period: 15s
```

Add the volume to the `volumes` section:

```yaml
sycorax-minio:
    name: sycorax-minio
```

- [ ] **Step 3: Update .env.example with vault and S3 vars**

Append to `.env.example`:

```env
# Vault
VAULT_UPLOAD_LIMIT_MB=100
VAULT_UPLOAD_LIMIT_AUTH_MB=500
VAULT_ALLOWED_MIMES="image/*,video/*,audio/*,application/pdf,application/zip"
VAULT_STORAGE_DISK=s3
VAULT_TTL_ANON_MIN_DAYS=1
VAULT_TTL_ANON_MAX_DAYS=7
VAULT_TTL_AUTH_MIN_DAYS=1
VAULT_TTL_AUTH_MAX_DAYS=30
VAULT_TTL_CUSTOM_OVERRIDE=true
VAULT_RATE_LIMIT_ANON=10
VAULT_RATE_LIMIT_AUTH=60
```

Update the existing AWS section to have MinIO defaults:

```env
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=h-vault
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

- [ ] **Step 4: Add App panel to FilamentPanel enum**

In `app/Enums/FilamentPanel.php`, add the `App` case for future use by panel-app (PRD-08):

```php
enum FilamentPanel: string
{
    case Admin = 'admin';
    case App = 'app';
    case KnowledgeBase = 'knowledge-base';
}
```

- [ ] **Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "chore: install Sanctum, add MinIO to docker-compose, add vault env vars"
```

---

### Task 2: Scaffold the Vault Module

**Files:**

- Create: `app-modules/vault/composer.json`
- Create: `app-modules/vault/src/VaultServiceProvider.php`
- Create: `app-modules/vault/config/vault.php`
- Create: `app-modules/vault/phpstan.neon`

- [ ] **Step 1: Create module directory structure**

```bash
mkdir -p app-modules/vault/{src/{Files/Actions,Http/{Controllers/Api,Requests,Resources},Events,Enums},config,database/{migrations/files,factories/Files,seeders},resources/routes,tests/{Feature,Unit},lang}
```

- [ ] **Step 2: Create module composer.json**

Create `app-modules/vault/composer.json`:

```json
{
    "name": "he4rt/vault",
    "description": "Core vault module: files, upload API, storage",
    "type": "library",
    "version": "1.0.0",
    "license": "proprietary",
    "autoload": {
        "psr-4": {
            "He4rt\\Vault\\": "src/",
            "He4rt\\Vault\\Database\\Factories\\": "database/factories/",
            "He4rt\\Vault\\Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "He4rt\\Vault\\Tests\\": "tests/"
        }
    },
    "minimum-stability": "stable",
    "extra": {
        "laravel": {
            "providers": ["He4rt\\Vault\\VaultServiceProvider"]
        }
    }
}
```

- [ ] **Step 3: Create config/vault.php**

Create `app-modules/vault/config/vault.php`:

```php
<?php

declare(strict_types=1);

return [
    'upload' => [
        'max_size_mb' => (int) env('VAULT_UPLOAD_LIMIT_MB', 100),
        'max_size_auth_mb' => (int) env('VAULT_UPLOAD_LIMIT_AUTH_MB', 500),
        'allowed_mimes' => explode(',', env('VAULT_ALLOWED_MIMES', 'image/*,video/*,audio/*,application/pdf,application/zip')),
    ],
    'storage' => [
        'disk' => env('VAULT_STORAGE_DISK', 's3'),
        'path_prefix' => 'files',
    ],
    'ttl' => [
        'anonymous' => [
            'min_days' => (int) env('VAULT_TTL_ANON_MIN_DAYS', 1),
            'max_days' => (int) env('VAULT_TTL_ANON_MAX_DAYS', 7),
        ],
        'authenticated' => [
            'min_days' => (int) env('VAULT_TTL_AUTH_MIN_DAYS', 1),
            'max_days' => (int) env('VAULT_TTL_AUTH_MAX_DAYS', 30),
        ],
        'custom_override' => (bool) env('VAULT_TTL_CUSTOM_OVERRIDE', true),
    ],
    'rate_limit' => [
        'anonymous' => (int) env('VAULT_RATE_LIMIT_ANON', 10),
        'authenticated' => (int) env('VAULT_RATE_LIMIT_AUTH', 60),
    ],
];
```

- [ ] **Step 4: Create VaultServiceProvider**

Create `app-modules/vault/src/VaultServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault;

use He4rt\Vault\Files\File;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class VaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vault.php', 'vault');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/*');
        $this->loadRoutesFrom(__DIR__.'/../resources/routes/api.php');

        Relation::enforceMorphMap([
            'vault_file' => File::class,
        ]);
    }
}
```

- [ ] **Step 5: Create phpstan.neon**

Create `app-modules/vault/phpstan.neon`:

```neon
includes:
    - phpstan-baseline.neon

parameters:
    level: 5
    paths:
        - src
```

Create `app-modules/vault/phpstan-baseline.neon`:

```neon
parameters:
    ignoreErrors: []
```

- [ ] **Step 6: Register module in root composer.json and update autoload**

Run:

```bash
composer require he4rt/vault:^1.0.0
```

If that fails due to module not being auto-discovered, run:

```bash
composer dump-autoload
php artisan package:discover
php artisan modules:sync --no-phpunit
```

- [ ] **Step 7: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "feat(vault): scaffold vault module with service provider and config"
```

---

### Task 3: FileStatus Enum and File Model

**Files:**

- Create: `app-modules/vault/src/Enums/FileStatus.php`
- Create: `app-modules/vault/database/migrations/files/2026_05_26_000001_create_vault_files_table.php`
- Create: `app-modules/vault/src/Files/File.php`
- Create: `app-modules/vault/src/Files/FilePolicy.php`
- Create: `app-modules/vault/database/factories/Files/FileFactory.php`

- [ ] **Step 1: Create FileStatus enum**

Create `app-modules/vault/src/Enums/FileStatus.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum FileStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Infected = 'infected';
    case Quarantined = 'quarantined';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Clean => 'Clean',
            self::Infected => 'Infected',
            self::Quarantined => 'Quarantined',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Pending => Color::Yellow,
            self::Clean => Color::Green,
            self::Infected => Color::Red,
            self::Quarantined => Color::Orange,
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Clean => Heroicon::CheckCircle,
            self::Infected => Heroicon::ExclamationTriangle,
            self::Quarantined => Heroicon::ShieldExclamation,
        };
    }
}
```

- [ ] **Step 2: Create vault_files migration**

Create `app-modules/vault/database/migrations/files/2026_05_26_000001_create_vault_files_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('token')->unique();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('identity_users');
            $table->string('original_name', 255);
            $table->string('storage_path', 512);
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('file_size');
            $table->string('password_hash', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('thumbnail_path', 512)->nullable();
            $table->boolean('is_one_time')->default(false);
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }
};
```

- [ ] **Step 3: Create File model**

Create `app-modules/vault/src/Files/File.php`:

```php
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

        FileStatusChanged::dispatch($this, $oldStatus, $newStatus);
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
```

- [ ] **Step 4: Create FilePolicy**

Create `app-modules/vault/src/Files/FilePolicy.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Files;

use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, File $file): bool
    {
        return true;
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(?User $user, File $file): bool
    {
        if ($file->uploaded_by === null) {
            return false;
        }

        return $user !== null && $user->id === $file->uploaded_by;
    }

    public function delete(?User $user, File $file): bool
    {
        if ($file->uploaded_by === null) {
            return true;
        }

        return $user !== null && $user->id === $file->uploaded_by;
    }
}
```

- [ ] **Step 5: Create FileFactory**

Create `app-modules/vault/database/factories/Files/FileFactory.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Database\Factories\Files;

use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<File>
 */
final class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        $fileName = fake()->word().'.'.fake()->fileExtension();

        return [
            'token' => Str::uuid()->toString(),
            'uploaded_by' => null,
            'original_name' => $fileName,
            'storage_path' => 'files/'.now()->format('Y/m/').Str::uuid().'.'.(pathinfo($fileName, PATHINFO_EXTENSION) ?: 'bin'),
            'mime_type' => 'image/png',
            'file_size' => fake()->numberBetween(1024, 10 * 1024 * 1024),
            'password_hash' => null,
            'status' => FileStatus::Pending,
            'thumbnail_path' => null,
            'is_one_time' => false,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function authenticated(): static
    {
        return $this->state(fn () => [
            'uploaded_by' => User::factory(),
        ]);
    }

    public function protected(string $password = 'secret'): static
    {
        return $this->state(fn () => [
            'password_hash' => bcrypt($password),
        ]);
    }

    public function oneTime(): static
    {
        return $this->state(fn () => [
            'is_one_time' => true,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function clean(): static
    {
        return $this->state(fn () => [
            'status' => FileStatus::Clean,
        ]);
    }

    public function withMime(string $mimeType): static
    {
        return $this->state(fn () => [
            'mime_type' => $mimeType,
        ]);
    }
}
```

- [ ] **Step 6: Run migration to verify**

Run:

```bash
php artisan migrate
```

Expected: `vault_files` table created successfully.

- [ ] **Step 7: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "feat(vault): add File model, migration, factory, policy, and FileStatus enum"
```

---

### Task 4: Events

**Files:**

- Create: `app-modules/vault/src/Events/FileUploaded.php`
- Create: `app-modules/vault/src/Events/FileDeleted.php`
- Create: `app-modules/vault/src/Events/FileStatusChanged.php`

- [ ] **Step 1: Create FileUploaded event**

Create `app-modules/vault/src/Events/FileUploaded.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Events;

use He4rt\Vault\Files\File;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileUploaded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly File $file,
    ) {}
}
```

- [ ] **Step 2: Create FileDeleted event**

Create `app-modules/vault/src/Events/FileDeleted.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Events;

use He4rt\Vault\Files\File;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly File $file,
    ) {}
}
```

- [ ] **Step 3: Create FileStatusChanged event**

Create `app-modules/vault/src/Events/FileStatusChanged.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Events;

use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly File $file,
        public readonly FileStatus $oldStatus,
        public readonly FileStatus $newStatus,
    ) {}
}
```

- [ ] **Step 4: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "feat(vault): add FileUploaded, FileDeleted, FileStatusChanged events"
```

---

### Task 5: StoreFileAction

**Files:**

- Create: `app-modules/vault/src/Files/Actions/StoreFileAction.php`
- Create: `app-modules/vault/tests/Unit/Files/Actions/StoreFileActionTest.php`

- [ ] **Step 1: Write failing test for StoreFileAction**

Create `app-modules/vault/tests/Unit/Files/Actions/StoreFileActionTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileUploaded;
use He4rt\Vault\Files\Actions\StoreFileAction;
use He4rt\Vault\Files\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('s3');
    Event::fake([FileUploaded::class]);
});

test('stores file anonymously', function (): void {
    $uploadedFile = UploadedFile::fake()->image('photo.png', 100, 100);

    $action = app(StoreFileAction::class);
    $file = $action->handle(
        uploadedFile: $uploadedFile,
    );

    expect($file)->toBeInstanceOf(File::class)
        ->and($file->original_name)->toBe('photo.png')
        ->and($file->mime_type)->toBe('image/png')
        ->and($file->file_size)->toBeGreaterThan(0)
        ->and($file->uploaded_by)->toBeNull()
        ->and($file->status)->toBe(FileStatus::Pending)
        ->and($file->is_one_time)->toBeFalse()
        ->and($file->token)->not->toBeEmpty()
        ->and($file->expires_at)->not->toBeNull();

    Storage::disk('s3')->assertExists($file->storage_path);
    Event::assertDispatched(FileUploaded::class, fn (FileUploaded $e) => $e->file->id === $file->id);
});

test('stores file with authenticated user', function (): void {
    $user = User::factory()->create();
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $action = app(StoreFileAction::class);
    $file = $action->handle(
        uploadedFile: $uploadedFile,
        user: $user,
    );

    expect($file->uploaded_by)->toBe($user->id);
});

test('stores file with password', function (): void {
    $uploadedFile = UploadedFile::fake()->image('secret.png');

    $action = app(StoreFileAction::class);
    $file = $action->handle(
        uploadedFile: $uploadedFile,
        password: 'my-secret',
    );

    expect($file->password_hash)->not->toBeNull()
        ->and($file->isProtected())->toBeTrue();
});

test('stores file as one-time', function (): void {
    $uploadedFile = UploadedFile::fake()->image('once.png');

    $action = app(StoreFileAction::class);
    $file = $action->handle(
        uploadedFile: $uploadedFile,
        isOneTime: true,
    );

    expect($file->is_one_time)->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=StoreFileAction`

Expected: FAIL — `StoreFileAction` class not found.

- [ ] **Step 3: Implement StoreFileAction**

Create `app-modules/vault/src/Files/Actions/StoreFileAction.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Files\Actions;

use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileUploaded;
use He4rt\Vault\Files\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreFileAction
{
    public function handle(
        UploadedFile $uploadedFile,
        ?User $user = null,
        ?string $password = null,
        bool $isOneTime = false,
        ?\DateTimeInterface $expiresAt = null,
    ): File {
        $token = Str::uuid()->toString();
        $extension = $uploadedFile->getClientOriginalExtension() ?: 'bin';
        $storagePath = sprintf(
            '%s/%s/%s.%s',
            config('vault.storage.path_prefix'),
            now()->format('Y/m'),
            $token,
            $extension,
        );

        Storage::disk(config('vault.storage.disk'))->putFileAs(
            dirname($storagePath),
            $uploadedFile,
            basename($storagePath),
        );

        $file = File::query()->create([
            'token' => $token,
            'uploaded_by' => $user?->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'storage_path' => $storagePath,
            'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
            'file_size' => $uploadedFile->getSize(),
            'password_hash' => $password !== null ? Hash::make($password) : null,
            'status' => FileStatus::Pending,
            'is_one_time' => $isOneTime,
            'expires_at' => $expiresAt ?? now()->addDays($this->calculateDefaultTtlDays($uploadedFile, $user)),
        ]);

        FileUploaded::dispatch($file);

        return $file;
    }

    private function calculateDefaultTtlDays(UploadedFile $file, ?User $user): int
    {
        $isAuthenticated = $user !== null;
        $configKey = $isAuthenticated ? 'vault.ttl.authenticated' : 'vault.ttl.anonymous';

        $minDays = config("{$configKey}.min_days");
        $maxDays = config("{$configKey}.max_days");

        $maxSizeMb = $isAuthenticated
            ? config('vault.upload.max_size_auth_mb')
            : config('vault.upload.max_size_mb');

        $maxSizeBytes = $maxSizeMb * 1024 * 1024;
        $ratio = min($file->getSize() / $maxSizeBytes, 1.0);

        $ttlDays = $minDays + ($maxDays - $minDays) * ((1 - $ratio) ** 3);

        return (int) round($ttlDays);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=StoreFileAction`

Expected: All 4 tests PASS.

- [ ] **Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "feat(vault): add StoreFileAction with TTL calculation and event dispatch"
```

---

### Task 6: DeleteFileAction

**Files:**

- Create: `app-modules/vault/src/Files/Actions/DeleteFileAction.php`
- Create: `app-modules/vault/tests/Unit/Files/Actions/DeleteFileActionTest.php`

- [ ] **Step 1: Write failing test for DeleteFileAction**

Create `app-modules/vault/tests/Unit/Files/Actions/DeleteFileActionTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Vault\Events\FileDeleted;
use He4rt\Vault\Files\Actions\DeleteFileAction;
use He4rt\Vault\Files\File;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('s3');
    Event::fake([FileDeleted::class]);
});

test('deletes file from storage and database', function (): void {
    $file = File::factory()->create();
    Storage::disk('s3')->put($file->storage_path, 'file-content');

    $action = app(DeleteFileAction::class);
    $action->handle($file);

    Storage::disk('s3')->assertMissing($file->storage_path);
    expect(File::withTrashed()->find($file->id))->toBeNull();
    Event::assertDispatched(FileDeleted::class, fn (FileDeleted $e) => $e->file->id === $file->id);
});

test('deletes file with thumbnail from storage', function (): void {
    $file = File::factory()->create(['thumbnail_path' => 'thumbnails/2026/05/test_thumb.webp']);
    Storage::disk('s3')->put($file->storage_path, 'file-content');
    Storage::disk('s3')->put($file->thumbnail_path, 'thumb-content');

    $action = app(DeleteFileAction::class);
    $action->handle($file);

    Storage::disk('s3')->assertMissing($file->storage_path);
    Storage::disk('s3')->assertMissing($file->thumbnail_path);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DeleteFileAction`

Expected: FAIL — `DeleteFileAction` class not found.

- [ ] **Step 3: Implement DeleteFileAction**

Create `app-modules/vault/src/Files/Actions/DeleteFileAction.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Files\Actions;

use He4rt\Vault\Events\FileDeleted;
use He4rt\Vault\Files\File;
use Illuminate\Support\Facades\Storage;

class DeleteFileAction
{
    public function handle(File $file): void
    {
        $disk = Storage::disk(config('vault.storage.disk'));

        $disk->delete($file->storage_path);

        if ($file->thumbnail_path !== null) {
            $disk->delete($file->thumbnail_path);
        }

        FileDeleted::dispatch($file);

        $file->forceDelete();
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=DeleteFileAction`

Expected: All 2 tests PASS.

- [ ] **Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "feat(vault): add DeleteFileAction with S3 cleanup and event dispatch"
```

---

### Task 7: API Request and Resource

**Files:**

- Create: `app-modules/vault/src/Http/Requests/StoreFileRequest.php`
- Create: `app-modules/vault/src/Http/Resources/FileResource.php`

- [ ] **Step 1: Create StoreFileRequest**

Create `app-modules/vault/src/Http/Requests/StoreFileRequest.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxSizeMb = $this->user()
            ? config('vault.upload.max_size_auth_mb')
            : config('vault.upload.max_size_mb');

        $maxSizeKb = $maxSizeMb * 1024;

        return [
            'file' => ['required', 'file', "max:{$maxSizeKb}"],
            'password' => ['nullable', 'string', 'min:1', 'max:255'],
            'expires_in' => ['nullable', 'string', 'regex:/^\d+[hdwm]$/'],
            'is_one_time' => ['nullable', 'boolean'],
        ];
    }
}
```

- [ ] **Step 2: Create FileResource**

Create `app-modules/vault/src/Http/Resources/FileResource.php`:

```php
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
            'url' => "/f/{$this->token}/{$this->original_name}",
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
```

- [ ] **Step 3: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "feat(vault): add StoreFileRequest validation and FileResource"
```

---

### Task 8: FileController and API Routes

**Files:**

- Create: `app-modules/vault/src/Http/Controllers/Api/FileController.php`
- Create: `app-modules/vault/src/Http/Controllers/Api/AuthTokenController.php`
- Create: `app-modules/vault/resources/routes/api.php`

- [ ] **Step 1: Create FileController**

Create `app-modules/vault/src/Http/Controllers/Api/FileController.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Controllers\Api;

use He4rt\Vault\Files\Actions\DeleteFileAction;
use He4rt\Vault\Files\Actions\StoreFileAction;
use He4rt\Vault\Files\File;
use He4rt\Vault\Http\Requests\StoreFileRequest;
use He4rt\Vault\Http\Resources\FileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class FileController
{
    public function store(StoreFileRequest $request, StoreFileAction $action): JsonResponse
    {
        $expiresAt = null;

        if ($request->filled('expires_in')) {
            $expiresAt = $this->parseExpiresIn($request->string('expires_in')->toString());
        }

        $file = $action->handle(
            uploadedFile: $request->file('file'),
            user: $request->user(),
            password: $request->string('password')->toString() ?: null,
            isOneTime: $request->boolean('is_one_time'),
            expiresAt: $expiresAt,
        );

        return (new FileResource($file))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $token): FileResource
    {
        $file = File::query()->where('token', $token)->firstOrFail();

        return new FileResource($file);
    }

    public function update(Request $request, string $token): FileResource
    {
        $file = File::query()->where('token', $token)->firstOrFail();

        $this->authorizeFileOwner($request, $file);

        $data = [];

        if ($request->has('password')) {
            $password = $request->string('password')->toString();
            $data['password_hash'] = $password !== '' ? Hash::make($password) : null;
        }

        if ($request->filled('expires_in')) {
            $data['expires_at'] = $this->parseExpiresIn($request->string('expires_in')->toString());
        }

        if ($data !== []) {
            $file->update($data);
        }

        return new FileResource($file->fresh());
    }

    public function destroy(Request $request, string $token, DeleteFileAction $action): JsonResponse
    {
        $file = File::query()->where('token', $token)->firstOrFail();

        $this->authorizeFileOwner($request, $file);

        $action->handle($file);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function parseExpiresIn(string $expiresIn): \DateTimeInterface
    {
        preg_match('/^(\d+)([hdwm])$/', $expiresIn, $matches);

        $value = (int) $matches[1];

        return match ($matches[2]) {
            'h' => now()->addHours($value),
            'd' => now()->addDays($value),
            'w' => now()->addWeeks($value),
            'm' => now()->addMonths($value),
        };
    }

    private function authorizeFileOwner(Request $request, File $file): void
    {
        if ($file->uploaded_by === null) {
            return;
        }

        if ($request->user()?->id !== $file->uploaded_by) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}
```

- [ ] **Step 2: Create AuthTokenController**

Create `app-modules/vault/src/Http/Controllers/Api/AuthTokenController.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthTokenController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(
                ['error' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $token = $request->user()->createToken($request->string('device_name')->toString());

        return response()->json([
            'token' => $token->plainTextToken,
        ], Response::HTTP_CREATED);
    }
}
```

- [ ] **Step 3: Create API routes**

Create `app-modules/vault/resources/routes/api.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Vault\Http\Controllers\Api\AuthTokenController;
use He4rt\Vault\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1')->group(function (): void {
    Route::post('auth/token', [AuthTokenController::class, 'store'])
        ->name('vault.auth.token');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::put('files', [FileController::class, 'store'])
            ->withoutMiddleware('auth:sanctum')
            ->name('vault.files.store');

        Route::get('files/{token}', [FileController::class, 'show'])
            ->withoutMiddleware('auth:sanctum')
            ->name('vault.files.show');

        Route::patch('files/{token}', [FileController::class, 'update'])
            ->name('vault.files.update');

        Route::delete('files/{token}', [FileController::class, 'destroy'])
            ->name('vault.files.destroy');
    });
});
```

Note: The `store` and `show` routes have `withoutMiddleware('auth:sanctum')` so they work for both anonymous and authenticated users. The optional auth is handled by Sanctum's `auth:sanctum` guard which populates `$request->user()` when a token is present, or leaves it null. We need to use the stateful API approach — update the route to use `optional` auth instead:

Replace the routes file with:

```php
<?php

declare(strict_types=1);

use He4rt\Vault\Http\Controllers\Api\AuthTokenController;
use He4rt\Vault\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1')->group(function (): void {
    Route::post('auth/token', [AuthTokenController::class, 'store'])
        ->name('vault.auth.token');

    Route::put('files', [FileController::class, 'store'])
        ->middleware('auth:sanctum')
        ->name('vault.files.store');

    Route::get('files/{token}', [FileController::class, 'show'])
        ->name('vault.files.show');

    Route::patch('files/{token}', [FileController::class, 'update'])
        ->middleware('auth:sanctum')
        ->name('vault.files.update');

    Route::delete('files/{token}', [FileController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('vault.files.destroy');
});
```

Wait — the upload route needs to work for both anonymous AND authenticated. Sanctum's `auth:sanctum` middleware returns 401 if no token is present. We need a custom approach. Let's remove `auth:sanctum` from the upload route and handle optional auth inside the controller:

```php
<?php

declare(strict_types=1);

use He4rt\Vault\Http\Controllers\Api\AuthTokenController;
use He4rt\Vault\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api/v1')->group(function (): void {
    Route::post('auth/token', [AuthTokenController::class, 'store'])
        ->name('vault.auth.token');

    Route::put('files', [FileController::class, 'store'])
        ->name('vault.files.store');

    Route::get('files/{token}', [FileController::class, 'show'])
        ->name('vault.files.show');

    Route::patch('files/{token}', [FileController::class, 'update'])
        ->middleware('auth:sanctum')
        ->name('vault.files.update');

    Route::delete('files/{token}', [FileController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('vault.files.destroy');
});
```

Then update `StoreFileRequest` and `FileController::store()` to resolve the user manually from the bearer token when present. Update `StoreFileRequest::authorize()` to always return true, and update `rules()` to resolve the user via `auth('sanctum')->user()`:

Update `StoreFileRequest`:

```php
public function rules(): array
{
    $user = auth('sanctum')->user();

    $maxSizeMb = $user
        ? config('vault.upload.max_size_auth_mb')
        : config('vault.upload.max_size_mb');

    $maxSizeKb = $maxSizeMb * 1024;

    return [
        'file' => ['required', 'file', "max:{$maxSizeKb}"],
        'password' => ['nullable', 'string', 'min:1', 'max:255'],
        'expires_in' => ['nullable', 'string', 'regex:/^\d+[hdwm]$/'],
        'is_one_time' => ['nullable', 'boolean'],
    ];
}
```

Update `FileController::store()` to resolve user via sanctum guard:

```php
public function store(StoreFileRequest $request, StoreFileAction $action): JsonResponse
{
    $user = auth('sanctum')->user();

    // ... use $user instead of $request->user()
}
```

- [ ] **Step 4: Verify routes are registered**

Run: `php artisan route:list --path=api/v1`

Expected: 5 routes listed — auth/token, files (PUT, GET, PATCH, DELETE).

- [ ] **Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "feat(vault): add FileController, AuthTokenController, and API routes"
```

---

### Task 9: Feature Tests for File API

**Files:**

- Create: `app-modules/vault/tests/Feature/Files/FileUploadTest.php`
- Create: `app-modules/vault/tests/Feature/Files/FileCrudTest.php`
- Create: `app-modules/vault/tests/Feature/Auth/AuthTokenTest.php`

- [ ] **Step 1: Create upload feature tests**

Create `app-modules/vault/tests/Feature/Files/FileUploadTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileUploaded;
use He4rt\Vault\Files\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('s3');
    Event::fake([FileUploaded::class]);
});

test('anonymous user can upload a file', function (): void {
    $response = $this->putJson(route('vault.files.store'), [
        'file' => UploadedFile::fake()->image('photo.png', 100, 100),
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['token', 'url', 'original_name', 'mime_type', 'file_size', 'status', 'is_protected', 'expires_at', 'created_at'],
        ]);

    expect(File::query()->count())->toBe(1);

    $file = File::query()->first();
    expect($file->uploaded_by)->toBeNull()
        ->and($file->status)->toBe(FileStatus::Pending);

    Storage::disk('s3')->assertExists($file->storage_path);
    Event::assertDispatched(FileUploaded::class);
});

test('authenticated user can upload a file with higher limits', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson(route('vault.files.store'), [
            'file' => UploadedFile::fake()->create('large.zip', 200 * 1024),
        ]);

    $response->assertCreated();

    $file = File::query()->first();
    expect($file->uploaded_by)->toBe($user->id);
});

test('anonymous upload rejects files exceeding size limit', function (): void {
    $maxSizeKb = config('vault.upload.max_size_mb') * 1024;

    $response = $this->putJson(route('vault.files.store'), [
        'file' => UploadedFile::fake()->create('huge.zip', $maxSizeKb + 1),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

test('upload with password creates protected file', function (): void {
    $response = $this->putJson(route('vault.files.store'), [
        'file' => UploadedFile::fake()->image('secret.png'),
        'password' => 'my-secret',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.is_protected', true);
});

test('upload with is_one_time flag', function (): void {
    $response = $this->putJson(route('vault.files.store'), [
        'file' => UploadedFile::fake()->image('once.png'),
        'is_one_time' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.is_one_time', true);
});

test('upload with custom expires_in', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson(route('vault.files.store'), [
            'file' => UploadedFile::fake()->image('timed.png'),
            'expires_in' => '24h',
        ]);

    $response->assertCreated();

    $file = File::query()->first();
    expect($file->expires_at->diffInHours(now()))->toBeBetween(23, 25);
});

test('upload without file returns validation error', function (): void {
    $response = $this->putJson(route('vault.files.store'));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});
```

- [ ] **Step 2: Create CRUD feature tests**

Create `app-modules/vault/tests/Feature/Files/FileCrudTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use He4rt\Vault\Events\FileDeleted;
use He4rt\Vault\Files\File;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

test('can get file info by token', function (): void {
    $file = File::factory()->create();

    $response = $this->getJson(route('vault.files.show', ['token' => $file->token]));

    $response->assertOk()
        ->assertJsonPath('data.token', $file->token)
        ->assertJsonPath('data.original_name', $file->original_name);
});

test('returns 404 for non-existent token', function (): void {
    $response = $this->getJson(route('vault.files.show', ['token' => 'non-existent']));

    $response->assertNotFound();
});

test('owner can update file password', function (): void {
    $user = User::factory()->create();
    $file = File::factory()->create(['uploaded_by' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->patchJson(route('vault.files.update', ['token' => $file->token]), [
            'password' => 'new-password',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.is_protected', true);
});

test('non-owner cannot update file', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $file = File::factory()->create(['uploaded_by' => $owner->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->patchJson(route('vault.files.update', ['token' => $file->token]), [
            'password' => 'hack',
        ]);

    $response->assertForbidden();
});

test('owner can delete file', function (): void {
    Storage::fake('s3');
    Event::fake([FileDeleted::class]);

    $user = User::factory()->create();
    $file = File::factory()->create(['uploaded_by' => $user->id]);
    Storage::disk('s3')->put($file->storage_path, 'content');

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson(route('vault.files.destroy', ['token' => $file->token]));

    $response->assertNoContent();
    expect(File::query()->find($file->id))->toBeNull();
    Event::assertDispatched(FileDeleted::class);
});

test('unauthenticated user cannot delete file', function (): void {
    $file = File::factory()->create(['uploaded_by' => User::factory()->create()->id]);

    $response = $this->deleteJson(route('vault.files.destroy', ['token' => $file->token]));

    $response->assertUnauthorized();
});
```

- [ ] **Step 3: Create auth token tests**

Create `app-modules/vault/tests/Feature/Auth/AuthTokenTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;

test('user can generate sanctum token with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson(route('vault.auth.token'), [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token']);
});

test('returns 401 with invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson(route('vault.auth.token'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
        'device_name' => 'test-device',
    ]);

    $response->assertUnauthorized();
});

test('returns validation errors for missing fields', function (): void {
    $response = $this->postJson(route('vault.auth.token'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});
```

- [ ] **Step 4: Run all vault tests**

Run: `php artisan test --compact --filter="He4rt\\\\Vault"`

Expected: All tests PASS.

- [ ] **Step 5: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "test(vault): add feature tests for file upload, CRUD, and auth token API"
```

---

### Task 10: Model Unit Tests and PHPStan

**Files:**

- Create: `app-modules/vault/tests/Unit/Files/FileTest.php`
- Create: `app-modules/vault/tests/Unit/Enums/FileStatusTest.php`

- [ ] **Step 1: Create File model unit tests**

Create `app-modules/vault/tests/Unit/Files/FileTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileStatusChanged;
use He4rt\Vault\Files\File;
use Illuminate\Support\Facades\Event;

test('file factory creates valid file', function (): void {
    $file = File::factory()->create();

    expect($file)->toBeInstanceOf(File::class)
        ->and($file->token)->not->toBeEmpty()
        ->and($file->original_name)->not->toBeEmpty()
        ->and($file->storage_path)->not->toBeEmpty()
        ->and($file->mime_type)->not->toBeEmpty()
        ->and($file->file_size)->toBeGreaterThan(0)
        ->and($file->status)->toBe(FileStatus::Pending);
});

test('isProtected returns true when password_hash is set', function (): void {
    $file = File::factory()->protected()->create();

    expect($file->isProtected())->toBeTrue();
});

test('isProtected returns false when password_hash is null', function (): void {
    $file = File::factory()->create();

    expect($file->isProtected())->toBeFalse();
});

test('isExpired returns true when expires_at is in the past', function (): void {
    $file = File::factory()->expired()->create();

    expect($file->isExpired())->toBeTrue();
});

test('isExpired returns false when expires_at is in the future', function (): void {
    $file = File::factory()->create(['expires_at' => now()->addDay()]);

    expect($file->isExpired())->toBeFalse();
});

test('changeStatus updates status and dispatches event', function (): void {
    Event::fake([FileStatusChanged::class]);

    $file = File::factory()->create(['status' => FileStatus::Pending]);

    $file->changeStatus(FileStatus::Clean);

    expect($file->fresh()->status)->toBe(FileStatus::Clean);

    Event::assertDispatched(FileStatusChanged::class, function (FileStatusChanged $e) use ($file): bool {
        return $e->file->id === $file->id
            && $e->oldStatus === FileStatus::Pending
            && $e->newStatus === FileStatus::Clean;
    });
});

test('uploader relation returns user', function (): void {
    $file = File::factory()->authenticated()->create();

    expect($file->uploader)->not->toBeNull()
        ->and($file->uploader->id)->toBe($file->uploaded_by);
});

test('uploader relation returns null for anonymous file', function (): void {
    $file = File::factory()->create();

    expect($file->uploader)->toBeNull();
});
```

- [ ] **Step 2: Create FileStatus enum tests**

Create `app-modules/vault/tests/Unit/Enums/FileStatusTest.php`:

```php
<?php

declare(strict_types=1);

use He4rt\Vault\Enums\FileStatus;

test('all statuses have labels', function (FileStatus $status): void {
    expect($status->getLabel())->toBeString()->not->toBeEmpty();
})->with(FileStatus::cases());

test('all statuses have colors', function (FileStatus $status): void {
    expect($status->getColor())->toBeArray()->not->toBeEmpty();
})->with(FileStatus::cases());

test('all statuses have icons', function (FileStatus $status): void {
    expect($status->getIcon())->not->toBeNull();
})->with(FileStatus::cases());
```

- [ ] **Step 3: Run all vault tests**

Run: `php artisan test --compact --filter="He4rt\\\\Vault"`

Expected: All tests PASS.

- [ ] **Step 4: Run PHPStan on vault module**

Run: `vendor/bin/phpstan analyse app-modules/vault/src --level=5`

Expected: No errors, or only baseline-worthy issues. If there are errors, fix them or add to the baseline.

- [ ] **Step 5: Run full check**

Run: `make check`

Expected: Pint, Rector, and PHPStan all pass.

- [ ] **Step 6: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "test(vault): add unit tests for File model, FileStatus enum, and pass PHPStan"
```

---

### Task 11: Database Seeder

**Files:**

- Create: `app-modules/vault/database/seeders/VaultSeeder.php`

- [ ] **Step 1: Create VaultSeeder**

Create `app-modules/vault/database/seeders/VaultSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Vault\Database\Seeders;

use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\File;
use Illuminate\Database\Seeder;

class VaultSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        File::factory()
            ->count(5)
            ->create(['status' => FileStatus::Clean]);

        if ($user) {
            File::factory()
                ->count(3)
                ->create([
                    'uploaded_by' => $user->id,
                    'status' => FileStatus::Clean,
                ]);

            File::factory()
                ->protected()
                ->create([
                    'uploaded_by' => $user->id,
                    'status' => FileStatus::Clean,
                ]);

            File::factory()
                ->oneTime()
                ->create([
                    'uploaded_by' => $user->id,
                    'status' => FileStatus::Clean,
                ]);
        }
    }
}
```

- [ ] **Step 2: Register seeder in root DatabaseSeeder if not auto-discovered**

Check if `database/seeders/DatabaseSeeder.php` exists. If it does, add a call to VaultSeeder:

```php
$this->call([
    // ... existing seeders
    \He4rt\Vault\Database\Seeders\VaultSeeder::class,
]);
```

- [ ] **Step 3: Run Pint and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "feat(vault): add VaultSeeder with sample files"
```

---

### Task 12: Final Verification

- [ ] **Step 1: Run full test suite**

Run: `make test`

Expected: All tests pass (both vault and existing identity tests).

- [ ] **Step 2: Run full check**

Run: `make check`

Expected: Rector, Pint, and PHPStan all pass.

- [ ] **Step 3: Verify routes**

Run: `php artisan route:list --path=api/v1`

Expected: 5 routes listed:

```
POST   api/v1/auth/token     vault.auth.token
PUT    api/v1/files           vault.files.store
GET    api/v1/files/{token}   vault.files.show
PATCH  api/v1/files/{token}   vault.files.update
DELETE api/v1/files/{token}   vault.files.destroy
```

- [ ] **Step 4: Verify migration**

Run: `php artisan migrate:fresh --seed`

Expected: `vault_files` table created and seeded.

- [ ] **Step 5: Smoke test with curl (requires dev server + MinIO running)**

Start MinIO and dev server:

```bash
make env-up
# Create h-vault bucket in MinIO at http://localhost:9001
make dev
```

Test anonymous upload:

```bash
curl -X PUT http://localhost:8000/api/v1/files \
  -F "file=@/path/to/any/image.png"
```

Expected: 201 response with token and file metadata.

- [ ] **Step 6: Final commit if any fixes were needed**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add -A
git commit -m "chore(vault): final verification fixes for PRD-01"
```
