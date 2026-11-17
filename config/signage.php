<?php

return [
    // Off unless the environment explicitly switches the module on (SIGNAGE_ENABLED=true in .env).
    // Local loopback access in the local environment is separate and never counts as a production switch.
    'enabled' => filter_var(env('SIGNAGE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
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
