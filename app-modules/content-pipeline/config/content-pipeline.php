<?php

declare(strict_types=1);

return [
    'thumbnails' => [
        'width' => (int) env('THUMBNAIL_WIDTH', 300),
        'height' => (int) env('THUMBNAIL_HEIGHT', 300),
        'format' => 'webp',
        'quality' => 80,
    ],
    'antivirus' => [
        'driver' => env('ANTIVIRUS_DRIVER', 'null'),
        'clamav_binary' => env('CLAMAV_BINARY_PATH', '/usr/bin/clamscan'),
    ],
    'zip' => [
        'max_files' => (int) env('ZIP_MAX_FILES', 100),
        'max_size_mb' => (int) env('ZIP_MAX_SIZE_MB', 500),
    ],
];
