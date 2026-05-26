# H-Vault

Servi de hospedagem de arquivos temporarios inspirado no [WaifuVault](https://waifuvault.moe), reconstruido do zero em Laravel.

Upload anonimo com limites ou autenticado via Sanctum. Arquivos expiram automaticamente com base em uma curva de TTL proporcional ao tamanho. Painel de administracao via Filament, painel do usuario para gerenciar buckets e albuns, e uma landing page publica com Livewire + Flux UI.

## Stack

| Componente       | Tecnologia                           |
| :--------------- | :----------------------------------- |
| Framework        | Laravel 13                           |
| Admin Panel      | Filament v5 (`panel-admin`)          |
| User Panel       | Filament v5 (`panel-app`)            |
| Landing Page     | Livewire v4 + Flux UI                |
| API Auth         | Laravel Sanctum (Bearer tokens)      |
| Storage          | Flysystem S3 (Cloudflare R2 / MinIO) |
| Database         | PostgreSQL 18                        |
| Cache / Queues   | Redis 8                              |
| Queue Monitor    | Laravel Horizon                      |
| Image Processing | Intervention Image (Imagick)         |
| Antivirus        | ClamAV (CLI)                         |
| File Reputation  | VirusTotal API                       |
| CAPTCHA          | Cloudflare Turnstile                 |
| Real-time        | Laravel Reverb                       |
| Containerizacao  | Docker Compose                       |

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
│ content-pipeline   │    │    analytics       │
│  Thumbnails        │    │  Counters          │
│  Zip               │    │  Broadcasting      │
│  Antivirus         │    │  Stats             │
└────────┬───────────┘    └────────────────────┘
         │
         │ dispatch Events
         ▼
┌────────────────────┐
│    security        │
│  Rate Limiting     │
│  IP Blocking       │
│  CAPTCHA           │
│  File Reputation   │
└────────────────────┘
```

**Regra de dependencia**: imports vao sempre "para cima" (em direcao ao `vault` e `identity`). Events fluem "para baixo". Nunca o contrario.

## Modulos

### `identity` (existente)

Core compartilhado. Users, Teams, Roles (Spatie Permission), ExternalIdentity. Todos os outros modulos importam models daqui.

### `vault`

Coracao do negocio. Models de File, Bucket e Album. API REST publica. Actions para upload, download e delecao. Calculo de TTL. Scheduled command para limpeza de arquivos expirados.

### `content-pipeline`

Processamento assincrono de conteudo. Escuta events do `vault` e despacha jobs em filas dedicadas. Thumbnails via Intervention Image, zip via ZipArchive, scan antivirus via ClamAV. Emite seus proprios events quando o processamento termina.

### `security`

Rate limiting, bloqueio de IP, verificacao CAPTCHA e checagem de reputacao de arquivo via VirusTotal. Escuta events do `content-pipeline` para reagir a scans completos. Middleware de bloqueio de IP aplicado nas rotas do vault.

### `panel-app`

Painel Filament do usuario. Resources para Files, Buckets e Albums. Widgets de uso de storage e uploads recentes. Tambem contem as paginas Livewire publicas: landing page (hero + upload + SDK info) e pagina de download com verificacao de senha.

### `analytics`

Metricas e contadores em tempo real. Escuta events do vault para manter contadores Redis. Broadcasting via Laravel Reverb para atualizar o frontend em tempo real.

### `panel-admin` (existente)

Painel Filament administrativo. Resources para gerenciamento de arquivos, IPs bloqueados e estatisticas do sistema.

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
│   │   └── vault.php                  #   upload limits, TTL curve, storage disk, allowed mimes
│   ├── src/
│   │   ├── Providers/
│   │   │   └── VaultServiceProvider.php
│   │   ├── Files/
│   │   │   ├── File.php               #   Model
│   │   │   ├── FilePolicy.php
│   │   │   ├── FileStatus.php         #   Enum: pending, clean, infected, quarantined
│   │   │   └── Actions/
│   │   │       ├── StoreFileAction.php
│   │   │       ├── ServeFileAction.php
│   │   │       └── DeleteFileAction.php
│   │   ├── Buckets/
│   │   │   ├── Bucket.php
│   │   │   ├── BucketPolicy.php
│   │   │   └── Actions/
│   │   │       └── CreateBucketAction.php
│   │   ├── Albums/
│   │   │   ├── Album.php
│   │   │   └── AlbumPolicy.php
│   │   ├── TTL/
│   │   │   ├── TtlCalculator.php
│   │   │   └── CleanExpiredFilesCommand.php
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   │   ├── FileController.php
│   │   │   │   ├── BucketController.php
│   │   │   │   ├── AlbumController.php
│   │   │   │   └── FileServeController.php
│   │   │   ├── Requests/
│   │   │   │   ├── StoreFileRequest.php
│   │   │   │   ├── StoreBucketRequest.php
│   │   │   │   └── StoreAlbumRequest.php
│   │   │   └── Resources/
│   │   │       ├── FileResource.php
│   │   │       ├── BucketResource.php
│   │   │       └── AlbumResource.php
│   │   └── Events/
│   │       ├── FileUploaded.php
│   │       ├── FileDownloaded.php
│   │       ├── FileExpired.php
│   │       ├── FileDeleted.php
│   │       ├── FileStatusChanged.php
│   │       └── BucketCreated.php
│   ├── routes/
│   │   └── vault-api.php
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   └── tests/
│
├── content-pipeline/                  # Processamento assincrono
│   ├── config/
│   │   └── content-pipeline.php       #   thumbnail sizes, antivirus path, zip limits
│   ├── src/
│   │   ├── Providers/
│   │   │   └── ContentPipelineServiceProvider.php
│   │   ├── Thumbnails/
│   │   │   ├── GenerateThumbnailJob.php
│   │   │   ├── GenerateThumbnailListener.php
│   │   │   └── ThumbnailGenerator.php
│   │   ├── Zip/
│   │   │   ├── CreateZipArchiveJob.php
│   │   │   └── ZipArchiveService.php
│   │   ├── Antivirus/
│   │   │   ├── ScanFileJob.php
│   │   │   ├── ScanFileListener.php
│   │   │   ├── Contracts/
│   │   │   │   └── AntivirusScanner.php
│   │   │   └── Drivers/
│   │   │       ├── ClamAvScanner.php
│   │   │       └── NullScanner.php
│   │   ├── Events/
│   │   │   ├── FileScanCompleted.php
│   │   │   ├── FileScanFailed.php
│   │   │   └── ThumbnailGenerated.php
│   │   └── Listeners/
│   │       └── UpdateFileStatusListener.php
│   └── tests/
│
├── security/                          # Rate limiting, IP blocking, CAPTCHA
│   ├── config/
│   │   └── security.php               #   rate limits, captcha provider, virustotal key
│   ├── src/
│   │   ├── Providers/
│   │   │   └── SecurityServiceProvider.php
│   │   ├── RateLimiting/
│   │   │   └── VaultRateLimiter.php
│   │   ├── IpBlocking/
│   │   │   ├── BlockedIp.php
│   │   │   ├── BlockIpAction.php
│   │   │   └── IpBlockingMiddleware.php
│   │   ├── Captcha/
│   │   │   ├── Contracts/
│   │   │   │   └── CaptchaVerifier.php
│   │   │   └── Drivers/
│   │   │       ├── TurnstileVerifier.php
│   │   │       ├── RecaptchaVerifier.php
│   │   │       └── NullVerifier.php
│   │   ├── FileReputation/
│   │   │   ├── CheckFileReputationListener.php
│   │   │   ├── VirusTotalClient.php
│   │   │   └── Events/
│   │   │       └── FileQuarantined.php
│   │   └── Listeners/
│   │       └── BlockSuspiciousIpListener.php
│   └── tests/
│
├── panel-app/                         # Filament user panel + Landing
│   ├── src/
│   │   ├── Providers/
│   │   │   └── PanelAppServiceProvider.php
│   │   ├── Filament/
│   │   │   ├── Resources/
│   │   │   │   ├── FileResource.php
│   │   │   │   ├── BucketResource.php
│   │   │   │   └── AlbumResource.php
│   │   │   ├── Pages/
│   │   │   │   └── Dashboard.php
│   │   │   └── Widgets/
│   │   │       ├── StorageUsageWidget.php
│   │   │       └── RecentUploadsWidget.php
│   │   └── Livewire/
│   │       ├── Landing.php
│   │       └── FileDownload.php
│   ├── resources/views/livewire/
│   │   ├── landing.blade.php
│   │   └── file-download.blade.php
│   └── routes/
│       └── panel-app-routes.php
│
├── analytics/                         # Metricas e real-time
│   ├── config/
│   │   └── analytics.php              #   counter strategy, broadcast driver
│   ├── src/
│   │   ├── Providers/
│   │   │   └── AnalyticsServiceProvider.php
│   │   ├── Counters/
│   │   │   ├── FileCounter.php
│   │   │   └── BandwidthTracker.php
│   │   ├── Listeners/
│   │   │   ├── IncrementFileCounterListener.php
│   │   │   ├── DecrementFileCounterListener.php
│   │   │   ├── TrackDownloadListener.php
│   │   │   └── TrackUploadListener.php
│   │   └── Broadcasting/
│   │       └── FileCountUpdated.php
│   └── tests/
│
└── panel-admin/                       # (EXISTE) Admin Panel
```

## Event-Driven Communication

Modulos nao importam classes uns dos outros. Comunicacao acontece via Events que vivem no modulo emissor e Listeners que vivem no modulo receptor. O Laravel auto-descobre listeners pelo type-hint do `handle()`.

### Mapa de Events

| Event                  | Modulo Emissor   | Listeners (Modulo Receptor)                                     |
| :--------------------- | :--------------- | :-------------------------------------------------------------- |
| `FileUploaded`         | vault            | content-pipeline (thumbnail, scan), analytics (counter)         |
| `FileDownloaded`       | vault            | analytics (counter, bandwidth)                                  |
| `FileExpired`          | vault            | content-pipeline (cleanup thumbnail), analytics (counter)       |
| `FileDeleted`          | vault            | content-pipeline (cleanup), analytics (counter)                 |
| `BucketCreated`        | vault            | analytics (counter)                                             |
| `FileScanCompleted`    | content-pipeline | security (reputation check), vault (update status via listener) |
| `FileScanFailed`       | content-pipeline | vault (mark as failed)                                          |
| `ThumbnailGenerated`   | content-pipeline | vault (update thumbnail_path)                                   |
| `FileQuarantined`      | security         | vault (soft delete), analytics (alert)                          |
| `SuspiciousIpDetected` | security         | vault (block uploads from IP)                                   |

### Fluxo: Upload de Arquivo

```
 USER                                    SYSTEM
  |                                        |
  |  PUT /api/v1/files                     |
  |  (multipart: file + password? + ttl?)  |
  | ────────────────────────────────────>  |
  |                                        |  vault: StoreFileRequest
  |                                        |    validation: mimes, max size, captcha
  |                                        |  vault: StoreFileAction
  |                                        |    Storage::disk('s3')->putFileAs(...)
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
  |  └────────────────────────────────┘   |
  |                                        |
  |  ┌─ analytics ────────────────────┐   |
  |  | IncrementFileCounterListener   |   |
  |  |   -> handle(FileUploaded)       |   |
  |  |   -> Redis INCR files:total    |   |
  |  |   -> broadcast(FileCountUpdated)|  |
  |  └────────────────────────────────┘   |
  |                                        |
  |  ┌─ security ─────────────────────┐   |
  |  | CheckFileReputationListener    |   |
  |  |   -> handle(FileScanCompleted)  |   |
  |  |   -> VirusTotal API            |   |
  |  |   -> se malicioso:             |   |
  |  |      FileQuarantined::dispatch |   |
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
  |    Stream do arquivo via S3            |
  | <────────────────────────────────────  |
  |                                        |  FileDownloaded::dispatch($file)
  |                                        |
  |  ┌─ analytics ────────────────────┐   |
  |  | TrackDownloadListener          |   |
  |  |   -> bandwidth tracking        |   |
  |  └────────────────────────────────┘   |
```

### Fluxo: Expiracao Automatica

```
 SCHEDULER                                SYSTEM
  |                                        |
  |  hourly: CleanExpiredFilesCommand      |
  | ────────────────────────────────────>  |
  |                                        |  vault: File::expired()  (scope on vault_files)
  |                                        |    ->chunkById(100, function ($files) {
  |                                        |      foreach: Storage::disk('s3')->delete(...)
  |                                        |      foreach: FileExpired::dispatch($file)
  |                                        |      $file->forceDelete()
  |                                        |    })
  |                                        |
  |  ┌─ content-pipeline ──────────────┐   |
  |  | CleanupThumbnailListener       |   |
  |  |   -> handle(FileExpired)        |   |
  |  |   -> delete thumbnail from S3  |   |
  |  └────────────────────────────────┘   |
  |                                        |
  |  ┌─ analytics ────────────────────┐   |
  |  | DecrementFileCounterListener   |   |
  |  |   -> handle(FileExpired)        |   |
  |  |   -> Redis DECR files:total    |   |
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

### File Serving

```
GET /f/{token}/{filename}          # Download/stream do arquivo
GET /f/{token}/thumbnail           # Thumbnail
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
GET    /api/v1/buckets/{bucket}/albums/{album}/zip      # Download ZIP
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
| `id`             | `BIGINT PK`    | Auto-increment                                   |
| `token`          | `UUID`         | Unique, indexed                                  |
| `bucket_id`      | `BIGINT FK`    | Nullable, references `vault_buckets`             |
| `album_id`       | `BIGINT FK`    | Nullable, references `vault_albums`              |
| `uploaded_by`    | `BIGINT FK`    | Nullable, references `identity_users`            |
| `original_name`  | `VARCHAR(255)` |                                                  |
| `storage_path`   | `VARCHAR(512)` | Path no S3                                       |
| `mime_type`      | `VARCHAR(127)` |                                                  |
| `file_size`      | `BIGINT`       | Bytes                                            |
| `password_hash`  | `VARCHAR(255)` | Nullable, Argon2                                 |
| `status`         | `VARCHAR(20)`  | Enum cast: pending, clean, infected, quarantined |
| `thumbnail_path` | `VARCHAR(512)` | Nullable, path no S3                             |
| `is_one_time`    | `BOOLEAN`      | Default false                                    |
| `expires_at`     | `TIMESTAMP`    | Indexed para cleanup                             |
| `created_at`     | `TIMESTAMP`    |                                                  |
| `updated_at`     | `TIMESTAMP`    |                                                  |
| `deleted_at`     | `TIMESTAMP`    | Nullable, soft deletes para quarentena           |

### `vault_buckets`

| Column        | Type           | Notes                       |
| :------------ | :------------- | :-------------------------- |
| `id`          | `BIGINT PK`    |                             |
| `user_id`     | `BIGINT FK`    | References `identity_users` |
| `token`       | `UUID`         | Unique, token privado       |
| `share_token` | `UUID`         | Nullable, token publico     |
| `name`        | `VARCHAR(255)` | Nullable                    |
| `created_at`  | `TIMESTAMP`    |                             |
| `updated_at`  | `TIMESTAMP`    |                             |

### `vault_albums`

| Column        | Type           | Notes                      |
| :------------ | :------------- | :------------------------- |
| `id`          | `BIGINT PK`    |                            |
| `bucket_id`   | `BIGINT FK`    | References `vault_buckets` |
| `name`        | `VARCHAR(255)` |                            |
| `is_shared`   | `BOOLEAN`      | Default false              |
| `share_token` | `UUID`         | Nullable                   |
| `created_at`  | `TIMESTAMP`    |                            |
| `updated_at`  | `TIMESTAMP`    |                            |

### `security_blocked_ips`

| Column          | Type        | Notes            |
| :-------------- | :---------- | :--------------- |
| `id`            | `BIGINT PK` |                  |
| `ip`            | `INET`      | Unique           |
| `reason`        | `TEXT`      |                  |
| `blocked_until` | `TIMESTAMP` | Nullable (perma) |
| `created_at`    | `TIMESTAMP` |                  |

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
TTL em dias

365 |━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━╮
    |                                     ╲
    |                                      ╲
    |                                       ╲
    |                                        ╲
 30 |                                         ━━━
    └──────────────────────────────────────────────
    0%                  50%                  100%
              Tamanho do arquivo / Limite maximo
```

| Tamanho (% do limite) | TTL aproximado |
| :-------------------: | :------------- |
|         0-50%         | ~11-12 meses   |
|          75%          | ~6 meses       |
|          90%          | ~2 meses       |
|         100%          | ~30 dias       |

Usuarios autenticados podem definir TTL customizado (dentro dos limites). Uploads anonimos usam a curva automatica com TTL maximo reduzido.

## Queues (Horizon)

Filas dedicadas por tipo de processamento. Horizon gerencia workers com auto-scaling.

```
┌──────────────────────────────────────────┐
│             Laravel Horizon              │
├──────────────────────────────────────────┤
│                                          │
│  Queue: default                          │
│    Propensity: general jobs              │
│                                          │
│  Queue: thumbnails                       │
│    Job: GenerateThumbnailJob             │
│    Workers: min 1 / max 3               │
│    Timeout: 60s                          │
│                                          │
│  Queue: antivirus                        │
│    Job: ScanFileJob                      │
│    Workers: min 1 / max 2               │
│    Timeout: 120s / retry_after: 180s    │
│    Backoff: [1, 5, 10]                  │
│                                          │
│  Queue: zip                              │
│    Job: CreateZipArchiveJob              │
│    Workers: min 1 / max 5               │
│    Timeout: 300s                         │
│                                          │
│  Queue: reputation                       │
│    Job: CheckFileReputationJob           │
│    Workers: 1                            │
│    Middleware: RateLimited (VirusTotal)  │
│                                          │
└──────────────────────────────────────────┘
```

## Storage (S3-Compatible)

Todos os arquivos sao armazenados via Flysystem S3 driver. Compativel com Cloudflare R2, AWS S3 e MinIO para desenvolvimento local.

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=auto
AWS_BUCKET=h-vault
AWS_ENDPOINT=https://account-id.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Estrutura de paths no bucket:

```
h-vault/
├── files/{year}/{month}/{uuid}.{ext}          # Arquivos originais
├── thumbnails/{year}/{month}/{uuid}_thumb.webp # Thumbnails
└── zip/{uuid}.zip                              # ZIPs temporarios
```

## Security

### Rate Limiting

Limites configurados no modulo `security` via `RateLimiter::for()`:

| Rota                | Anonimo        | Autenticado      |
| :------------------ | :------------- | :--------------- |
| `PUT /api/v1/files` | 10/min por IP  | 60/min por user  |
| `GET /f/{token}/*`  | 100/min por IP | 300/min por user |
| `POST /api/v1/auth` | 5/min por IP   | -                |

### CAPTCHA

Cloudflare Turnstile na landing page e no upload anonimo. Configuravel via interface contract — swap para reCAPTCHA ou hCaptcha sem mudar codigo.

### Antivirus Pipeline

1. Upload completa -> `FileUploaded` event
2. `ScanFileListener` despacha `ScanFileJob` na fila `antivirus`
3. ClamAV faz scan local do arquivo
4. `FileScanCompleted` event com resultado
5. `CheckFileReputationListener` consulta VirusTotal API
6. Se malicioso -> `FileQuarantined` event -> soft delete

### Protecao por Senha

Arquivos podem ter senha definida no upload. Senha eh hasheada com Argon2 e verificada no download. Arquivos protegidos retornam 403 sem o header `X-File-Password`.

### Upload One-Time

Flag `is_one_time` marca arquivos para delecao apos primeiro download bem-sucedido.

## Landing Page

Pagina publica construida com Livewire + Flux UI. Layout simples e funcional:

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                      H-Vault                         │
│           Hospedagem temporaria de arquivos           │
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │                                                │  │
│  │     Arraste um arquivo ou clique para           │  │
│  │              fazer upload                       │  │
│  │                                                │  │
│  │         [ Limite: 100MB anonimo ]              │  │
│  │                                                │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Senha (opcional): [______________]                  │
│  Expira em:        [  24 horas  v ]                 │
│  Download unico:   [ ] Sim                          │
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │            [ Fazer Upload ]                    │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
├──────────────────────────────────────────────────────┤
│                                                      │
│  SDK & Integracao                                    │
│                                                      │
│  curl -X PUT https://h-vault.example.com/api/v1/... │
│                                                      │
│  Pacotes:                                            │
│    Python: pip install h-vault                       │
│    Node:   npm install h-vault-sdk                   │
│    PHP:    composer require he4rt/h-vault-sdk        │
│                                                      │
├──────────────────────────────────────────────────────┤
│  Total de arquivos hospedados: 12,345   (real-time) │
└──────────────────────────────────────────────────────┘
```

## Configuracao

### Variaveis de Ambiente

```env
# App
APP_NAME=H-Vault
APP_URL=https://h-vault.example.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=h_vault
DB_USERNAME=postgres
DB_PASSWORD=secret

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Storage (S3/R2)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=h-vault
AWS_ENDPOINT=

# Vault
VAULT_UPLOAD_LIMIT_MB=1000
VAULT_UPLOAD_LIMIT_ANONYMOUS_MB=100
VAULT_TTL_MAX_DAYS=365
VAULT_TTL_MIN_DAYS=30
VAULT_ALLOWED_MIMES=image/*,video/*,audio/*,application/pdf,application/zip

# Security
VAULT_RATE_LIMIT=100
VAULT_RATE_LIMIT_WINDOW_MS=60000
CAPTCHA_DRIVER=turnstile
CAPTCHA_SITE_KEY=
CAPTCHA_SECRET_KEY=
VIRUSTOTAL_API_KEY=
VIRUSTOTAL_REPUTATION_LIMIT=5

# Antivirus
CLAMAV_BINARY_PATH=/usr/bin/clamscan

# Broadcasting (Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
```

### Docker Compose (Desenvolvimento)

```yaml
services:
    h-vault-db:
        image: postgres:18
        environment:
            POSTGRES_DB: h_vault
            POSTGRES_USER: postgres
            POSTGRES_PASSWORD: postgres
        ports: ['5432:5432']

    h-vault-redis:
        image: redis:8
        ports: ['6379:6379']

    h-vault-minio:
        image: minio/minio
        command: server /data --console-address ":9001"
        environment:
            MINIO_ROOT_USER: minioadmin
            MINIO_ROOT_PASSWORD: minioadmin
        ports:
            - '9000:9000'
            - '9001:9001'

    h-vault-mailpit:
        image: axllent/mailpit
        ports:
            - '1025:1025'
            - '8025:8025'
```

## Quick Start

```bash
# 1. Clone e instale
git clone https://github.com/he4rt/h-vault.git
cd h-vault
make setup

# 2. Suba a infra
make env-up

# 3. Configure o .env
cp .env.example .env
php artisan key:generate

# 4. Rode migracoes
php artisan migrate --seed

# 5. Inicie o dev server
make dev

# 6. Acesse
# Landing:     http://localhost:8000
# User Panel:  http://localhost:8000/app
# Admin Panel: http://localhost:8000/admin
# Horizon:     http://localhost:8000/horizon
# MinIO:       http://localhost:9001
# Mailpit:     http://localhost:8025
```

## Makefile

| Comando              | Alias | Descricao                             |
| :------------------- | :---: | :------------------------------------ |
| `make test`          |  `t`  | Rodar todos os testes Pest            |
| `make test-feature`  |       | Somente testes de feature             |
| `make pint`          |       | Formatar com Pint                     |
| `make phpstan`       |  `p`  | Analise estatica                      |
| `make check`         |  `c`  | Dry-run: Rector + Pint + PHPStan      |
| `make format`        |  `f`  | Rector + Pint fixes                   |
| `make route-list`    | `rl`  | Listar rotas                          |
| `make migrate-fresh` |       | Reset e seed do banco                 |
| `make env-up`        |       | Docker Compose up                     |
| `make env-down`      |       | Docker Compose down                   |
| `make dev`           |       | Server + Queue + Pail + Vite + Reverb |
| `make horizon`       |       | Iniciar Horizon                       |

## Licenca

MIT
