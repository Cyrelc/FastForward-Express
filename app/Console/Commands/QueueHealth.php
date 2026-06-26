<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print the age in seconds of the oldest pending (unreserved) queue job, or 0 if the queue is empty. Replaces the tinker-based probe used by the health-check script (tinker is unusable in production).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Oldest job that is waiting to be picked up (not currently reserved by a
        // worker) and already available. If this keeps growing, workers are stuck
        // or dead. created_at / available_at are stored as unix timestamps.
        $oldest = DB::table('jobs')
            ->whereNull('reserved_at')
            ->where('available_at', '<=', time())
            ->orderBy('created_at', 'asc')
            ->first();

        $age = $oldest ? max(0, time() - (int) $oldest->created_at) : 0;

        $this->line((string) $age);

        return Command::SUCCESS;
    }
}
