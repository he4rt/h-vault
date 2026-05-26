# ADR-0004: Proxy streaming for downloads

**Status:** Accepted
**Date:** 2026-05-26

## Context

File downloads can be served via proxy streaming (Laravel reads from S3 and streams to client) or via redirect to a signed S3 temporary URL. Signed URLs bypass application-level controls (password, one-time delete, logging).

## Decision

All downloads are proxy-streamed through Laravel using chunked `StreamedResponse`. The application maintains full control over password verification, one-time file deletion, download counting, and response headers.

## Consequences

- All download bandwidth passes through the Laravel server (doubled: S3 → Laravel → client).
- Memory footprint is constant regardless of file size (chunked streaming).
- Cloudflare R2 has zero egress fees, so the bandwidth cost is only server I/O.
- If performance becomes a bottleneck with very large files, this can be revisited with a hybrid approach (signed URLs for large files).
