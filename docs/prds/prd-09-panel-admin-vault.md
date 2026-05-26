# PRD-09: Panel Admin Vault — Admin Moderation Resources

**Status:** Draft
**Priority:** P2
**Module:** `panel-admin`
**Prerequisites:** PRD-01, PRD-06
**Depends on:** PRD-01 (File model), PRD-06 (BlockedIp model)
**Blocked by:** PRD-06

## Summary

Add vault-related resources to the existing `panel-admin` Filament panel: file moderation, blocked IP management, and system stats widgets.

## Motivation

Admins need to moderate uploaded content (find and remove problematic files), manage IP blocks, and monitor system health. The admin panel already exists with User/Team/Role resources — this PRD adds the vault layer.

## Scope

### In Scope

- `FileResource` in panel-admin: list all files (not scoped to user), view, delete, change status, quarantine
- `BlockedIpResource` in panel-admin: list, create, edit, remove IP blocks
- Dashboard widgets: total files count, total storage used, files by status, recent uploads
- File table: searchable by name/token, filterable by status/mime_type/date
- File actions: delete (removes from S3), change status, extend TTL
- Bulk actions: bulk delete, bulk quarantine

### Out of Scope

- Bucket/Album management (admin doesn't manage user organization — see grilling decision)
- Upload functionality (admin doesn't upload)
- ClamAV/VirusTotal integration (future PRD)

## Panel Structure

```
/admin (existing panel)
├── ... (existing: Users, Teams, Roles, ExternalIdentities)
│
├── Files (NEW)
│   ├── List (all files, searchable, filterable by status/mime)
│   ├── View (file detail + metadata + preview)
│   └── Actions: delete, quarantine, change status, extend TTL
│
├── Blocked IPs (NEW)
│   ├── List (all blocked IPs with reason and expiry)
│   ├── Create (manual IP block)
│   └── Edit (update reason/expiry)
│
└── Dashboard (UPDATED)
    ├── ... (existing widgets)
    ├── TotalFilesWidget (NEW)
    ├── StorageUsedWidget (NEW)
    └── FilesByStatusWidget (NEW)
```

## Acceptance Criteria

- [ ] `FileResource` added to panel-admin with all files visible
- [ ] File table: columns for name, token, size, status, mime, uploaded_by, expires_at
- [ ] File table: search by name and token
- [ ] File table: filter by status, mime_type
- [ ] File actions: delete (S3 + DB), quarantine (soft delete + status change), extend TTL
- [ ] Bulk actions: bulk delete, bulk quarantine
- [ ] `BlockedIpResource` with CRUD
- [ ] Dashboard widgets: total files, storage used, files by status
- [ ] Admin sees all files regardless of ownership
- [ ] Action notifications follow Filament convention (see `.ai/filament` — `successNotification()` on built-in actions)
- [ ] Tests: file listing, file deletion, IP blocking CRUD, widget rendering
