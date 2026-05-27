<?php

declare(strict_types=1);

namespace He4rt\ContentPipeline\Thumbnails;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;

/**
 * Generates thumbnail images for uploaded files.
 *
 * When the Imagick extension is available, images are resized and converted
 * to WebP. Otherwise the original file content is stored as-is under the
 * thumbnails prefix (graceful degradation for environments without Imagick).
 */
final class ThumbnailGenerator
{
    public function generate(string $storagePath, string $disk): string
    {
        $originalContent = Storage::disk($disk)->get($storagePath);

        $year = now()->format('Y');
        $month = now()->format('m');
        $uuid = Str::uuid()->toString();
        $thumbnailPath = sprintf('thumbnails/%s/%s/%s_thumb.webp', $year, $month, $uuid);

        $thumbnailContent = $this->processImage($originalContent ?? '');

        Storage::disk($disk)->put($thumbnailPath, $thumbnailContent);

        return $thumbnailPath;
    }

    private function processImage(string $content): string
    {
        $imagickIsAvailable = extension_loaded('imagick') && class_exists(Imagick::class);

        if (!$imagickIsAvailable || $content === '') {
            return $content;
        }

        try {
            $width = (int) config('content-pipeline.thumbnails.width', 300);
            $height = (int) config('content-pipeline.thumbnails.height', 300);
            $quality = (int) config('content-pipeline.thumbnails.quality', 80);

            $imagick = new Imagick();
            $imagick->readImageBlob($content);
            $imagick->thumbnailImage($width, $height, bestfit: true);
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality($quality);

            return $imagick->getImageBlob();
        } catch (Exception) {
            return $content;
        }
    }
}
