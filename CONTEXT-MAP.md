# Context Map

Multi-context modular monolith. Each module is a bounded context with its own glossary and ADRs.

## Contexts

| Module           | CONTEXT.md                                | ADRs                                     |
| :--------------- | :---------------------------------------- | :--------------------------------------- |
| vault            | `app-modules/vault/CONTEXT.md`            | `app-modules/vault/docs/adr/`            |
| content-pipeline | `app-modules/content-pipeline/CONTEXT.md` | `app-modules/content-pipeline/docs/adr/` |
| security         | `app-modules/security/CONTEXT.md`         | `app-modules/security/docs/adr/`         |
| identity         | `app-modules/identity/CONTEXT.md`         | `app-modules/identity/docs/adr/`         |
| panel-guest      | `app-modules/panel-guest/CONTEXT.md`      | —                                        |
| panel-app        | `app-modules/panel-app/CONTEXT.md`        | —                                        |
| panel-admin      | `app-modules/panel-admin/CONTEXT.md`      | —                                        |

## System-wide ADRs

`docs/adr/` — decisions that cross module boundaries.

## Dependency direction

```
identity (core) ──► vault ──► content-pipeline ──► security
                      │
                      ├──► panel-guest (public UI)
                      ├──► panel-app (user Filament panel)
                      └──► panel-admin (admin Filament panel)
```

Imports flow **up** (toward identity/vault). Events flow **down** (toward pipeline/security). Panels import vault models for display.
