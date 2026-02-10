<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class StoreWorkerHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:health-store 
                            {--status=unknown : Worker status (up, down, recovered, error, unknown)}
                            {--message= : Optional status message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store worker health status in cache for dashboard display';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $status = $this->option('status');
        $message = $this->option('message');
        
        $healthData = [
            'status' => $status,
            'message' => $message,
            'checked_at' => now()->timestamp,
            'checked_at_human' => now()->toDateTimeString(),
        ];
        
        // Store with 15-minute TTL (should be updated every 10 minutes by cron)
        Cache::put('worker:health:status', $healthData, now()->addMinutes(15));
        
        // Also log to activity log for historical tracking
        activity('worker_health')
            ->withProperties($healthData)
            ->log($message ?? "Worker status: {$status}");
        
        $this->info("Worker health stored: {$status}");
        
        return Command::SUCCESS;
    }
}
