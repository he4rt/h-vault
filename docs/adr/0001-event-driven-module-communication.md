# ADR-0001: Event-driven module communication

**Status:** Accepted
**Date:** 2026-05-26

## Context

H-Vault is a modular monolith with 7+ modules. Modules need to communicate (e.g., vault notifies content-pipeline about new uploads) without creating import dependencies that break bounded context isolation.

## Decision

Events live in the emitting module. Listeners live in the receiving module. Laravel auto-discovers listeners via the `handle()` type-hint. No module imports classes from another module except `identity` (shared core) and `vault` models (for panels).

Events are created in the emitting module's PRD and dispatched immediately, even if no listeners exist yet. Listeners are added by downstream module PRDs — purely additive, never modifying the emitting module.

## Consequences

- Events fire into the void initially (zero listeners) — this is a no-op in Laravel.
- Each downstream PRD is self-contained: it only creates listeners, never touches vault code.
- Agents implementing downstream modules need only the event class signature as their contract.
