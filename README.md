# H-Vault

Servico de hospedagem de arquivos temporarios inspirado no [WaifuVault](https://waifuvault.moe), reconstruido do zero em Laravel.

Upload anonimo com limites ou autenticado via Sanctum. Arquivos expiram automaticamente com base em uma curva de TTL proporcional ao tamanho. Painel de administracao via Filament, painel do usuario para gerenciar buckets e albuns, e uma landing page publica com Livewire.

## Stack

| Componente       | Tecnologia                                         |
| :--------------- | :------------------------------------------------- |
| Framework        | Laravel 13                                         |
| Admin Panel      | Filament v5 (`panel-admin`)                        |
| User Panel       | Filament v5 (`panel-app`)                          |
| Landing Page     | Livewire v4 + Tailwind CSS (`panel-guest`)         |
| API Auth         | Laravel Sanctum (Bearer tokens)                    |
| Storage          | Flysystem S3 (Cloudflare R2 / MinIO / local)       |
| Database         | SQLite (dev) / PostgreSQL (prod)                   |
| Cache / Queues   | Database (dev) / Redis (prod)                      |
| Image Processing | Imagick (thumbnails via content-pipeline)          |
| Antivirus        | NullScanner (MVP) — contrato para ClamAV           |
| Containerizacao  | Docker Compose (PostgreSQL, Redis, MinIO, Mailpit) |

### Nao implementado (planejado)

| Componente      | Tecnologia      | Status              |
| :-------------- | :-------------- | :------------------ |
| Queue Monitor   | Laravel Horizon | Nao configurado     |
| Real-time       | Laravel Reverb  | Deferido (ADR-0006) |
| File Reputation | VirusTotal API  | Nao implementado    |
| ClamAV Driver   | ClamAV (CLI)    | Contrato pronto     |

## Arquitetura

Monolito modular com `internachi/modular`. Cada modulo eh um bounded context isolado. Comunicacao entre modulos acontece exclusivamente via **Events/Listeners** — nenhum modulo importa classes de outro (exceto `identity` como core compartilhado e models do `vault` para os paineis).

```
                     ┌──────────────────┐
                     │    identity      │
                     │  Users, Teams    │
                     │  Roles, Auth     │
                     └────────┬─────────┘
                              │ importa
           ┌──────────────────┼──────────────────────┐
           │                  │                       │
    ┌──────▼──────┐    ┌──────▼──────┐    ┌──────────▼──────────┐
    │    vault    │    │  panel-app  │    │    panel-admin      │
    │  Core API   │    │  User UI   │    │    Admin UI         │
    └──────┬──────┘    └─────────────┘    └─────────────────────┘
           │
           │ dispatch Events
           │
     ┌─────┴────────────────────────────┐
     │                                  │
     ▼                                  ▼
┌────────────────────┐    ┌────────────────────┐
│ content-pipeline   │    │    panel-guest     │
│  Thumbnails        │    │  Landing page      │
│  Zip               │    │  File preview      │
│  NullScanner       │    │  (Livewire)        │
└────────────────────┘    └────────────────────┘

┌────────────────────┐
│    security        │
│  Rate Limiting     │
│  IP Blocking       │
└────────────────────┘
```

**Regra de dependencia**: imports vao sempre "para cima" (em direcao ao `vault` e `identity`). Events fluem "para baixo". Nunca o contrario.

## Modulos

### `identity` (existente)

Core compartilhado. Users, Teams, Roles (Spatie Permission), ExternalIdentity. Todos os outros modulos importam models daqui.

### `vault`

Coracao do negocio. Models de File, Bucket e Album. API REST publica. Actions para upload, download e delecao. Calculo de TTL com curva cubica. Scheduled command para limpeza de arquivos expirados.

### `content-pipeline`

Processamento assincrono de conteudo. Escuta events do `vault` e despacha jobs em filas dedicadas. Thumbnails via Imagick, zip via ZipArchive. Scanner de antivirus via contrato (`AntivirusScanner` interface) com `NullScanner` como driver default no MVP (ver ADR-0005).

### `security`

Rate limiting por IP (anonimo) e por usuario (autenticado). Bloqueio de IP com middleware aplicado nas rotas do vault. Sem CAPTCHA (ver ADR-0003).

### `panel-guest`

Paginas publicas construidas com Livewire + Tailwind CSS. Landing page com upload form drag-and-drop em `/`. File preview em `/preview/{token}/{filename}` com renderizacao por mime type (imagem, video, audio, PDF, generico). Verificacao de senha para arquivos protegidos.

### `panel-app`

Painel Filament do usuario em `/app`. Resources para Files, Buckets e Albums com scoping por usuario autenticado. Widgets de uso de storage e uploads recentes.

### `panel-admin` (existente)

Painel Filament administrativo em `/admin`. Resources para gerenciamento de todos os arquivos (moderacao, quarentena), IPs bloqueados e widgets de estatisticas do sistema.

## Estrutura de Modulos

```
app-modules/
├── identity/                          # (EXISTE) Users, Teams, Roles, Auth
│   ├── src/
│   │   ├── Users/User.php
│   │   ├── Teams/Team.php
│   │   ├── Permissions/Role.php
│   │   └── ExternalIdentity/
│   ├── database/migrations/
│   └── config/
│
├── vault/                             # Core: Files, Buckets, Albums, API
│   ├── config/
│   │   └── vault.php                  #   upload limits, TTL curve, storage disk
│   ├── src/
│   │   ├── VaultServiceProvider.php
│   │   ├── Enums/FileStatus.php       #   Enum: pending, clean, infected, quarantined
│   │   ├── Files/
│   │   │   ├── File.php               #   Model
│   │   │   └── Actions/
│   │   │       ├── StoreFileAction.php
│   │   │       ├── ServeFileAction.php
│   │   │       └── DeleteFileAction.php
│   │   ├── Buckets/
│   │   │   ├── Bucket.php
│   │   │   └── Actions/
│   │   │       └── CreateBucketAction.php
│   │   ├── Albums/
│   │   │   └── Album.php
│   │   ├── TTL/
│   │   │   ├── TtlCalculator.php
│   │   │   └── CleanExpiredFilesCommand.php
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   │   ├── FileController.php
│   │   │   │   ├── BucketController.php
│   │   │   │   ├── AlbumController.php
│   │   │   │   ├── AuthTokenController.php
│   │   │   │   └── FileServeController.php
│   │   │   ├── Requests/
│   │   │   └── Resources/
│   │   └── Events/
│   │       ├── FileUploaded.php
│   │       ├── FileDownloaded.php
│   │       ├── FileExpired.php
│   │       ├── FileDeleted.php
│   │       ├── FileStatusChanged.php
│   │       └── BucketCreated.php
│   ├── resources/routes/api.php
│   ├── database/
│   │   ├── migrations/
│   │   └── factories/
│   └── tests/
│
├── content-pipeline/                  # Processamento assincrono
│   ├── config/
│   │   └── content-pipeline.php       #   thumbnail sizes, antivirus driver
│   ├── src/
│   │   ├── ContentPipelineServiceProvider.php
│   │   ├── Thumbnails/
│   │   │   ├── GenerateThumbnailJob.php
│   │   │   ├── GenerateThumbnailListener.php
│   │   │   └── ThumbnailGenerator.php
│   │   ├── Zip/
│   │   │   └── ZipArchiveService.php
│   │   ├── Antivirus/
│   │   │   ├── ScanFileJob.php
│   │   │   ├── ScanFileListener.php
│   │   │   ├── Contracts/
│   │   │   │   └── AntivirusScanner.php
│   │   │   └── Drivers/
│   │   │       └── NullScanner.php
│   │   ├── Events/
│   │   │   ├── FileScanCompleted.php
│   │   │   ├── FileScanFailed.php
│   │   │   └── ThumbnailGenerated.php
│   │   └── Listeners/
│   │       ├── UpdateFileStatusListener.php
│   │       └── CleanupThumbnailListener.php
│   └── tests/
│
├── security/                          # Rate limiting, IP blocking
│   ├── src/
│   │   ├── SecurityServiceProvider.php
│   │   ├── RateLimiting/
│   │   │   └── VaultRateLimiter.php
│   │   ├── IpBlocking/
│   │   │   ├── BlockedIp.php
│   │   │   ├── BlockIpAction.php
│   │   │   └── IpBlockingMiddleware.php
│   │   └── Listeners/
│   │       └── BlockSuspiciousIpListener.php
│   └── tests/
│
├── panel-guest/                       # Landing page + File preview
│   ├── src/
│   │   ├── PanelGuestServiceProvider.php
│   │   └── Livewire/
│   │       ├── Landing.php
│   │       └── FilePreview.php
│   ├── resources/
│   │   ├── views/livewire/
│   │   │   ├── landing.blade.php
│   │   │   └── file-preview.blade.php
│   │   └── views/layouts/
│   │       └── guest.blade.php
│   ├── resources/routes/web.php
│   └── tests/
│
├── panel-app/                         # Filament user panel
│   ├── src/
│   │   └── Filament/
│   │       ├── Resources/
│   │       │   ├── Files/FileResource.php
│   │       │   └── Buckets/BucketResource.php
│   │       └── Widgets/
│   │           ├── StorageUsageWidget.php
│   │           └── RecentUploadsWidget.php
│   └── tests/
│
└── panel-admin/                       # (EXISTE) Admin Panel
    └── src/
        └── Filament/
            ├── Resources/
            │   ├── Files/FileResource.php
            │   └── BlockedIps/BlockedIpResource.php
            └── Widgets/
                ├── TotalFilesWidget.php
                └── FilesByStatusWidget.php
```

## Event-Driven Communication

Modulos nao importam classes uns dos outros. Comunicacao acontece via Events que vivem no modulo emissor e Listeners que vivem no modulo receptor. O Laravel auto-descobre listeners pelo type-hint do `handle()`.

### Mapa de Events

| Event                | Modulo Emissor   | Listeners (Modulo Receptor)                                        |
| :------------------- | :--------------- | :----------------------------------------------------------------- |
| `FileUploaded`       | vault            | content-pipeline (thumbnail + scan)                                |
| `FileDownloaded`     | vault            | (sem listeners no MVP)                                             |
| `FileExpired`        | vault            | content-pipeline (cleanup thumbnail)                               |
| `FileDeleted`        | vault            | content-pipeline (cleanup thumbnail)                               |
| `BucketCreated`      | vault            | (sem listeners no MVP)                                             |
| `FileScanCompleted`  | content-pipeline | content-pipeline (update file status via UpdateFileStatusListener) |
| `FileScanFailed`     | content-pipeline | (sem listeners no MVP)                                             |
| `ThumbnailGenerated` | content-pipeline | (sem listeners no MVP)                                             |

### Fluxo: Upload de Arquivo

```
 USER                                    SYSTEM
  |                                        |
  |  PUT /api/v1/files                     |
  |  (multipart: file + password? + ttl?)  |
  | ────────────────────────────────────>  |
  |                                        |  vault: StoreFileAction
  |                                        |    validation: mimes, max size
  |                                        |    Storage::disk(config)->putFileAs(...)
  |                                        |    File::create([...])
  |                                        |    FileUploaded::dispatch($file)
  |                                        |
  |    {"data": {                          |
  |      "token": "a1b2c3d4",             |
  |      "url": "/f/a1b2c3d4/photo.png",  |
  |      "expires_at": "2026-06-25T..."    |
  |    }}                                  |
  | <────────────────────────────────────  |
  |                                        |
  |  (assincrono — Listeners em filas)     |
  |                                        |
  |  ┌─ content-pipeline ──────────────┐   |
  |  | GenerateThumbnailListener       |   |
  |  |   -> handle(FileUploaded)       |   |
  |  |   -> GenerateThumbnailJob       |   |
  |  |      queue: thumbnails          |   |
  |  |                                 |   |
  |  | ScanFileListener               |   |
  |  |   -> handle(FileUploaded)       |   |
  |  |   -> ScanFileJob                |   |
  |  |      queue: antivirus           |   |
  |  |      driver: NullScanner (MVP)  |   |
  |  └────────────────────────────────┘   |
```

### Fluxo: Download de Arquivo

```
 USER                                    SYSTEM
  |                                        |
  |  GET /f/{token}/{filename}             |
  | ────────────────────────────────────>  |
  |                                        |  vault: FileServeController
  |                                        |    Cache::remember("file:{token}")
  |                                        |    verificar: expirado? -> 404 + cleanup
  |                                        |    verificar: senha? -> 403
  |                                        |    verificar: one-time? -> soft delete apos
  |                                        |
  |    Stream do arquivo (proxy)           |
  | <────────────────────────────────────  |
  |                                        |  FileDownloaded::dispatch($file)
```

### Fluxo: Expiracao Automatica

```
 SCHEDULER                                SYSTEM
  |                                        |
  |  hourly: CleanExpiredFilesCommand      |
  | ────────────────────────────────────>  |
  |                                        |  vault: File::expired() scope
  |                                        |    ->chunkById(100, function ($files) {
  |                                        |      foreach: Storage::delete(...)
  |                                        |      foreach: FileExpired::dispatch($file)
  |                                        |      $file->forceDelete()
  |                                        |    })
  |                                        |
  |  ┌─ content-pipeline ──────────────┐   |
  |  | CleanupThumbnailListener       |   |
  |  |   -> handle(FileExpired)        |   |
  |  |   -> delete thumbnail           |   |
  |  └────────────────────────────────┘   |
```

## API REST

Base URL: `/api/v1`

### Autenticacao

Modelo hibrido. Uploads anonimos com limites menores (tamanho e TTL curto). Usuarios autenticados via Sanctum Bearer token tem limites maiores, acesso a buckets e albuns.

```
POST /api/v1/auth/token    # Gerar token Sanctum
```

### Files

```
PUT    /api/v1/files               # Upload (anonimo ou autenticado)
GET    /api/v1/files/{token}       # Info do arquivo
PATCH  /api/v1/files/{token}       # Atualizar (password, TTL)
DELETE /api/v1/files/{token}       # Deletar
```

### File Serving (raw download)

```
GET /f/{token}/{filename}          # Download/stream do arquivo (proxy)
```

### File Preview (pagina HTML)

```
GET /preview/{token}/{filename}    # Preview com renderizacao por mime type
```

### Buckets (requer Sanctum)

```
POST   /api/v1/buckets                    # Criar bucket
GET    /api/v1/buckets/{token}            # Info
DELETE /api/v1/buckets/{token}            # Deletar
GET    /api/v1/buckets/{token}/files      # Listar arquivos
```

### Albums (requer Sanctum, scoped ao bucket)

```
POST   /api/v1/buckets/{bucket}/albums                 # Criar album
GET    /api/v1/buckets/{bucket}/albums/{album}          # Info
DELETE /api/v1/buckets/{bucket}/albums/{album}          # Deletar
```

### Acesso publico (share tokens)

```
GET /api/v1/shared/buckets/{share_token}    # Bucket compartilhado (read-only)
GET /api/v1/shared/albums/{share_token}     # Album compartilhado (read-only)
```

### Exemplo de Upload

```bash
# Upload anonimo
curl -X PUT https://h-vault.example.com/api/v1/files \
  -F "file=@photo.png" \
  -F "password=optional-secret" \
  -F "expires_in=24h"

# Upload autenticado (limites maiores)
curl -X PUT https://h-vault.example.com/api/v1/files \
  -H "Authorization: Bearer {sanctum-token}" \
  -F "file=@video.mp4" \
  -F "bucket_id=abc-123"
```

### Exemplo de Response

```json
{
    "data": {
        "token": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "url": "/f/a1b2c3d4-e5f6-7890-abcd-ef1234567890/photo.png",
        "original_name": "photo.png",
        "mime_type": "image/png",
        "file_size": 245760,
        "status": "pending",
        "is_protected": true,
        "expires_at": "2026-06-25T14:30:00Z",
        "created_at": "2026-05-26T14:30:00Z"
    }
}
```

## Database Schema

Todas as tabelas usam o prefixo do modulo ao qual pertencem. Models definem `$table` explicitamente.

| Modulo   | Model     | Tabela                 |
| :------- | :-------- | :--------------------- |
| identity | User      | `identity_users`       |
| identity | Team      | `identity_teams`       |
| identity | Role      | `identity_roles`       |
| vault    | File      | `vault_files`          |
| vault    | Bucket    | `vault_buckets`        |
| vault    | Album     | `vault_albums`         |
| security | BlockedIp | `security_blocked_ips` |

### `vault_files`

| Column           | Type           | Notes                                            |
| :--------------- | :------------- | :----------------------------------------------- |
| `id`             | `VARCHAR PK`   | UUID via HasUuids                                |
| `token`          | `UUID`         | Unique, indexed                                  |
| `uploaded_by`    | `VARCHAR FK`   | Nullable, references `identity_users`            |
| `original_name`  | `VARCHAR(255)` |                                                  |
| `storage_path`   | `VARCHAR(512)` | Path no storage disk                             |
| `mime_type`      | `VARCHAR(127)` |                                                  |
| `file_size`      | `INTEGER`      | Bytes                                            |
| `password_hash`  | `VARCHAR(255)` | Nullable, Argon2                                 |
| `status`         | `VARCHAR(20)`  | Enum cast: pending, clean, infected, quarantined |
| `thumbnail_path` | `VARCHAR(512)` | Nullable                                         |
| `is_one_time`    | `BOOLEAN`      | Default false                                    |
| `expires_at`     | `DATETIME`     | Indexed para cleanup                             |
| `created_at`     | `DATETIME`     |                                                  |
| `updated_at`     | `DATETIME`     |                                                  |
| `deleted_at`     | `DATETIME`     | Nullable, soft deletes para quarentena           |
| `bucket_id`      | `VARCHAR FK`   | Nullable, references `vault_buckets`             |
| `album_id`       | `VARCHAR FK`   | Nullable, references `vault_albums`              |

### `vault_buckets`

| Column        | Type           | Notes                       |
| :------------ | :------------- | :-------------------------- |
| `id`          | `VARCHAR PK`   | UUID via HasUuids           |
| `user_id`     | `VARCHAR FK`   | References `identity_users` |
| `token`       | `UUID`         | Unique, token privado       |
| `share_token` | `UUID`         | Nullable, token publico     |
| `name`        | `VARCHAR(255)` | Nullable                    |
| `created_at`  | `DATETIME`     |                             |
| `updated_at`  | `DATETIME`     |                             |

### `vault_albums`

| Column        | Type           | Notes                      |
| :------------ | :------------- | :------------------------- |
| `id`          | `VARCHAR PK`   | UUID via HasUuids          |
| `bucket_id`   | `VARCHAR FK`   | References `vault_buckets` |
| `name`        | `VARCHAR(255)` |                            |
| `is_shared`   | `BOOLEAN`      | Default false              |
| `share_token` | `UUID`         | Nullable                   |
| `created_at`  | `DATETIME`     |                            |
| `updated_at`  | `DATETIME`     |                            |

### `security_blocked_ips`

| Column          | Type         | Notes             |
| :-------------- | :----------- | :---------------- |
| `id`            | `VARCHAR PK` | UUID via HasUuids |
| `ip`            | `VARCHAR`    | Unique            |
| `reason`        | `TEXT`       |                   |
| `blocked_until` | `DATETIME`   | Nullable (perma)  |
| `created_at`    | `DATETIME`   |                   |

### ERD

```
identity_users 1──N vault_buckets 1──N vault_albums
                          |
                          └──N vault_files N──1 vault_albums (optional)
                                   |
                                   └──1 identity_users (optional, uploaded_by)

security_blocked_ips (standalone, security module)
```

## TTL (Time To Live)

Arquivos expiram automaticamente. O TTL eh calculado com uma curva cubica baseada na proporcao do tamanho do arquivo em relacao ao limite maximo.

```
Formula: TTL = min + (max - min) * (1 - ratio)^3
```

| Contexto    | TTL min | TTL max |
| :---------- | :------ | :------ |
| Anonimo     | 1 dia   | 7 dias  |
| Autenticado | 1 dia   | 30 dias |

Usuarios autenticados podem definir TTL customizado (dentro dos limites via `VAULT_TTL_CUSTOM_OVERRIDE`). Uploads anonimos usam a curva automatica.

## Security

### Rate Limiting

Limites configurados no modulo `security` via `RateLimiter::for()`:

| Rota                | Anonimo        | Autenticado      |
| :------------------ | :------------- | :--------------- |
| `PUT /api/v1/files` | 10/min por IP  | 60/min por user  |
| `GET /f/{token}/*`  | 100/min por IP | 300/min por user |
| `POST /api/v1/auth` | 5/min por IP   | -                |

### IP Blocking

Middleware `vault.ip-block` aplicado em todas as rotas do vault. IPs bloqueados sao cacheados e verificados antes de processar a request. Bloqueios podem ser temporarios ou permanentes. CRUD completo via `panel-admin`.

### Antivirus Pipeline (MVP)

1. Upload completa -> `FileUploaded` event
2. `ScanFileListener` despacha `ScanFileJob` na fila `antivirus`
3. `NullScanner` marca todos os arquivos como `clean` (MVP)
4. `FileScanCompleted` event com resultado
5. `UpdateFileStatusListener` atualiza status do arquivo

O contrato `AntivirusScanner` permite trocar para ClamAV sem mudar o resto do codigo (ver ADR-0005).

### Protecao por Senha

Arquivos podem ter senha definida no upload. Senha eh hasheada com Argon2 e verificada no download. Arquivos protegidos retornam 403 sem o header `X-File-Password`.

### Upload One-Time

Flag `is_one_time` marca arquivos para delecao apos primeiro download bem-sucedido.

## Landing Page

Pagina publica construida com Livewire + Tailwind CSS no modulo `panel-guest`.

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                      H-Vault                         │
│             Temporary file hosting                   │
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │                                                │  │
│  │     Choose a file or drag and drop             │  │
│  │                                                │  │
│  │         [ Max: 100 MB ]                        │  │
│  │                                                │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Password protection: [______________]               │
│  Expires in:          [  24 hours  v ]               │
│  One-time download:   [ ]                            │
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │              [ Upload ]                        │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Apos upload:                                        │
│  ┌────────────────────────────────────────────────┐  │
│  │  ✓ File uploaded!                             │  │
│  │  Preview link: https://h-vault.../preview/... │  │
│  │  Expires 23 hours from now                    │  │
│  │  [Copy] [Upload another file]                 │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
├──────────────────────────────────────────────────────┤
│  API Usage                                           │
│  curl -X PUT https://h-vault.../api/v1/files ...    │
├──────────────────────────────────────────────────────┤
│  N files hosted  (cached counter, 5 min TTL)         │
└──────────────────────────────────────────────────────┘
```

## Storage

Arquivos sao armazenados via Flysystem. O disk eh configuravel via `VAULT_STORAGE_DISK` (default: `s3`). Para desenvolvimento local, usar `local`.

```env
# Desenvolvimento (local)
VAULT_STORAGE_DISK=local

# Producao (S3/R2/MinIO)
VAULT_STORAGE_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=auto
AWS_BUCKET=h-vault
AWS_ENDPOINT=https://account-id.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Estrutura de paths no storage:

```
files/{year}/{month}/{uuid}.{ext}          # Arquivos originais
thumbnails/{year}/{month}/{uuid}_thumb.webp # Thumbnails
```

## Configuracao

### Variaveis de Ambiente

```env
# App
APP_NAME=H-Vault
APP_URL=https://h-vault.example.com

# Database (SQLite para dev, PostgreSQL para prod)
DB_CONNECTION=sqlite

# Queue (database para dev, redis para prod)
QUEUE_CONNECTION=database

# Storage
VAULT_STORAGE_DISK=local

# Vault
VAULT_UPLOAD_LIMIT_MB=100
VAULT_UPLOAD_LIMIT_AUTH_MB=500
VAULT_TTL_ANON_MIN_DAYS=1
VAULT_TTL_ANON_MAX_DAYS=7
VAULT_TTL_AUTH_MIN_DAYS=1
VAULT_TTL_AUTH_MAX_DAYS=30
VAULT_ALLOWED_MIMES=image/*,video/*,audio/*,application/pdf,application/zip
VAULT_RATE_LIMIT_ANON=10
VAULT_RATE_LIMIT_AUTH=60

# Antivirus
ANTIVIRUS_DRIVER=null
```

### Docker Compose (Desenvolvimento)

```yaml
services:
    sycorax-db:
        image: postgres:18
        ports: ['5432:5432']

    sycorax-redis:
        image: redis:8
        ports: ['6379:6379']

    sycorax-minio:
        image: minio/minio
        command: server /data --console-address ":9001"
        ports: ['9000:9000', '9001:9001']

    sycorax-mailpit:
        image: axllent/mailpit
        ports: ['1025:1025', '8025:8025']
```

## ADRs (Architecture Decision Records)

| ADR  | Decisao                                             |
| :--- | :-------------------------------------------------- |
| 0001 | Event-driven module communication                   |
| 0002 | Incremental migrations per PRD                      |
| 0003 | No CAPTCHA anywhere                                 |
| 0004 | Proxy streaming for downloads                       |
| 0005 | NullScanner as default antivirus in MVP             |
| 0006 | Static counter in MVP, real-time analytics deferred |

## Quick Start

```bash
# 1. Clone e instale
git clone https://github.com/danielhe4rt/h-vault.git
cd h-vault
make setup

# 2. Suba a infra (PostgreSQL, Redis, MinIO, Mailpit)
make env-up

# 3. Configure o .env
cp .env.example .env
php artisan key:generate

# 4. Rode migracoes
php artisan migrate --seed

# 5. Inicie o dev server
make dev

# 6. Acesse
# Landing:     http://h-vault.test
# User Panel:  http://h-vault.test/app
# Admin Panel: http://h-vault.test/admin
# MinIO:       http://localhost:9001
# Mailpit:     http://localhost:8025
```

## Makefile

| Comando              | Alias | Descricao                        |
| :------------------- | :---: | :------------------------------- |
| `make test`          |  `t`  | Rodar todos os testes Pest       |
| `make test-feature`  |       | Somente testes de feature        |
| `make test-unit`     |       | Somente testes de unit           |
| `make pint`          |       | Formatar com Pint                |
| `make phpstan`       |  `p`  | Analise estatica                 |
| `make check`         |  `c`  | Dry-run: Rector + Pint + PHPStan |
| `make format`        |  `f`  | Rector + Pint fixes              |
| `make route-list`    | `rl`  | Listar rotas                     |
| `make migrate-fresh` |       | Reset e seed do banco            |
| `make env-up`        |       | Docker Compose up                |
| `make env-down`      |       | Docker Compose down              |
| `make dev`           |       | Server + Queue + Pail + Vite     |
| `make setup`         |       | Setup inicial do projeto         |

## Licenca

MIT
