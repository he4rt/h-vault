# H-Vault — Project Overview

Servico de hospedagem de arquivos temporarios inspirado no WaifuVault, construido em Laravel.

## Core Concept

Usuarios fazem upload de arquivos via API REST ou landing page. Cada arquivo recebe um token unico e expira automaticamente com base em uma curva de TTL proporcional ao tamanho. Uploads podem ser anonimos (com limites menores) ou autenticados via Sanctum (limites maiores, acesso a buckets e albuns).

## Modular Architecture

Monolito modular com `internachi/modular`. Namespace: `He4rt\`. Modulos vivem em `app-modules/`.

| Modulo             | Responsabilidade                                                  |
| :----------------- | :---------------------------------------------------------------- |
| `identity`         | Users, Teams, Roles, Auth (shared core)                           |
| `vault`            | Files, Buckets, Albums, API REST, TTL, Events                     |
| `content-pipeline` | Processamento assincrono: thumbnails, zip, antivirus              |
| `security`         | Rate limiting, IP blocking, CAPTCHA, file reputation (VirusTotal) |
| `panel-admin`      | Filament admin panel                                              |
| `panel-app`        | Filament user panel + landing page Livewire/Flux                  |
| `analytics`        | Contadores Redis, bandwidth tracking, broadcasting real-time      |

## Event-Driven Communication

Modulos **nao importam classes uns dos outros** (exceto `identity` como core e models do `vault` para os paineis). Comunicacao cross-module acontece via Events/Listeners com auto-discovery do Laravel.

- Events vivem no modulo emissor
- Listeners vivem no modulo receptor
- Dependencias fluem "para cima" (em direcao a `identity` e `vault`), events fluem "para baixo"

## Table Naming Convention

Todas as tabelas usam o prefixo do modulo: `vault_files`, `vault_buckets`, `vault_albums`, `security_blocked_ips`, `identity_users`, etc. Models definem `$table` explicitamente.

## Key Flows

1. **Upload**: API REST (`PUT /api/v1/files`) -> `StoreFileAction` -> S3 storage -> `FileUploaded` event -> listeners assincrono (thumbnail, scan, counter)
2. **Download**: `GET /f/{token}/{filename}` -> cache check -> password/expiry validation -> S3 stream -> `FileDownloaded` event
3. **Expiracao**: Scheduled command (hourly) -> cleanup files expirados -> `FileExpired` event -> thumbnail cleanup + counter decrement
4. **Antivirus**: `FileUploaded` -> `ScanFileJob` (ClamAV) -> `FileScanCompleted` -> `CheckFileReputationListener` (VirusTotal) -> quarantine se malicioso

## Tech Decisions

- **Storage**: S3-compatible (Cloudflare R2 / MinIO) via Flysystem
- **Queues**: Redis + Horizon com filas dedicadas (thumbnails, antivirus, zip, reputation)
- **Auth API**: Hibrido — anonimo com limites + Sanctum tokens para autenticados
- **Frontend**: Livewire + Flux UI (landing page simples), sem SPA
- **Real-time**: Laravel Reverb para contadores ao vivo
