<?php

declare(strict_types=1);

namespace He4rt\Vault\Events;

use He4rt\Vault\Files\File;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly File $file,
    ) {}
}
