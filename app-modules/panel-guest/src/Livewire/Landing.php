<?php

declare(strict_types=1);

namespace He4rt\PanelGuest\Livewire;

use DateTimeInterface;
use He4rt\Vault\Files\Actions\StoreFileAction;
use He4rt\Vault\Files\File;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;

class Landing extends Component
{
    use WithFileUploads;

    public $file;

    public string $password = '';

    public string $expiresIn = '24h';

    public bool $isOneTime = false;

    /** @var array{token: string, url: string, original_name: string, expires_at: string}|null */
    public ?array $uploadedFile = null;

    public function store(StoreFileAction $action): void
    {
        $maxSizeKb = (int) config('vault.upload.max_size_mb', 100) * 1024;

        $this->validate([
            'file' => ['required', 'file', 'max:'.$maxSizeKb],
        ]);

        $expiresAt = $this->parseExpiresIn($this->expiresIn);

        $file = $action->handle(
            uploadedFile: $this->file,
            password: $this->password ?: null,
            isOneTime: $this->isOneTime,
            expiresAt: $expiresAt,
        );

        $this->uploadedFile = [
            'token' => $file->token,
            'url' => route('guest.file-preview', [
                'token' => $file->token,
                'filename' => $file->original_name,
            ]),
            'original_name' => $file->original_name,
            'expires_at' => $file->expires_at->diffForHumans(),
        ];

        $this->reset('file', 'password', 'isOneTime');
    }

    public function resetUpload(): void
    {
        $this->reset('uploadedFile', 'file', 'password', 'isOneTime');
        $this->expiresIn = '24h';
    }

    public function getFileCountProperty(): int
    {
        return Cache::remember('files:total', 300, fn () => File::query()->count());
    }

    public function render(): View
    {
        return view('panel-guest::livewire.landing')
            ->layout('panel-guest::layouts.guest');
    }

    private function parseExpiresIn(string $value): DateTimeInterface
    {
        preg_match('/^(\d+)([hdwm])$/', $value, $matches);
        $num = (int) ($matches[1] ?? 24);
        $unit = $matches[2] ?? 'h';

        return match ($unit) {
            'h' => now()->addHours($num),
            'd' => now()->addDays($num),
            'w' => now()->addWeeks($num),
            'm' => now()->addMonths($num),
            default => now()->addHours(24),
        };
    }
}
