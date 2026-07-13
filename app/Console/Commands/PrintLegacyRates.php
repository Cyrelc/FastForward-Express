<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Prints the retired weight_rates and time_rates tables next to the conditionals that are supposed to
 * have replaced them, so you can check by eye that nothing was left behind.
 *
 * Both columns still hold their data - nothing dropped them - but nothing reads them for pricing any
 * more: weight moved to conditionals (see ratesheets:rebuild-weight-conditionals), and time rates had
 * already been dead in the engine for a long time (generateTimeCharges was commented out, so they
 * contributed $0 to every automatic quote; they survived only as a manual pick-list on the bills page).
 *
 * This command is the audit trail for that move. It reads only, and can be run on any database.
 */
class PrintLegacyRates extends Command
{
    protected $signature = 'ratesheets:print-legacy-rates {--ratesheet= : Limit to one ratesheet id}';
    protected $description = 'Print the old weight_rates and time_rates alongside the conditionals that replaced them';

    public function handle()
    {
        $ratesheets = DB::table('ratesheets')->orderBy('ratesheet_id');
        if($this->option('ratesheet'))
            $ratesheets->where('ratesheet_id', $this->option('ratesheet'));

        foreach($ratesheets->get() as $ratesheet) {
            $this->newLine();
            $this->line(str_repeat('=', 100));
            $this->line("<info>RATESHEET {$ratesheet->ratesheet_id}</info>  {$ratesheet->name}");
            $this->line(str_repeat('=', 100));

            $this->printWeight($ratesheet);
            $this->printTime($ratesheet);
        }

        $this->newLine();
        $this->comment('The OLD columns are still in the database, untouched. Nothing reads them for pricing.');
        $this->comment('Rebuild the weight rules any time with: php artisan ratesheets:rebuild-weight-conditionals');

        return 0;
    }

    private function printWeight($ratesheet)
    {
        $this->newLine();
        $this->line('<comment>--- OLD weight_rates (retired) ---</comment>');

        $weightRates = json_decode($ratesheet->weight_rates, true);

        if(!is_array($weightRates) || !$weightRates) {
            $this->line('  (none)');
        } else {
            foreach($weightRates as $rate) {
                $name = $rate['name'] ?? '(unnamed)';
                // Only these two were ever read by the old engine; anything else on the sheet - the
                // "(copy)" duplicates, for instance - was silently ignored, and still is.
                $used = in_array($name, ['Basic Weight Rate', 'Pallet Weight Rate']) ? '' : '   <fg=red>(IGNORED - name not recognised)</>';
                $this->line("  {$name}{$used}");

                $rows = [];
                foreach($rate['brackets'] ?? [] as $bracket) {
                    $rows[] = [
                        $this->num($bracket['lbmax'] ?? null),
                        '$' . $this->num($bracket['basePrice'] ?? 0),
                        $this->num($bracket['additionalXLbs'] ?? 0),
                        '$' . $this->num($bracket['incrementalPrice'] ?? 0),
                    ];
                }
                if($rows)
                    $this->table(['up to (lb)', 'base', 'per (lb)', 'increment'], $rows);
            }
        }

        $this->line('<comment>--- NOW: weight conditionals ---</comment>');
        $this->printConditionals($ratesheet->ratesheet_id, 'weightRate');
    }

    private function printTime($ratesheet)
    {
        $this->newLine();
        $this->line('<comment>--- OLD time_rates (retired; these were already charging $0) ---</comment>');

        $timeRates = json_decode($ratesheet->time_rates, true);

        if(!is_array($timeRates) || !$timeRates) {
            $this->line('  (none)');
        } else {
            $rows = [];
            foreach($timeRates as $rate) {
                foreach($rate['brackets'] ?? [] as $bracket) {
                    $rows[] = [
                        $rate['name'] ?? '(unnamed)',
                        '$' . $this->num($rate['price'] ?? 0),
                        $this->day($bracket['startDayOfWeek'] ?? null) . ' ' . $this->time($bracket['startTime'] ?? null),
                        $this->day($bracket['endDayOfWeek'] ?? null) . ' ' . $this->time($bracket['endTime'] ?? null),
                    ];
                }
            }
            if($rows)
                $this->table(['name', 'price', 'from', 'to'], $rows);
        }

        $this->line('<comment>--- NOW: time-of-day conditionals ---</comment>');
        $this->printConditionals($ratesheet->ratesheet_id, 'timeRate');
    }

    private function printConditionals($ratesheetId, $chargeTypeValue)
    {
        $chargeType = DB::table('selections')
            ->where('type', 'charge_type')
            ->where('value', $chargeTypeValue)
            ->value('selection_id');

        $conditionals = DB::table('conditionals')
            ->where('ratesheet_id', $ratesheetId)
            ->where('type', $chargeType)
            ->orderBy('priority')
            ->get();

        if($conditionals->isEmpty()) {
            $this->line('  <fg=red>(NONE — nothing replaced the rates above)</>');
            return;
        }

        $rows = [];
        foreach($conditionals as $conditional) {
            $rows[] = [
                $conditional->priority,
                $conditional->name,
                $conditional->original_equation_string ?: ('$' . $this->num($conditional->value)),
                $conditional->human_readable,
            ];
        }

        $this->table(['pri', 'name', 'charge', 'when'], $rows);
    }

    private function num($value)
    {
        if($value === null || $value === '')
            return '-';

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private function day($day)
    {
        if(is_array($day))
            $day = $day['label'] ?? null;

        return $day === null || $day === '' ? '?' : (string) $day;
    }

    private function time($time)
    {
        if(!$time)
            return '?';

        try {
            return (new \DateTime($time))->format('H:i');
        } catch (\Throwable $e) {
            return (string) $time;
        }
    }
}
