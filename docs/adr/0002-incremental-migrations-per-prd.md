# ADR-0002: Incremental migrations per PRD

**Status:** Accepted
**Date:** 2026-05-26

## Context

The `vault_files` table has nullable FKs to `vault_buckets` and `vault_albums`. These tables are created in a different PRD (vault-buckets-albums) than the one that creates `vault_files` (vault-core). Creating FKs to non-existent tables fails.

## Decision

Each PRD delivers a self-contained, valid migration state. vault-core creates `vault_files` without `bucket_id`/`album_id`. vault-buckets-albums creates `vault_buckets`, `vault_albums`, and adds the FK columns to `vault_files` via a separate migration.

## Consequences

- Any PRD can be migrated independently in sequence.
- Agents don't need knowledge of future schema — they work with the current state.
- The migration chain is: vault-core → vault-buckets-albums adds FKs.
