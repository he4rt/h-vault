# PRD-06: Security — Rate Limiting & IP Blocking

**Status:** Draft
**Priority:** P1
**Module:** `security`
**Prerequisites:** PRD-01 (vault-core)
**Depends on:** PRD-01 (events, routes)
**Blocked by:** None after PRD-01

## Summary

Create the `security` module with rate limiting per IP/user, IP blocking middleware, and the blocked IP management system. No CAPTCHA (see ADR-0003).

## Motivation

Without CAPTCHA, rate limiting is the primary protection against API abuse. IP blocking provides a reactive defense for repeat offenders.

## Scope

### In Scope

- Module scaffolding via `internachi/modular`
- `VaultRateLimiter`: configures `RateLimiter::for()` for vault routes
- `BlockedIp` model with `security_blocked_ips` migration
- `BlockIpAction`: creates blocked IP record
- `IpBlockingMiddleware`: checks incoming IP against blocked list, returns 403
- `BlockSuspiciousIpListener`: listens to repeated abuse patterns (future, can be stub)
- `config/security.php` with rate limits and blocking config
- Middleware applied to vault API routes
- Database factories and seeders
- Feature tests

### Out of Scope

- CAPTCHA (removed — ADR-0003)
- VirusTotal file reputation (future PRD with ClamAV)
- `FileQuarantined` event and quarantine flow (future, tied to antivirus)

## Data Model

### `security_blocked_ips` migration

```php
Schema::create('security_blocked_ips', function (Blueprint $table) {
    $table->id();
    $table->ipAddress('ip')->unique();
    $table->text('reason');
    $table->timestamp('blocked_until')->nullable();
    $table->timestamps();
});
```

## Rate Limits

Configured via `config/security.php`, applied via `VaultRateLimiter`:

| Route               | Anonymous      | Authenticated    |
| :------------------ | :------------- | :--------------- |
| `PUT /api/v1/files` | 10/min per IP  | 60/min per user  |
| `GET /f/{token}/*`  | 100/min per IP | 300/min per user |
| `POST /api/v1/auth` | 5/min per IP   | —                |

## Configuration

```php
// config/security.php
return [
    'rate_limits' => [
        'upload_anonymous' => (int) env('VAULT_RATE_LIMIT_UPLOAD_ANON', 10),
        'upload_authenticated' => (int) env('VAULT_RATE_LIMIT_UPLOAD_AUTH', 60),
        'download_anonymous' => (int) env('VAULT_RATE_LIMIT_DOWNLOAD_ANON', 100),
        'download_authenticated' => (int) env('VAULT_RATE_LIMIT_DOWNLOAD_AUTH', 300),
        'auth' => (int) env('VAULT_RATE_LIMIT_AUTH', 5),
    ],
    'ip_blocking' => [
        'enabled' => (bool) env('VAULT_IP_BLOCKING_ENABLED', true),
        'cache_ttl' => (int) env('VAULT_IP_BLOCK_CACHE_TTL', 3600),
    ],
];
```

## Acceptance Criteria

- [ ] `security` module created via `internachi/modular`
- [ ] Rate limiting applied to upload, download, and auth routes
- [ ] Anonymous requests limited by IP
- [ ] Authenticated requests limited by user ID
- [ ] Rate limit exceeded returns 429 Too Many Requests
- [ ] `BlockedIp` model with CRUD
- [ ] `IpBlockingMiddleware` returns 403 for blocked IPs
- [ ] Blocked IPs with `blocked_until` auto-expire
- [ ] Permanent blocks (null `blocked_until`) persist
- [ ] Feature tests: rate limit hit, IP blocked, IP block expired
