# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- **`CONTEXT-MAP.md`** at the repo root — it points at one `CONTEXT.md` per module/context. Read each one relevant to the topic.
- **`docs/adr/`** — read ADRs that touch the area you're about to work in. Also check `app-modules/<module>/docs/adr/` for module-scoped decisions.

If any of these files don't exist, **proceed silently**. Don't flag their absence; don't suggest creating them upfront. The producer skill (`/grill-with-docs`) creates them lazily when terms or decisions actually get resolved.

## File structure

This is a multi-context repo (modular monolith). Each module can have its own `CONTEXT.md` and ADRs:

```
/
├── CONTEXT-MAP.md                         <- maps modules to contexts
├── docs/adr/                              <- system-wide decisions
└── app-modules/
    ├── vault/
    │   ├── CONTEXT.md                     <- vault domain language
    │   └── docs/adr/                      <- vault-specific decisions
    ├── content-pipeline/
    │   ├── CONTEXT.md
    │   └── docs/adr/
    ├── security/
    │   ├── CONTEXT.md
    │   └── docs/adr/
    ├── identity/
    │   ├── CONTEXT.md
    │   └── docs/adr/
    ├── analytics/
    │   ├── CONTEXT.md
    │   └── docs/adr/
    ├── panel-app/
    │   └── CONTEXT.md
    └── panel-admin/
        └── CONTEXT.md
```

## Use the glossary's vocabulary

When your output names a domain concept (in an issue title, a refactor proposal, a hypothesis, a test name), use the term as defined in the relevant `CONTEXT.md`. Don't drift to synonyms the glossary explicitly avoids.

If the concept you need isn't in the glossary yet, that's a signal — either you're inventing language the project doesn't use (reconsider) or there's a real gap (note it for `/grill-with-docs`).

## Flag ADR conflicts

If your output contradicts an existing ADR, surface it explicitly rather than silently overriding:

> _Contradicts ADR-0007 (event-driven module communication) — but worth reopening because..._
