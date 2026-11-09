<?php

namespace App\\Console\\Commands;

use App\\Models\\Logbook;
use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\Storage;
use Illuminate\\Support\\Str;

class MigrateLogbookPhotosToPrivate extends Command
{
    protected $signature = 'nexora:migrate-logbook-photos-to-private {--dry-run : Report changes without modifying files}';

    protected $description = 'Move legacy public logbook photos into private application storage.';

    public function handle(): int
    {
        $private = Storage::disk('local');
        $public = Storage::disk('public');
        $dryRun = (bool) $this->option('dry-run');

        $migrated = 0;
        $alreadyPrivate = 0;
        $missing = 0;
        $failed = 0;

        foreach (Logbook::query()->whereNotNull('foto')->cursor() as $logbook) {
            $path = ltrim((string) $logbook->foto, '/');

            if (!Str::startsWith($path, 'logbooks/')) {
                $this->warn("Skipped logbook {$logbook->id}: unsupported photo path.");
                $failed++;
                continue;
            }

            $privateExists = $private->exists($path);
            $publicExists = $public->exists($path);

            if (!$privateExists && !$publicExists) {
                $this->warn("Missing photo for logbook {$logbook->id}: {$path}");
                $missing++;
                continue;
            }

            if ($dryRun) {
                if ($privateExists && $publicExists) {
                    $this->line("Would verify and remove public duplicate: {$path}");
                } elseif (!$privateExists && $publicExists) {
                    $this->line("Would migrate to private storage: {$path}");
                } else {
                    $this->line("Already private: {$path}");
                }
                continue;
            }

            if (!$privateExists) {
                $contents = $public->get($path);

                if (!$private->put($path, $contents)) {
                    $this->error("Could not copy photo into private storage: {$path}");
                    $failed++;
                    continue;
                }

                if (!$private->exists($path)) {
                    $this->error("Private copy could not be verified: {$path}");
                    $failed++;
                    continue;
                }
            }

            if ($publicExists) {
                $privateHash = hash('sha256', $private->get($path));
                $publicHash = hash('sha256', $public->get($path));

                if (!hash_equals($privateHash, $publicHash)) {
                    $this->error("Private/public photo mismatch; public copy was preserved: {$path}");
                    $failed++;
                    continue;
                }

                if (!$public->delete($path)) {
                    $this->error("Private copy exists, but public copy could not be removed: {$path}");
                    $failed++;
                    continue;
                }
            }

            if ($privateExists) {
                $alreadyPrivate++;
            } else {
                $migrated++;
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Migrated to private', $migrated],
                ['Already private', $alreadyPrivate],
                ['Missing source file', $missing],
                ['Failed / needs review', $failed],
            ],
        );

        if ($dryRun) {
            $this->info('Dry run complete. No files were changed.');
            return self::SUCCESS;
        }

        return ($missing > 0 || $failed > 0) ? self::FAILURE : self::SUCCESS;
    }
}
