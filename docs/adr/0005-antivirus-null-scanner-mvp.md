# ADR-0005: NullScanner as default antivirus in MVP

**Status:** Accepted
**Date:** 2026-05-26

## Context

The content-pipeline module includes an antivirus scanning step (ClamAV) and file reputation check (VirusTotal). Both require external dependencies (ClamAV binary, VirusTotal API key) that add complexity to local development and deployment.

## Decision

The MVP ships with the `AntivirusScanner` contract and `NullScanner` driver. All files are marked `clean` immediately. The `FileStatus` enum (`pending`, `clean`, `infected`, `quarantined`) and the scanning events (`FileScanCompleted`, `FileScanFailed`) are created in the MVP so the schema and event contracts are correct from day one. ClamAV and VirusTotal integration will be added in a future PRD by implementing `ClamAvScanner` and swapping the driver in config.

## Consequences

- Zero external dependencies for antivirus in MVP.
- The `vault_files.status` column exists from PRD 1 with the full enum.
- Swapping to real scanning is a config change + one new driver class.
- No schema migration or event contract changes needed when ClamAV is added.
