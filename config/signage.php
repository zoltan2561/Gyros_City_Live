<?php

return [
    // The full device module remains disabled until a pilot explicitly enables it.
    'enabled' => false,
    'poll_seconds' => 30,
    'heartbeat_seconds' => 60,
    'offline_price_seconds' => 1800,
    'video_max_kb' => 512000,
    'image_max_kb' => 20480,
    'video_max_seconds' => 600,
    'ffmpeg' => 'ffmpeg',
    'ffprobe' => 'ffprobe',
    'queue_connection' => 'signage',
    'queue' => 'signage',
];
