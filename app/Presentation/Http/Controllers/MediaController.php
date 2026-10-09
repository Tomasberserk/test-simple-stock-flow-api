<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class MediaController
{
    public function show(string $key): BinaryFileResponse|HttpResponse
    {
        $cleanKey = basename($key);
        $path = 'products/' . $cleanKey;

        if (!Storage::disk('public')->exists($path)) {
            return response('', Response::HTTP_NOT_FOUND);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}
