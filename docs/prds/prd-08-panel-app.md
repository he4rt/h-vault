# PRD-08: Panel App — User Filament Panel

**Status:** Draft
**Priority:** P2
**Module:** `panel-app`
**Prerequisites:** PRD-01, PRD-02, PRD-03
**Depends on:** PRD-01, PRD-02, PRD-03
**Blocked by:** PRD-03

## Summary

Create the `panel-app` module: a Filament v5 panel at `/app` where authenticated users manage their files, buckets, and albums.

## Motivation

Authenticated users need a dashboard to see their uploads, organize files into buckets and albums, manage sharing, and monitor storage usage.

## Scope

### In Scope

- Module scaffolding via `internachi/modular`
- Filament panel registration at `/app` (panel ID: `app`)
- `FileResource`: list, view, delete user's files
- `BucketResource`: CRUD for user's buckets
- `AlbumResource`: CRUD for albums within buckets
- `Dashboard` page with widgets
- `StorageUsageWidget`: total storage used by user
- `RecentUploadsWidget`: last N uploads with status
- Auth via Filament's built-in login (backed by identity module users)
- Scoped queries: users only see their own files/buckets/albums
- File actions: delete, extend TTL, toggle password, copy share link
- Bucket actions: create, rename, delete, generate share token
- Album actions: create, rename, delete, toggle sharing

### Out of Scope

- File upload via panel (users upload via API or landing page)
- Admin functionality (panel-admin)
- Real-time widgets (future analytics PRD)

## Panel Structure

```
/app
├── Login / Register
├── Dashboard
│   ├── StorageUsageWidget (total MB used / limit)
│   └── RecentUploadsWidget (last 10 uploads, status badges)
├── Files
│   ├── List (table: name, size, status, expires_at, actions)
│   └── View (detail page with preview, metadata, share link)
├── Buckets
│   ├── List (table: name, file count, created_at)
│   ├── Create
│   ├── View (files within bucket)
│   └── Albums (relation manager)
│       ├── List
│       ├── Create
│       └── View (files within album)
```

## Acceptance Criteria

- [ ] `panel-app` module created via `internachi/modular`
- [ ] Filament panel accessible at `/app`
- [ ] Users can login/register
- [ ] `FileResource` lists only the authenticated user's files
- [ ] Files table shows: name, size, status, expires_at, actions
- [ ] `BucketResource` with CRUD
- [ ] `AlbumResource` scoped to buckets via relation manager
- [ ] Dashboard shows storage usage and recent uploads widgets
- [ ] File actions: delete, copy link, extend TTL
- [ ] Bucket share token generation
- [ ] Dark mode compatible
- [ ] Tests: resource listing, scoping (user can't see other users' files), CRUD operations
