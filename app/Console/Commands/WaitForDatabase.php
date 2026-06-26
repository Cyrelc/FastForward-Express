<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WaitForDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:wait
                            {--sleep=3 : Seconds to wait between connection attempts}
                            {--timeout=0 : Give up after this many seconds (0 = wait forever)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Block until the database accepts connections. Used by the worker launcher so a transient DB outage cannot drive Supervisor to FATAL.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sleep = max(1, (int) $this->option('sleep'));
        $timeout = (int) $this->option('timeout');
        $start = time();
        $attempts = 0;

        while (true) {
            try {
                // Purge any previously-resolved (and possibly dead) connection so
                // DB::connection() rebuilds a fresh one and getPdo() actually
                // attempts to connect rather than returning a stale handle.
                DB::purge();
                DB::connection()->getPdo();

                // Reachable. If we had to wait, the DB was down — record recovery
                // instrumentation so the next month-end outage is self-diagnosing
                // without needing server-side MySQL logs.
                if ($attempts > 0) {
                    $this->logRecovery($start, $attempts);
                }

                $this->info('Database is reachable.');

                return Command::SUCCESS;
            } catch (\Throwable $e) {
                $attempts++;

                if ($timeout > 0 && (time() - $start) >= $timeout) {
                    $this->error("Database still unreachable after {$timeout}s, giving up.");

                    return Command::FAILURE;
                }

                $this->warn("Database unreachable (attempt {$attempts}): {$e->getMessage()}. Retrying in {$sleep}s...");
                sleep($sleep);
            }
        }
    }

    /**
     * Log a snapshot of connection counters once the DB comes back, so we can
     * tell whether outages are connection-exhaustion vs. a crash/restart.
     */
    private function logRecovery(int $start, int $attempts): void
    {
        $waitedSeconds = time() - $start;
        $threadsConnected = null;
        $maxConnections = null;

        try {
            $threadsConnected = optional(DB::selectOne("SHOW STATUS LIKE 'Threads_connected'"))->Value;
            $maxConnections = optional(DB::selectOne("SHOW VARIABLES LIKE 'max_connections'"))->Value;
        } catch (\Throwable $e) {
            // Counters are best-effort; never let instrumentation block startup.
        }

        $properties = [
            'waited_seconds' => $waitedSeconds,
            'attempts' => $attempts,
            'threads_connected' => $threadsConnected,
            'max_connections' => $maxConnections,
        ];

        activity('worker_maintenance')
            ->withProperties($properties)
            ->log("Database recovered after {$waitedSeconds}s ({$attempts} attempts) before worker start");
    }
}
