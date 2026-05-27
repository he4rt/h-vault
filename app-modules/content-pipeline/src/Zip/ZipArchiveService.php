<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Zip;

use He4rt\Vault\Albums\Album;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

final class ZipArchiveService
{
    public function createFromAlbum(Album $album): string
    {
        $disk = config('vault.storage.disk');
        $maxFiles = (int) config('content-pipeline.zip.max_files', 100);

        $files = $album->files()->limit($maxFiles)->get();

        $tempPath = sys_get_temp_dir().'/'.Str::uuid().'.zip';

        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            $content = Storage::disk($disk)->get($file->storage_path);

            if ($content === null) {
                continue;
            }

            $zip->addFromString($file->original_name, $content);
        }

        $zip->close();

        $zipPath = 'zip/'.Str::uuid().'.zip';
        Storage::disk($disk)->put($zipPath, (string) file_get_contents($tempPath));

        @unlink($tempPath);

        return $zipPath;
    }
}
