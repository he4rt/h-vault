<?php

declare(strict_types=1);

namespace He4rt\Vault\Http\Controllers\Api;

use He4rt\Vault\Files\Actions\ServeFileAction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileServeController
{
    public function __invoke(string $token, string $filename, Request $request, ServeFileAction $action): StreamedResponse
    {
        return $action->handle(
            token: $token,
            password: $request->header('X-File-Password'),
        );
    }
}
