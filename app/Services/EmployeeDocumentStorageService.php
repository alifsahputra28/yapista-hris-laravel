<?php

namespace App\Services;

use App\Models\EmployeeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentStorageService
{
    public const PRIVATE_DISK = 'private';

    public const LEGACY_PUBLIC_DISK = 'public';

    /**
     * @return array{disk: string, path: string}|null
     */
    public function locate(EmployeeDocument $document): ?array
    {
        $path = $this->normalizeRelativePath($document->file_path);

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

    public function store(UploadedFile $file, int $employeeId): string
    {
        $path = $file->store("employee-documents/{$employeeId}", self::PRIVATE_DISK);

        if (! is_string($path) || $path === '' || ! Storage::disk(self::PRIVATE_DISK)->exists($path)) {
            throw new RuntimeException('Dokumen gagal disimpan ke private storage.');
        }

        return $path;
    }

    public function deletePrivatePath(?string $path): void
    {
        $path = $this->normalizeRelativePath($path);

        if ($path !== null && Storage::disk(self::PRIVATE_DISK)->exists($path)) {
            Storage::disk(self::PRIVATE_DISK)->delete($path);
        }
    }

    public function deletePath(?string $path): void
    {
        $path = $this->normalizeRelativePath($path);

        if ($path === null) {
            return;
        }

        foreach ([self::PRIVATE_DISK, self::LEGACY_PUBLIC_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    /**
     * @param  array{disk: string, path: string}  $location
     */
    public function inlineResponse(EmployeeDocument $document, array $location): StreamedResponse
    {
        $mimeType = $this->safeMimeType($document, $location);

        if (! in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return $this->downloadResponse($document, $location);
        }

        return Storage::disk($location['disk'])->response(
            $location['path'],
            $this->safeFilename($document),
            $this->securityHeaders($mimeType),
            'inline'
        );
    }

    /**
     * @param  array{disk: string, path: string}  $location
     */
    public function downloadResponse(EmployeeDocument $document, array $location): StreamedResponse
    {
        return Storage::disk($location['disk'])->download(
            $location['path'],
            $this->safeFilename($document),
            $this->securityHeaders($this->safeMimeType($document, $location))
        );
    }

    public function normalizeRelativePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        if ($path === '' || str_contains($path, "\0") || preg_match('#(^|/)\.\.(/|$)#', $path)) {
            return null;
        }

        return $path;
    }

    /**
     * @param  array{disk: string, path: string}  $location
     */
    private function safeMimeType(EmployeeDocument $document, array $location): string
    {
        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        $detected = Storage::disk($location['disk'])->mimeType($location['path']);

        if (is_string($detected) && in_array($detected, $allowed, true)) {
            return $detected;
        }

        if (is_string($document->mime_type) && in_array($document->mime_type, $allowed, true)) {
            return $document->mime_type;
        }

        return 'application/octet-stream';
    }

    private function safeFilename(EmployeeDocument $document): string
    {
        $name = basename(str_replace('\\', '/', (string) $document->original_name));
        $name = preg_replace('/[\x00-\x1F\x7F"\\\\]/u', '_', $name) ?? '';
        $name = trim($name, " .\t\n\r\0\x0B");

        return $name !== '' ? $name : 'dokumen-'.$document->document_type;
    }

    /**
     * @return array<string, string>
     */
    private function securityHeaders(string $mimeType): array
    {
        return [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Frame-Options' => 'SAMEORIGIN',
        ];
    }
}
