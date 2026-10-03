<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Ports\Outbound\FileStorage;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

final class LocalFileStorage implements FileStorage
{
    private const DISK = 'public';
    private const DIRECTORY = 'products';

    public function store(string $content, string $extension): string
    {
        $ext = ltrim($extension, '.');
        $key = Uuid::uuid4()->toString() . ($ext !== '' ? ".{$ext}" : '');
        $path = self::DIRECTORY . '/' . $key;

        Storage::disk(self::DISK)->put($path, $content);

        return $key;
    }

    public function delete(string $key): void
    {
        $path = self::DIRECTORY . '/' . $key;
        Storage::disk(self::DISK)->delete($path);
    }

    public function getUrl(string $key): ?string
    {
        $path = self::DIRECTORY . '/' . $key;
        if (!Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }
}
