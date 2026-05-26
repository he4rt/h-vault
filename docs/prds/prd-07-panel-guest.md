# PRD-07: Panel Guest — Landing Page & File Preview

**Status:** Draft
**Priority:** P1
**Module:** `panel-guest`
**Prerequisites:** PRD-01 (vault-core), PRD-02 (vault-serving)
**Depends on:** PRD-01, PRD-02
**Blocked by:** PRD-02

## Summary

Create the `panel-guest` module: the public-facing landing page with upload form and the file preview/download page. Built entirely with Livewire + Flux UI. No authentication.

## Motivation

Users need a browser-based interface to upload files anonymously and view/download files via their token URLs. This is the public face of H-Vault.

## Scope

### In Scope

- Module scaffolding via `internachi/modular`
- **Landing page** (`/`): hero section, drag-and-drop upload form, SDK/curl examples, cached file counter
- **File preview page** (`/f/{token}/{filename}`): renders preview based on mime type, password input form, download button
- Livewire components: `Landing.php`, `FilePreview.php`
- Blade views with Flux UI components
- File preview by mime type: images (inline), PDFs (embedded viewer), video/audio (HTML5 player), other (download only)
- Password form: if file is protected, show Flux input + submit before rendering preview
- Cached file counter: `Cache::remember('files:total', 300, fn() => File::count())` (see ADR-0006)
- Routes in `panel-guest-routes.php`
- Flux UI installation if not present

### Out of Scope

- Real-time counter with WebSocket (future analytics PRD)
- User authentication (panel-app handles that)
- Bucket/album browsing (panel-app)

## Pages

### Landing Page (`/`)

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│                      H-Vault                         │
│           Hospedagem temporária de arquivos           │
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │                                                │  │
│  │     Arraste um arquivo ou clique para           │  │
│  │              fazer upload                       │  │
│  │                                                │  │
│  │         [ Limite: 100MB ]                      │  │
│  │                                                │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Senha (opcional): [______________]                  │
│  Expira em:        [  24 horas  v ]                 │
│  Download único:   [ ] Sim                          │
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │            [ Fazer Upload ]                    │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Após upload:                                        │
│  ┌────────────────────────────────────────────────┐  │
│  │  ✓ Arquivo enviado!                           │  │
│  │  Link: https://h-vault.../f/a1b2c3/photo.png │  │
│  │  Expira em: 7 dias                            │  │
│  │  [Copiar link]                                │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
├──────────────────────────────────────────────────────┤
│  SDK & Integração                                    │
│  curl -X PUT https://h-vault.../api/v1/files ...    │
├──────────────────────────────────────────────────────┤
│  Total de arquivos hospedados: 12,345                │
└──────────────────────────────────────────────────────┘
```

### File Preview Page (`/f/{token}/{filename}`)

```
┌──────────────────────────────────────────────────────┐
│  H-Vault                                             │
│                                                      │
│  Se protegido por senha (antes do desbloqueio):      │
│  ┌────────────────────────────────────────────────┐  │
│  │  🔒 Arquivo protegido                         │  │
│  │  Senha: [______________] [Desbloquear]        │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Após desbloqueio (ou se não tem senha):             │
│  ┌────────────────────────────────────────────────┐  │
│  │  📄 photo.png                                 │  │
│  │  245 KB · image/png · Expira em 2026-06-02    │  │
│  ├────────────────────────────────────────────────┤  │
│  │                                                │  │
│  │         [Preview inline da imagem]             │  │
│  │         [ou PDF embeddado]                     │  │
│  │         [ou player de vídeo/áudio]             │  │
│  │         [ou ícone genérico + "Sem preview"]    │  │
│  │                                                │  │
│  ├────────────────────────────────────────────────┤  │
│  │         [ ⬇ Download ]                        │  │
│  └────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────┘
```

## Upload Flow (Landing)

```
 USER                              SYSTEM
  │                                   │
  │  📁 Arrasta arquivo no dropzone   │
  │ ──────────────────────────────►  │
  │                                   │  Livewire: Landing component
  │                                   │    wire:model file upload
  │                                   │    Livewire temp upload
  │                                   │
  │  👆 Clica "Fazer Upload"          │
  │ ──────────────────────────────►  │
  │                                   │  Landing::upload()
  │                                   │    StoreFileAction::execute(...)
  │                                   │    ✓ file stored in S3
  │                                   │    ✓ File record created
  │                                   │
  │    "✓ Arquivo enviado!"           │
  │    Link: /f/a1b2c3/photo.png     │
  │    [Copiar link]                  │
  │ ◄────────────────────────────────│
```

## Preview Flow (File Page)

```
 USER                              SYSTEM
  │                                   │
  │  🔗 Acessa /f/{token}/photo.png   │
  │ ──────────────────────────────►  │
  │                                   │  FilePreview component:
  │                                   │    File::where('token', $token)->first()
  │                                   │    ⚙️ check: exists? → 404 page
  │                                   │    ⚙️ check: expired? → 404 page
  │                                   │    ⚙️ check: has password? → show form
  │                                   │
  │    📱 Renders preview page        │
  │    (image/pdf/video/audio/icon)   │
  │ ◄────────────────────────────────│
  │                                   │
  │  👆 Clica "Download"              │
  │ ──────────────────────────────►  │
  │                                   │  ServeFileAction::execute(...)
  │                                   │    StreamedResponse
  │                                   │    FileDownloaded::dispatch()
  │                                   │
  │    📥 Browser downloads file      │
  │ ◄────────────────────────────────│
```

## Acceptance Criteria

- [ ] `panel-guest` module created via `internachi/modular`
- [ ] Landing page renders at `/` with upload form
- [ ] Drag-and-drop file upload works via Livewire
- [ ] After upload, shows link with copy button
- [ ] Password and expiry options work
- [ ] File counter displayed (cached, not real-time)
- [ ] SDK/curl examples section displayed
- [ ] File preview page renders at `/f/{token}/{filename}`
- [ ] Images render inline (`<img>`)
- [ ] PDFs render embedded (`<embed>` or `<iframe>`)
- [ ] Video/audio render with HTML5 player
- [ ] Other files show download-only view
- [ ] Password-protected files show password form first
- [ ] Expired/missing files show 404 page
- [ ] Dark mode styling works (no hardcoded bg-white)
- [ ] Responsive layout (mobile-friendly)
