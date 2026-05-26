# PRD-01: Vault Core — File Model, Upload API, Storage

**Status:** Draft
**Priority:** P0
**Module:** `vault`
**Prerequisites:** `identity` module (exists)
**Depends on:** None
**Blocked by:** None

## Summary

Create the `vault` module with the `File` model, S3 storage via MinIO, upload API (anonymous + authenticated), and the event contracts that all downstream modules depend on.

## Motivation

The vault is the heart of H-Vault. Every other module (content-pipeline, security, panels) depends on files existing in the system. This PRD establishes the core data model, storage, and API that everything else builds on.

## Scope

### In Scope

- Module scaffolding via `internachi/modular`
- `File` model with `vault_files` migration (WITHOUT `bucket_id`/`album_id` — see ADR-0002)
- `FileStatus` enum: `pending`, `clean`, `infected`, `quarantined`
- `StoreFileAction`: validates, stores to S3 (MinIO), creates record
- `DeleteFileAction`: removes from S3, deletes record
- `FileController` with `PUT /api/v1/files` (upload), `GET /api/v1/files/{token}` (info), `PATCH /api/v1/files/{token}` (update), `DELETE /api/v1/files/{token}` (delete)
- `StoreFileRequest` with validation (mimes, max size based on auth status)
- `FileResource` (API Resource for JSON responses)
- `FilePolicy` for authorization
- Laravel Sanctum installation and configuration
- Auth token endpoint: `POST /api/v1/auth/token`
- Hybrid auth: anonymous uploads (100MB limit) + authenticated uploads (500MB limit)
- Events: `FileUploaded`, `FileDeleted`, `FileStatusChanged` (dispatched, no listeners yet)
- `config/vault.php` with all configurable values
- MinIO configuration in Docker Compose (already defined)
- Database factories and seeders
- Feature tests for all endpoints

### Out of Scope

- Buckets and Albums (PRD-03)
- File serving/download (PRD-02)
- TTL calculation and expiry (PRD-04)
- Thumbnails, antivirus (PRD-05)
- Rate limiting (PRD-06)
- Any UI (PRDs 07, 08)

## Data Model

### `vault_files` migration

```php
Schema::create('vault_files', function (Blueprint $table) {
    $table->id();
    $table->uuid('token')->unique();
    $table->foreignId('uploaded_by')->nullable()->constrained('identity_users');
    $table->string('original_name', 255);
    $table->string('storage_path', 512);
    $table->string('mime_type', 127);
    $table->unsignedBigInteger('file_size');
    $table->string('password_hash', 255)->nullable();
    $table->string('status', 20)->default('pending');
    $table->string('thumbnail_path', 512)->nullable();
    $table->boolean('is_one_time')->default(false);
    $table->timestamp('expires_at')->nullable()->index();
    $table->timestamps();
    $table->softDeletes();
});
```

Note: `bucket_id` and `album_id` are NOT included here. They will be added by PRD-03 via a separate migration (see ADR-0002).

### `FileStatus` Enum

```php
enum FileStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Infected = 'infected';
    case Quarantined = 'quarantined';
}
```

## Configuration

```php
// config/vault.php
return [
    'upload' => [
        'max_size_mb' => env('VAULT_UPLOAD_LIMIT_MB', 100),
        'max_size_auth_mb' => env('VAULT_UPLOAD_LIMIT_AUTH_MB', 500),
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

## API Endpoints

### `PUT /api/v1/files` — Upload

```
Headers:
  Authorization: Bearer {token}  (optional)
  Content-Type: multipart/form-data

Body:
  file: (binary)               required
  password: (string)           optional
  expires_in: (string)         optional, e.g. "24h", "7d"
  is_one_time: (boolean)       optional, default false

Response 201:
  {
    "data": {
      "token": "uuid",
      "url": "/f/{token}/{filename}",
      "original_name": "photo.png",
      "mime_type": "image/png",
      "file_size": 245760,
      "status": "pending",
      "is_protected": true,
      "expires_at": "2026-06-02T14:30:00Z",
      "created_at": "2026-05-26T14:30:00Z"
    }
  }
```

### `GET /api/v1/files/{token}` — File Info

### `PATCH /api/v1/files/{token}` — Update (password, TTL)

### `DELETE /api/v1/files/{token}` — Delete

### `POST /api/v1/auth/token` — Generate Sanctum Token

## Events

All events are dispatched in this PRD. No listeners exist yet.

| Event               | Dispatched by      | Payload                                            |
| :------------------ | :----------------- | :------------------------------------------------- |
| `FileUploaded`      | `StoreFileAction`  | `File $file`                                       |
| `FileDeleted`       | `DeleteFileAction` | `File $file`                                       |
| `FileStatusChanged` | `File` model       | `File $file, string $oldStatus, string $newStatus` |

## Upload Flow

```
 USER                              SYSTEM
  │                                   │
  │  📤 PUT /api/v1/files             │
  │  (multipart: file + password?)    │
  │ ──────────────────────────────►  │
  │                                   │  StoreFileRequest: validate
  │                                   │    mimes: allowed_mimes
  │                                   │    size: max_size_mb or max_size_auth_mb
  │                                   │  StoreFileAction:
  │                                   │    Storage::disk('s3')->putFileAs(...)
  │                                   │    File::create([token: uuid, ...])
  │                                   │    FileUploaded::dispatch($file)
  │                                   │  validation: ✓
  │                                   │
  │    201 Created                    │
  │    {"data": {"token": "a1b2.."}}  │
  │ ◄────────────────────────────────│
```

## Acceptance Criteria

- [ ] `vault` module created via `internachi/modular`
- [ ] `vault_files` table created via migration (no bucket/album FKs)
- [ ] Anonymous upload works via `curl` without auth
- [ ] Authenticated upload works with Sanctum Bearer token and higher limits
- [ ] File stored in MinIO S3 bucket under `files/{year}/{month}/{uuid}.{ext}`
- [ ] Token endpoint generates Sanctum tokens
- [ ] Upload returns JSON response with token, URL, expiry
- [ ] Password hashed with Argon2 when provided
- [ ] Events dispatched on upload and delete
- [ ] PATCH endpoint allows updating password and TTL
- [ ] DELETE endpoint removes file from S3 and database
- [ ] Config values loaded from `config/vault.php`
- [ ] Feature tests cover: anonymous upload, auth upload, size limit enforcement, mime validation, CRUD operations, invalid token 404
