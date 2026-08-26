<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--disk=local}';
    protected $description = 'Create a SQL dump of the database';

    public function handle(): int
    {
        $disk = $this->option('disk');
        $filename = 'backup-'.now()->format('Y-m-d_His').'.sql';
        $path = 'backups/'.$filename;
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            $dbPath = config("database.connections.{$connection}.database");
            Storage::disk($disk)->put($path, file_get_contents($dbPath));
        } elseif ($driver === 'mysql') {
            $host = escapeshellarg(config("database.connections.{$connection}.host"));
            $port = escapeshellarg(config("database.connections.{$connection}.port"));
            $db = escapeshellarg(config("database.connections.{$connection}.database"));
            $user = escapeshellarg(config("database.connections.{$connection}.username"));
            $pass = escapeshellarg(config("database.connections.{$connection}.password"));
            $dump = shell_exec("mysqldump -h{$host} -P{$port} -u{$user} -p{$pass} {$db}");
            Storage::disk($disk)->put($path, $dump);
        } else {
            $this->error("Backup not supported for driver: {$driver}");
            return self::FAILURE;
        }

        $this->info("Backup created: {$path}");

        // Keep last 7 backups
        $backups = collect(Storage::disk($disk)->files('backups'))->sort()->reverse()->values();
        $backups->slice(7)->each(function ($old) use ($disk) {
            Storage::disk($disk)->delete($old);
        });

        return self::SUCCESS;
    }
}