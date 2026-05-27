<?php

declare(strict_types=1);

namespace He4rt\PanelGuest\Livewire;

use He4rt\Vault\Files\Actions\ServeFileAction;
use He4rt\Vault\Files\File;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilePreview extends Component
{
    public ?File $file = null;

    public string $token;

    public string $filename;

    public string $password = '';

    public bool $unlocked = false;

    public bool $notFound = false;

    public bool $expired = false;

    public ?string $error = null;

    public function mount(string $token, string $filename): void
    {
        $this->token = $token;
        $this->filename = $filename;

        $file = File::withTrashed()->where('token', $token)->first();

        if (!$file || $file->trashed()) {
            $this->notFound = true;

            return;
        }

        if ($file->isExpired()) {
            $this->expired = true;

            return;
        }

        $this->file = $file;
        $this->unlocked = !$file->isProtected();
    }

    public function unlock(): void
    {
        if (!$this->file instanceof File) {
            return;
        }

        $passwordIsCorrect = Hash::check($this->password, $this->file->password_hash);

        if ($passwordIsCorrect) {
            $this->unlocked = true;
            $this->error = null;
        } else {
            $this->error = 'Incorrect password.';
        }
    }

    public function download(ServeFileAction $action): StreamedResponse
    {
        abort_if(!$this->file instanceof File || !$this->unlocked, 403);

        return $action->handle(
            token: $this->token,
            password: $this->file->isProtected() ? $this->password : null,
        );
    }

    public function getPreviewTypeProperty(): string
    {
        if (!$this->file instanceof File) {
            return 'generic';
        }

        $mime = $this->file->mime_type;

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        if ($mime === 'application/pdf') {
            return 'pdf';
        }

        return 'generic';
    }

    public function getDownloadUrlProperty(): string
    {
        return route('vault.files.serve', [
            'token' => $this->token,
            'filename' => $this->filename,
        ]);
    }

    public function getFormattedSizeProperty(): string
    {
        if (!$this->file instanceof File) {
            return '';
        }

        $bytes = $this->file->file_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function render(): View
    {
        return view('panel-guest::livewire.file-preview')
            ->layout('panel-guest::layouts.guest');
    }
}
