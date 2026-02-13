<?php

namespace App\Console\Commands;

use App\Models\Ratesheet;
use DateTimeImmutable;
use Illuminate\Console\Command;

class NormalizeRatesheetTimeRates extends Command
{
    protected $signature = 'ratesheets:normalize-time-rates {--dry-run}';
    protected $description = 'Normalize ratesheet time_rates bracket times to ISO-8601 strings';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;
        $updated = 0;
        $converted = 0;
        $failed = 0;

        Ratesheet::select('ratesheet_id', 'time_rates')->orderBy('ratesheet_id')->chunk(100, function ($ratesheets) use ($dryRun, &$total, &$updated, &$converted, &$failed) {
            foreach ($ratesheets as $ratesheet) {
                $total++;
                if (!$ratesheet->time_rates) {
                    continue;
                }

                $timeRates = json_decode($ratesheet->time_rates, true);
                if (!is_array($timeRates)) {
                    $failed++;
                    continue;
                }

                $changed = false;
                foreach ($timeRates as &$timeRate) {
                    if (!isset($timeRate['brackets']) || !is_array($timeRate['brackets'])) {
                        continue;
                    }

                    foreach ($timeRate['brackets'] as &$bracket) {
                        if (isset($bracket['startTime'])) {
                            $normalized = $this->normalizeTimeValue($bracket['startTime'], $failed);
                            if ($normalized !== $bracket['startTime']) {
                                $bracket['startTime'] = $normalized;
                                $changed = true;
                                $converted++;
                            }
                        }

                        if (isset($bracket['endTime'])) {
                            $normalized = $this->normalizeTimeValue($bracket['endTime'], $failed);
                            if ($normalized !== $bracket['endTime']) {
                                $bracket['endTime'] = $normalized;
                                $changed = true;
                                $converted++;
                            }
                        }
                    }
                }
                unset($timeRate, $bracket);

                if ($changed) {
                    $updated++;
                    if (!$dryRun) {
                        $ratesheet->time_rates = json_encode($timeRates);
                        $ratesheet->save();
                    }
                }
            }
        });

        $this->info("Processed: {$total}");
        $this->info("Updated: {$updated}");
        $this->info("Converted time fields: {$converted}");
        $this->info("Failed to parse: {$failed}");

        if ($dryRun) {
            $this->warn('Dry run only. No changes were saved.');
        }

        return Command::SUCCESS;
    }

    private function normalizeTimeValue($value, &$failed)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }

        $withoutTzName = preg_replace('/\s\([^\)]*\)$/', '', $trimmed);

        $date = $this->tryParseDate($withoutTzName);
        if (!$date && strpos($withoutTzName, 'GMT') !== false) {
            $date = DateTimeImmutable::createFromFormat('D M d Y H:i:s \G\M\T O', $withoutTzName) ?: null;
        }

        if (!$date) {
            $failed++;
            return $value;
        }

        return $date->format(DATE_ATOM);
    }

    private function tryParseDate($value)
    {
        try {
            return new DateTimeImmutable($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
