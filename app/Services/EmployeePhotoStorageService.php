<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeePhotoStorageService
{
    public const PRIVATE_DISK = 'private';

    public const LEGACY_PUBLIC_DISK = 'public';

    /** @return array{disk: string, path: string}|null */
    public function locate(?string $storedPath): ?array
    {
        $path = $this->normalizeRelativePath($storedPath);

        if ($path === null) {
            return null;
        }

        foreach ([self::PRIVATE_DISK, self::LEGACY_PUBLIC_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return ['disk' => $disk, 'path' => $path];
            }
        }

        return null;
    }

    public function store(UploadedFile $file): string
    {
        $path = $file->store('employee-photos', self::PRIVATE_DISK);

        if (! is_string($path) || $path === '' || ! Storage::disk(self::PRIVATE_DISK)->exists($path)) {
            throw new RuntimeException('Foto pegawai gagal disimpan ke private storage.');
        }

        return $path;
    }

    public function deletePath(?string $storedPath): void
    {
        $path = $this->normalizeRelativePath($storedPath);

        if ($path === null) {
            return;
        }

        foreach ([self::PRIVATE_DISK, self::LEGACY_PUBLIC_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    public function moveLegacyToPrivate(?string $storedPath): bool
    {
        $path = $this->normalizeRelativePath($storedPath);

        if ($path === null || ! Storage::disk(self::LEGACY_PUBLIC_DISK)->exists($path)) {
            return false;
        }

        if (! Storage::disk(self::PRIVATE_DISK)->exists($path)) {
            $stream = Storage::disk(self::LEGACY_PUBLIC_DISK)->readStream($path);

            if (! is_resource($stream)) {
                return false;
            }

            try {
                if (! Storage::disk(self::PRIVATE_DISK)->writeStream($path, $stream)) {
                    return false;
                }
            } finally {
                fclose($stream);
            }
        }

        if (! Storage::disk(self::PRIVATE_DISK)->exists($path)) {
            return false;
        }

        return Storage::disk(self::LEGACY_PUBLIC_DISK)->delete($path);
    }

    public function legacyPublicExists(?string $storedPath): bool
    {
        $path = $this->normalizeRelativePath($storedPath);

        return $path !== null && Storage::disk(self::LEGACY_PUBLIC_DISK)->exists($path);
    }

    /** @param array{disk: string, path: string} $location */
    public function inlineResponse(array $location): StreamedResponse
    {
        $mimeType = Storage::disk($location['disk'])->mimeType($location['path']);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

        abort_unless(is_string($mimeType) && in_array($mimeType, $allowedMimeTypes, true), 404);

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        };

        return Storage::disk($location['disk'])->response(
            $location['path'],
            'employee-photo.'.$extension,
            [
                'Content-Type' => $mimeType,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
            ],
            'inline'
        );
    }

    public function normalizeRelativePath(?string $storedPath): ?string
    {
        if ($storedPath === null) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', trim($storedPath)), '/');

        if ($path === '' || str_contains($path, "\0") || preg_match('#(^|/)\.\.(/|$)#', $path)) {
            return null;
        }

        return $path;
    }
}
