<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class NewsletterImageController extends Controller
{
    public function __invoke(string $image)
    {
        $disk = Storage::disk('public');
        $path = 'newsletter/' . $image;
        abort_unless($disk->exists($path), 404);
        return $disk->response($path, null, ['Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff']);
    }
}
