<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class WorkerHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'worker:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive worker health check and auto-recovery';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $scriptPath = base_path('tools/check-worker-health.sh');
        
        if (!file_exists($scriptPath)) {
            $this->error("Health check script not found: {$scriptPath}");
            return Command::FAILURE;
        }
        
        $this->info('Running worker health check...');
        
        // Execute the bash script
        $output = [];
        $exitCode = 0;
        exec("bash {$scriptPath} 2>&1", $output, $exitCode);
        
        // Display output
        foreach ($output as $line) {
            if (str_contains($line, '✓')) {
                $this->info($line);
            } elseif (str_contains($line, '✗')) {
                $this->error($line);
            } else {
                $this->line($line);
            }
        }
        
        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
