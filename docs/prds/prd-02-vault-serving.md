# PRD-02: Vault Serving — Download, Streaming, Password, One-Time

**Status:** Draft
**Priority:** P0
**Module:** `vault`
**Prerequisites:** PRD-01 (vault-core)
**Depends on:** PRD-01
**Blocked by:** None after PRD-01

## Summary

Add file serving capabilities: proxy-streamed downloads through Laravel, password verification, one-time file deletion after first download, and the `FileDownloaded` event.

## Motivation

Files need to be downloadable. The serving layer handles access control (password, expiry, one-time) and streams files from S3 through Laravel for full control (see ADR-0004).

## Scope

### In Scope

- `ServeFileAction`: retrieves file from S3, validates password/expiry, streams response
- `FileServeController` with `GET /f/{token}/{filename}` route
- Proxy streaming via `StreamedResponse` (chunked, constant memory — see ADR-0004)
- Password verification: checks `X-File-Password` header against `password_hash` (Argon2)
- Expiry verification: returns 404 if `expires_at` is past
- One-time files: soft-deletes after first successful download
- `FileDownloaded` event dispatched after successful stream
- `FileExpired` event class created (dispatched by PRD-04, but class defined here for contract completeness)
- Cache layer: `Cache::remember("file:{token}", ...)` for file lookup
- Feature tests for all download scenarios

### Out of Scope

- UI for password input (PRD-07 — panel-guest)
- File preview/rendering (PRD-07)
- TTL cleanup command (PRD-04)
- Thumbnails (PRD-05)

## API Endpoints

### `GET /f/{token}/{filename}` — Download/Stream

```
Headers:
  X-File-Password: (string)  optional, required if file is protected

Response 200:
  Content-Type: {mime_type}
  Content-Disposition: inline; filename="{original_name}"
  (streamed file body)

Response 403:
  {"error": "Password required"}  (when file is protected, no/wrong password)

Response 404:
  {"error": "File not found"}  (when token invalid or file expired)

Response 410:
  {"error": "File no longer available"}  (when one-time file already downloaded)
```

## Events

| Event            | Dispatched by                        | Payload      |
| :--------------- | :----------------------------------- | :----------- |
| `FileDownloaded` | `ServeFileAction`                    | `File $file` |
| `FileExpired`    | (created here, dispatched by PRD-04) | `File $file` |

## Download Flow

```
 USER                              SYSTEM
  │                                   │
  │  📥 GET /f/{token}/photo.png      │
  │ ──────────────────────────────►  │
  │                                   │  FileServeController:
  │                                   │    Cache::remember("file:{token}")
  │                                   │    ⚙️ check: exists? → 404
  │                                   │    ⚙️ check: expired? → 404 + cleanup
  │                                   │    ⚙️ check: password? → 403
  │                                   │    ⚙️ check: soft-deleted? → 410
  │                                   │
  │                                   │  ServeFileAction:
  │                                   │    Storage::disk('s3')->readStream(...)
  │                                   │    StreamedResponse (chunked)
  │                                   │
  │    200 OK                         │
  │    Content-Type: image/png        │
  │    (streamed file bytes)          │
  │ ◄────────────────────────────────│
  │                                   │
  │                                   │  Post-stream:
  │                                   │    FileDownloaded::dispatch($file)
  │                                   │    if is_one_time: $file->delete()
```

## Password-Protected Download Flow

```
 USER                              SYSTEM
  │                                   │
  │  📥 GET /f/{token}/secret.pdf     │
  │  (no X-File-Password header)      │
  │ ──────────────────────────────►  │
  │                                   │  ⚙️ file.password_hash is not null
  │                                   │  ⚙️ no password provided
  │                                   │
  │    403 Forbidden                  │
  │    {"error": "Password required"} │
  │ ◄────────────────────────────────│
  │                                   │
  │  📥 GET /f/{token}/secret.pdf     │
  │  X-File-Password: my-secret      │
  │ ──────────────────────────────►  │
  │                                   │  ⚙️ Hash::check("my-secret", hash) ✓
  │                                   │  ⚙️ stream file
  │                                   │
  │    200 OK (streamed)              │
  │ ◄────────────────────────────────│
```

## Acceptance Criteria

- [ ] `GET /f/{token}/{filename}` streams file from S3 via proxy
- [ ] Memory usage is constant regardless of file size (chunked streaming)
- [ ] Expired files return 404
- [ ] Password-protected files return 403 without correct `X-File-Password`
- [ ] Password-protected files stream with correct `X-File-Password`
- [ ] One-time files are soft-deleted after first successful download
- [ ] One-time files return 410 on subsequent download attempts
- [ ] `FileDownloaded` event dispatched after successful download
- [ ] File lookup uses cache layer
- [ ] Content-Type and Content-Disposition headers are set correctly
- [ ] Feature tests cover: normal download, expired file, password required, wrong password, correct password, one-time download + second attempt, non-existent token
