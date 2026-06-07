# H-Vault — Project Overview

Servico de hospedagem de arquivos temporarios inspirado no WaifuVault, construido em Laravel.

## Core Concept

Usuarios fazem upload de arquivos via API REST ou landing page. Cada arquivo recebe um token unico e expira automaticamente com base em uma curva de TTL proporcional ao tamanho. Uploads podem ser anonimos (com limites menores) ou autenticados via Sanctum (limites maiores, acesso a buckets e albuns).

## Modular Architecture

Monolito modular com `internachi/modular`. Namespace: `He4rt\`. Modulos vivem em `app-modules/`.

| Modulo             | Responsabilidade                                          |
| :----------------- | :-------------------------------------------------------- |
| `identity`         | Users, Teams, Roles, Auth (shared core)                   |
| `vault`            | Files, Buckets, Albums, API REST, TTL, Events             |
| `content-pipeline` | Thumbnails, zip, antivirus contract (NullScanner no MVP)  |
| `security`         | Rate limiting, IP blocking                                |
| `panel-guest`      | Landing page (upload) + file preview (Livewire/Tailwind)  |
| `panel-app`        | Filament user panel (Files, Buckets, Albums)               |
| `panel-admin`      | Filament admin panel (moderation, blocked IPs, stats)     |

Modulos planejados mas nao implementados: `analytics` (contadores Redis, broadcasting Reverb — deferido por ADR-0006).

## Event-Driven Communication

Modulos **nao importam classes uns dos outros** (exceto `identity` como core e models do `vault` para os paineis). Comunicacao cross-module acontece via Events/Listeners com auto-discovery do Laravel.

- Events vivem no modulo emissor
- Listeners vivem no modulo receptor
- Dependencias fluem "para cima" (em direcao a `identity` e `vault`), events fluem "para baixo"

## Table Naming Convention

Todas as tabelas usam o prefixo do modulo: `vault_files`, `vault_buckets`, `vault_albums`, `security_blocked_ips`, `identity_users`, etc. Models definem `$table` explicitamente.

## Key Flows

1. **Upload**: API REST (`PUT /api/v1/files`) ou landing page (`/`) -> `StoreFileAction` -> storage (S3 ou local) -> `FileUploaded` event -> listeners async (thumbnail, scan)
2. **Download**: `GET /f/{token}/{filename}` -> cache check -> password/expiry validation -> proxy stream -> `FileDownloaded` event
3. **Preview**: `GET /preview/{token}/{filename}` -> Livewire FilePreview component -> mime-based rendering (image/video/audio/pdf/generic)
4. **Expiracao**: Scheduled command (hourly) -> cleanup files expirados -> `FileExpired` event -> thumbnail cleanup
5. **Antivirus**: `FileUploaded` -> `ScanFileJob` (NullScanner no MVP, contrato para ClamAV futuro) -> `FileScanCompleted` -> status update

## Tech Decisions

Decisoes documentadas em `docs/adr/`:

- **Storage**: S3-compatible (Cloudflare R2 / MinIO) via Flysystem; `VAULT_STORAGE_DISK=local` para dev
- **Queues**: Database queue no MVP; filas nomeadas (thumbnails, antivirus, zip) prontas para Redis + Horizon
- **Auth API**: Hibrido — anonimo com limites + Sanctum tokens para autenticados
- **Frontend**: Livewire + Tailwind CSS (landing page), Filament (paineis). Sem Flux UI, sem SPA
- **CAPTCHA**: Nenhum (ADR-0003) — rate limiting por IP eh suficiente
- **Antivirus**: NullScanner contract no MVP (ADR-0005) — interface pronta para ClamAV
- **Downloads**: Proxy streaming (ADR-0004) — servidor faz proxy do S3, sem URLs pre-signed
- **Analytics**: Deferido (ADR-0006) — counter estático via `Cache::remember` no MVP

## Livewire Reserved Names

Nao usar nomes de metodos que colidam com funcoes internas do Livewire (ex: `upload`, `download`). Usar nomes como `store`, `submit` em vez disso.
