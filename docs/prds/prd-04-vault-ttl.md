# PRD-04: Vault TTL — Expiry Calculation & Cleanup

**Status:** Draft
**Priority:** P1
**Module:** `vault`
**Prerequisites:** PRD-01 (vault-core)
**Depends on:** PRD-01
**Blocked by:** None after PRD-01

## Summary

Implement the TTL calculation engine (cubic curve based on file size ratio) and the scheduled command that cleans up expired files.

## Motivation

Files must expire automatically to prevent unbounded storage growth. The TTL curve gives small files longer lifetimes and large files shorter ones, incentivizing efficient use.

## Scope

### In Scope

- `TtlCalculator` service: cubic curve calculation
- `CleanExpiredFilesCommand` (Artisan scheduled command, runs hourly)
- `FileExpired` event dispatched for each expired file (event class from PRD-02)
- Integration with `StoreFileAction`: auto-calculate `expires_at` on upload when no custom TTL provided
- Custom TTL override for authenticated users (within configured limits)
- TTL config values in `config/vault.php` (already defined in PRD-01)
- Unit tests for `TtlCalculator` with edge cases
- Feature tests for the cleanup command

### Out of Scope

- Thumbnail cleanup on expiry (PRD-05 listener)
- Counter decrement on expiry (future analytics PRD)

## TTL Calculation

### Cubic Curve Formula

```
TTL = min_days + (max_days - min_days) × (1 - file_size / max_size)³
```

Uses the config values from `config/vault.php`:

```php
// Anonymous (default values)
min_days: 1, max_days: 7, max_size: 100MB

// Authenticated (default values)
min_days: 1, max_days: 30, max_size: 500MB
```

### Examples with default anonymous config

| File Size | % of Limit | TTL     |
| :-------- | :--------- | :------ |
| 1 MB      | 1%         | ~7 days |
| 50 MB     | 50%        | ~5 days |
| 75 MB     | 75%        | ~2 days |
| 100 MB    | 100%       | 1 day   |

### Custom TTL Override

Authenticated users can pass `expires_in` in the upload request. The value is clamped to `[min_days, max_days]` from the authenticated config. If `custom_override` is disabled in config, the curve is always used.

## Expiry Cleanup Flow

```
 SCHEDULER                            SYSTEM
  │                                      │
  │  ⏰ hourly: CleanExpiredFilesCommand │
  │ ──────────────────────────────────► │
  │                                      │  File::where('expires_at', '<', now())
  │                                      │    ->chunkById(100, function ($files) {
  │                                      │      foreach:
  │                                      │        Storage::disk('s3')->delete(path)
  │                                      │        FileExpired::dispatch($file)
  │                                      │        $file->forceDelete()
  │                                      │    })
  │                                      │
  │    ✓ "Cleaned 42 expired files"     │
  │ ◄──────────────────────────────────│
```

## Acceptance Criteria

- [ ] `TtlCalculator` correctly computes TTL using cubic curve
- [ ] `StoreFileAction` auto-sets `expires_at` when no custom TTL
- [ ] Authenticated users can override TTL within configured limits
- [ ] Anonymous uploads always use the curve (no custom override)
- [ ] `CleanExpiredFilesCommand` deletes expired files from S3 and database
- [ ] `FileExpired` event dispatched for each cleaned file
- [ ] Command runs in chunks (100 per batch) to avoid memory issues
- [ ] Command is registered in scheduler (hourly)
- [ ] Unit tests: TTL at 0%, 50%, 75%, 100% of limit, custom override clamping
- [ ] Feature tests: command cleans expired files, skips non-expired
