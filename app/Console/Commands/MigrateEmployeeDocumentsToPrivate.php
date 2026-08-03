<?php

namespace App\Console\Commands;

use App\Models\EmployeeDocument;
use App\Services\EmployeeDocumentStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateEmployeeDocumentsToPrivate extends Command
{
    protected $signature = 'employee-documents:migrate-private {--dry-run : Report changes without moving files}';

    protected $description = 'Move legacy employee documents from public storage to private storage';

    public function handle(EmployeeDocumentStorageService $storage): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = [
            'total' => 0,
            'would_move' => 0,
            'moved' => 0,
            'already_private' => 0,
            'missing' => 0,
            'failed' => 0,
        ];

        $this->info($dryRun ? 'Mode: DRY RUN' : 'Mode: MIGRATE');

        foreach (EmployeeDocument::query()->orderBy('id')->cursor() as $document) {
            $counts['total']++;
            $path = $storage->normalizeRelativePath($document->file_path);

            if ($path === null) {
                $counts['failed']++;
                $this->error("#{$document->id} invalid path");

                continue;
            }

            if (Storage::disk(EmployeeDocumentStorageService::PRIVATE_DISK)->exists($path)) {
                $counts['already_private']++;
                $this->line("#{$document->id} already private: {$path}");

                continue;
            }

            if (! Storage::disk(EmployeeDocumentStorageService::LEGACY_PUBLIC_DISK)->exists($path)) {
                $counts['missing']++;
                $this->warn("#{$document->id} missing: {$path}");

                continue;
            }

            if ($dryRun) {
                $counts['would_move']++;
                $this->line("#{$document->id} would move: {$path}");

                continue;
            }

            try {
                $this->moveAndVerify($path);
                $counts['moved']++;
                $this->info("#{$document->id} moved: {$path}");
            } catch (Throwable $exception) {
                $counts['failed']++;
                $this->error("#{$document->id} failed: {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->line('Total: '.$counts['total']);
        $this->line('Would move: '.$counts['would_move']);
        $this->line('Moved: '.$counts['moved']);
        $this->line('Already private: '.$counts['already_private']);
        $this->line('Missing: '.$counts['missing']);
        $this->line('Failed: '.$counts['failed']);

        return self::SUCCESS;
    }

    private function moveAndVerify(string $path): void
    {
        $public = Storage::disk(EmployeeDocumentStorageService::LEGACY_PUBLIC_DISK);
        $private = Storage::disk(EmployeeDocumentStorageService::PRIVATE_DISK);
        $sourceSize = $public->size($path);
        $stream = $public->readStream($path);

        if (! is_resource($stream)) {
            throw new \RuntimeException('Tidak dapat membaca file public.');
        }

        try {
            $written = $private->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }

        if (! $written || ! $private->exists($path)) {
            $private->delete($path);

            throw new \RuntimeException('Tidak dapat menulis file private.');
        }

        if ($private->size($path) !== $sourceSize) {
            $private->delete($path);

            throw new \RuntimeException('Ukuran file hasil salinan tidak sesuai.');
        }

        if (! $public->delete($path)) {
            $private->delete($path);

            throw new \RuntimeException('File public tidak dapat dihapus setelah verifikasi.');
        }
    }
}
