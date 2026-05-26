# ADR-0006: Static counter in MVP, real-time analytics deferred

**Status:** Accepted
**Date:** 2026-05-26

## Context

The analytics module includes Redis counters, bandwidth tracking, and real-time broadcasting via Laravel Reverb (WebSocket server). This requires Reverb installation, a separate process, and Redis counter management across multiple listeners.

## Decision

The analytics module is deferred entirely. The landing page displays a cached file count using `Cache::remember('files:total', 300, fn() => File::count())`. Real-time counters, Reverb, and the full analytics module will be added in a future PRD.

## Consequences

- No Laravel Reverb in MVP — one less service to run.
- No analytics listeners consuming vault events yet.
- File count on landing page refreshes every 5 minutes (good enough for MVP).
- The vault events (`FileUploaded`, `FileDeleted`, etc.) are already dispatched, so analytics listeners can be added later without vault changes.
