<?php

return [
    'ffmpeg'  => [
        'binaries' => env('FFMPEG_BINARIES'),
        'threads'  => 12
    ],
    'ffprobe' => [
        'binaries' => env('FFPROBE_BINARIES')
    ],
    'timeout' => 3600,
];
