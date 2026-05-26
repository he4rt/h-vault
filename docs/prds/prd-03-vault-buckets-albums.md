# PRD-03: Vault Buckets & Albums

**Status:** Draft
**Priority:** P1
**Module:** `vault`
**Prerequisites:** PRD-01 (vault-core)
**Depends on:** PRD-01
**Blocked by:** None after PRD-01

## Summary

Add Bucket and Album models, API endpoints, and the FK migration that links files to buckets and albums. Requires Sanctum authentication.

## Motivation

Authenticated users need organizational structures for their files. Buckets group files by user, Albums group files within a bucket for sharing and bulk download.

## Scope

### In Scope

- `Bucket` model with `vault_buckets` migration
- `Album` model with `vault_albums` migration
- Migration to add `bucket_id` and `album_id` FKs to `vault_files` (see ADR-0002)
- `CreateBucketAction`
- `BucketController`, `AlbumController` with full CRUD
- `StoreBucketRequest`, `StoreAlbumRequest`
- `BucketResource`, `AlbumResource` (API Resources)
- `BucketPolicy`, `AlbumPolicy`
- `BucketCreated` event
- Update `StoreFileAction` to accept optional `bucket_id`
- All bucket/album endpoints require Sanctum auth
- Share tokens for buckets and albums (public read-only access)
- Database factories and seeders
- Feature tests

### Out of Scope

- ZIP download of albums (PRD-05 content-pipeline)
- UI for managing buckets/albums (PRD-08 panel-app)

## Data Model

### `vault_buckets` migration

```php
Schema::create('vault_buckets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('identity_users');
    $table->uuid('token')->unique();
    $table->uuid('share_token')->nullable()->unique();
    $table->string('name', 255)->nullable();
    $table->timestamps();
});
```

### `vault_albums` migration

```php
Schema::create('vault_albums', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bucket_id')->constrained('vault_buckets')->cascadeOnDelete();
    $table->string('name', 255);
    $table->boolean('is_shared')->default(false);
    $table->uuid('share_token')->nullable()->unique();
    $table->timestamps();
});
```

### Add FKs to `vault_files`

```php
Schema::table('vault_files', function (Blueprint $table) {
    $table->foreignId('bucket_id')->nullable()->after('token')->constrained('vault_buckets')->nullOnDelete();
    $table->foreignId('album_id')->nullable()->after('bucket_id')->constrained('vault_albums')->nullOnDelete();
});
```

## API Endpoints

### Buckets (all require Sanctum auth)

```
POST   /api/v1/buckets                 — Create bucket
GET    /api/v1/buckets/{token}         — Bucket info
DELETE /api/v1/buckets/{token}         — Delete bucket (cascades to albums, nullifies file FKs)
GET    /api/v1/buckets/{token}/files   — List files in bucket
```

### Albums (all require Sanctum auth, scoped to bucket)

```
POST   /api/v1/buckets/{bucket}/albums                — Create album
GET    /api/v1/buckets/{bucket}/albums/{album}         — Album info
DELETE /api/v1/buckets/{bucket}/albums/{album}         — Delete album
```

### Shared access (public, no auth)

```
GET    /api/v1/shared/buckets/{share_token}            — Public bucket info + files
GET    /api/v1/shared/albums/{share_token}             — Public album info + files
```

## Events

| Event           | Dispatched by        | Payload          |
| :-------------- | :------------------- | :--------------- |
| `BucketCreated` | `CreateBucketAction` | `Bucket $bucket` |

## Acceptance Criteria

- [ ] `vault_buckets` and `vault_albums` tables created
- [ ] `bucket_id` and `album_id` FK columns added to `vault_files`
- [ ] Bucket CRUD via API with Sanctum auth
- [ ] Album CRUD scoped to bucket
- [ ] File upload accepts optional `bucket_id` for authenticated users
- [ ] Share tokens enable public read-only access
- [ ] Deleting a bucket cascades to albums, nullifies file FKs
- [ ] `BucketCreated` event dispatched
- [ ] Feature tests cover: bucket CRUD, album CRUD, file-bucket association, share token access, unauthorized access returns 403
