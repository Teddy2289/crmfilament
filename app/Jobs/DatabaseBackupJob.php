<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $backupDir = '/home/mbl';

        if (! is_dir($backupDir) && ! mkdir($backupDir, 0777, true) && ! is_dir($backupDir)) {
            throw new \RuntimeException("Impossible de créer le dossier de backup {$backupDir}.");
        }

        $connection = config('database.default');
        $connectionConfig = config("database.connections.{$connection}", []);
        $databaseName = (string) ($connectionConfig['database'] ?? env('DB_DATABASE'));
        $timestamp = now('Etc/GMT-3')->format('Ymd_His');
        $backupFile = sprintf('%s_%s.sql', preg_replace('/[^A-Za-z0-9_.-]+/', '_', $databaseName), $timestamp);
        $backupPath = $backupDir . '/' . $backupFile;

        $command = match ($connection) {
            'pgsql' => sprintf(
                "PGPASSWORD=%s pg_dump -h %s -p %s -U %s -d %s > %s",
                escapeshellarg($connectionConfig['password'] ?? ''),
                escapeshellarg($connectionConfig['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($connectionConfig['port'] ?? 5432)),
                escapeshellarg($connectionConfig['username'] ?? ''),
                escapeshellarg($databaseName),
                escapeshellarg($backupPath),
            ),
            'sqlite' => sprintf(
                "cp %s %s",
                escapeshellarg($connectionConfig['database'] ?? database_path('database.sqlite')),
                escapeshellarg($backupPath),
            ),
            default => sprintf(
                "mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s",
                escapeshellarg($connectionConfig['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($connectionConfig['port'] ?? 3306)),
                escapeshellarg($connectionConfig['username'] ?? ''),
                escapeshellarg($connectionConfig['password'] ?? ''),
                escapeshellarg($databaseName),
                escapeshellarg($backupPath),
            ),
        };

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Backup base de données : échec', [
                'connection' => $connection,
                'output' => $process->getErrorOutput(),
                'command' => $command,
            ]);

            throw new \RuntimeException('Le backup de la base de données a échoué : ' . $process->getErrorOutput());
        }

        Log::info('Backup base de données réalisé', [
            'connection' => $connection,
            'path' => $backupPath,
        ]);
    }
}
