# PRD-05: Content Pipeline — Thumbnails, Zip, Scanner Contract

**Status:** Draft
**Priority:** P1
**Module:** `content-pipeline`
**Prerequisites:** PRD-01 (vault-core)
**Depends on:** PRD-01 (events)
**Blocked by:** None after PRD-01

## Summary

Create the `content-pipeline` module for async content processing: thumbnail generation for images, ZIP archive creation for albums, and the antivirus scanner contract with NullScanner (see ADR-0005).

## Motivation

Uploaded files need post-processing: images need thumbnails for preview, albums need ZIP packaging for bulk download, and the scanner contract establishes the interface for future ClamAV integration.

## Scope

### In Scope

- Module scaffolding via `internachi/modular`
- **Thumbnails**: `GenerateThumbnailListener` (listens to `FileUploaded`), `GenerateThumbnailJob` (queue: thumbnails), `ThumbnailGenerator` service (Intervention Image)
- **Zip**: `CreateZipArchiveJob` (queue: zip), `ZipArchiveService`
- **Antivirus contract**: `AntivirusScanner` interface, `NullScanner` driver (marks all files `clean`), `ScanFileListener` (listens to `FileUploaded`), `ScanFileJob` (queue: antivirus)
- Events: `FileScanCompleted`, `FileScanFailed`, `ThumbnailGenerated`
- `UpdateFileStatusListener`: listens to `FileScanCompleted`, updates `vault_files.status`
- `config/content-pipeline.php` with thumbnail sizes, antivirus driver, zip limits
- Composer: `intervention/image` with Imagick driver
- Horizon queue configuration for `thumbnails`, `antivirus`, `zip` queues
- Feature tests (with NullScanner and test images)

### Out of Scope

- ClamAV driver implementation (future PRD)
- VirusTotal integration (future PRD, security module)
- Thumbnail cleanup on file expiry (listener for `FileExpired` — can be added here or deferred)

## Listeners

| Listens to          | Listener                    | Action                                     |
| :------------------ | :-------------------------- | :----------------------------------------- |
| `FileUploaded`      | `GenerateThumbnailListener` | Dispatches `GenerateThumbnailJob` if image |
| `FileUploaded`      | `ScanFileListener`          | Dispatches `ScanFileJob`                   |
| `FileScanCompleted` | `UpdateFileStatusListener`  | Updates `vault_files.status` to `clean`    |
| `FileExpired`       | `CleanupThumbnailListener`  | Deletes thumbnail from S3                  |
| `FileDeleted`       | `CleanupThumbnailListener`  | Deletes thumbnail from S3                  |

## Events Emitted

| Event                | Payload                                    |
| :------------------- | :----------------------------------------- |
| `FileScanCompleted`  | `File $file, string $scanner, bool $clean` |
| `FileScanFailed`     | `File $file, string $reason`               |
| `ThumbnailGenerated` | `File $file, string $thumbnailPath`        |

## Thumbnail Flow

```
 QUEUE: thumbnails                 SYSTEM
  │                                   │
  │  GenerateThumbnailJob             │
  │ ──────────────────────────────►  │
  │                                   │  ThumbnailGenerator:
  │                                   │    ⚙️ check: is image mime? → skip if not
  │                                   │    Storage::disk('s3')->get(file.storage_path)
  │                                   │    Intervention: resize to config sizes
  │                                   │    Storage::disk('s3')->put(thumbnails/...)
  │                                   │    $file->update(['thumbnail_path' => ...])
  │                                   │    ThumbnailGenerated::dispatch($file, $path)
  │                                   │  ✓ complete
```

## Configuration

```php
// config/content-pipeline.php
return [
    'thumbnails' => [
        'width' => (int) env('THUMBNAIL_WIDTH', 300),
        'height' => (int) env('THUMBNAIL_HEIGHT', 300),
        'format' => 'webp',
        'quality' => 80,
    ],
    'antivirus' => [
        'driver' => env('ANTIVIRUS_DRIVER', 'null'),
        'clamav_binary' => env('CLAMAV_BINARY_PATH', '/usr/bin/clamscan'),
    ],
    'zip' => [
        'max_files' => (int) env('ZIP_MAX_FILES', 100),
        'max_size_mb' => (int) env('ZIP_MAX_SIZE_MB', 500),
    ],
];
```

## Acceptance Criteria

- [ ] `content-pipeline` module created via `internachi/modular`
- [ ] `GenerateThumbnailListener` auto-discovered for `FileUploaded`
- [ ] Thumbnails generated as WebP and stored in S3 under `thumbnails/`
- [ ] `File.thumbnail_path` updated after generation
- [ ] Non-image files skip thumbnail generation silently
- [ ] `ScanFileListener` auto-discovered for `FileUploaded`
- [ ] `NullScanner` marks all files as `clean` immediately
- [ ] `FileScanCompleted` event dispatched after scan
- [ ] `UpdateFileStatusListener` updates file status to `clean`
- [ ] `ZipArchiveService` creates ZIP from album files
- [ ] Thumbnail cleanup on `FileExpired` and `FileDeleted`
- [ ] Horizon queue config for `thumbnails`, `antivirus`, `zip`
- [ ] Feature tests with test images and NullScanner
