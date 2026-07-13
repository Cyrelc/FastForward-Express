<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rewrites every ratesheet's weight brackets as pricing conditionals.
 *
 * A command rather than a migration, deliberately: it is idempotent (it REPLACES a sheet's weight
 * rules rather than adding to them), so it can be run against any database, at any time, as many
 * times as you like - including to rebuild after a rollback, or to pick up an edit to the brackets.
 *
 * Weight used to be charged by ChargeModelFactory::generatePackageCharges, which only ever applied a
 * bracket's flat basePrice. The incrementalPrice / additionalXLbs columns were read in exactly one
 * branch, reachable only ABOVE the top bracket - and that branch crashed (it array-indexed an
 * object). So a 999 lb parcel cost the same as a 100 lb one, and anything over the top bracket threw
 * a fatal instead of pricing.
 *
 * The rules written here do what the rate table was always authored to mean: a base price for the
 * bracket, plus the increment for every WHOLE step above the bracket floor. On St Albert that runs
 * 100-999 lb from $16 to $32, landing exactly on the next bracket's base - the giveaway that the
 * table was designed to be continuous. Prices go UP for parcels over 100 lb. That is intended.
 *
 * Two deliberate departures, both closing a hole rather than reproducing it:
 *   - The top bracket has no ceiling, so heavy freight keeps incrementing instead of crashing (or, on
 *     the pallet tables, being charged nothing at all).
 *   - A bracket whose increment has no step ("$1.50 per 0 lbs") cannot be honoured - it would divide
 *     by zero. Those are written as a flat base, which is what the engine did anyway, and reported so
 *     they can be corrected by hand.
 */
class RebuildWeightConditionals extends Command
{
    protected $signature = 'ratesheets:rebuild-weight-conditionals {--dry-run} {--ratesheet= : Limit to one ratesheet id}';
    protected $description = 'Rewrite ratesheet weight brackets as pricing conditionals (replaces any existing weight rules)';

    // The engine matched these two names exactly, ignoring every other table on the sheet - including
    // the "(copy)" duplicates one of them carries. Same here.
    private $rateTables = [
        'Basic Weight Rate' => false,
        'Pallet Weight Rate' => true,
    ];

    private $unhonourable = [];

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        $weightRateType = DB::table('selections')
            ->where('type', 'charge_type')
            ->where('value', 'weightRate')
            ->value('selection_id');

        if(!$weightRateType) {
            $this->error('No "weightRate" charge type in selections - cannot categorise the rules.');
            return 1;
        }

        $ratesheets = DB::table('ratesheets');
        if($this->option('ratesheet'))
            $ratesheets->where('ratesheet_id', $this->option('ratesheet'));

        foreach($ratesheets->get() as $ratesheet) {
            $weightRates = json_decode($ratesheet->weight_rates, true);
            if(!is_array($weightRates))
                continue;

            $this->line("<info>RS{$ratesheet->ratesheet_id}</info> {$ratesheet->name}");

            // Existing weight rules are replaced, never added to: two sets would stack, and every
            // parcel would be charged twice. This is also what makes the command safe to re-run.
            $existing = DB::table('conditionals')
                ->where('ratesheet_id', $ratesheet->ratesheet_id)
                ->where('type', $weightRateType);

            $replaced = $dryRun ? $existing->count() : $existing->delete();
            if($replaced)
                $this->line("  replacing {$replaced} existing weight rule(s)");

            // After the base price and any surcharges. Weight rules only add, so this affects the
            // order they are listed in, not the total.
            $priority = 200;

            foreach($this->rateTables as $tableName => $isPallet) {
                $index = array_search($tableName, array_column($weightRates, 'name'));
                if($index === false)
                    continue;

                $brackets = $weightRates[$index]['brackets'] ?? [];
                $bracketCount = count($brackets);

                foreach($brackets as $key => $bracket) {
                    $rule = $this->buildRule($ratesheet, $bracket, $brackets, $key, $bracketCount, $tableName, $isPallet, $priority);
                    if(!$rule)
                        continue;

                    $priority++;
                    $this->line("  <comment>{$rule['name']}</comment>: " . ($rule['original_equation_string'] ?? '$' . $rule['value']));

                    if(!$dryRun)
                        DB::table('conditionals')->insert(array_merge($rule, [
                            'type' => $weightRateType,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]));
                }
            }
        }

        if($this->unhonourable) {
            $this->newLine();
            $this->warn('These brackets set an increment price but no step, so the increment cannot be');
            $this->warn('applied (it would divide by zero). They were written as a flat base price, which');
            $this->warn('is what the old engine did. Fix them by hand:');
            foreach(array_unique($this->unhonourable) as $problem)
                $this->warn("  - {$problem}");
        }

        if($dryRun) {
            $this->newLine();
            $this->info('Dry run - nothing was written.');
        }

        return 0;
    }

    private function buildRule($ratesheet, $bracket, $brackets, $key, $bracketCount, $tableName, $isPallet, $priority)
    {
        $isFirst = $key === 0;
        $isLast = $key === $bracketCount - 1;

        // Where the bracket starts, and where the increment counts from. They differ by one: the table
        // reads "100 to 999 lb" so the charge steps up from 100, but the bracket has to begin the
        // instant the previous one ends (99) or a 99.5 lb parcel would belong to neither and be free.
        $lowerBound = $isFirst ? 0 : (float) ($brackets[$key - 1]['lbmax'] ?? 0);
        $offset = $isFirst ? 0 : $lowerBound + 1;
        $ceiling = (float) ($bracket['lbmax'] ?? 0);
        $base = (float) ($bracket['basePrice'] ?? 0);
        $step = (float) ($bracket['additionalXLbs'] ?? 0);
        $increment = (float) ($bracket['incrementalPrice'] ?? 0);

        $hasIncrement = $step > 0 && $increment > 0;

        if($increment > 0 && $step <= 0)
            $this->unhonourable[] = "RS{$ratesheet->ratesheet_id} {$tableName} <= {$this->trim($ceiling)} lb "
                . "(increment \${$this->trim($increment)} with no step)";

        // A flat bracket worth nothing is the same as no rule at all.
        if(!$hasIncrement && $base <= 0)
            return null;

        $name = ($isPallet ? 'Pallet Weight' : 'Basic Weight')
            . ($isLast
                ? ' ' . $this->trim($offset) . '+ lb'
                : ' ' . $this->trim($offset) . '-' . $this->trim($ceiling) . ' lb');

        return [
            'action' => json_encode(['value' => 'add', 'label' => 'Add Charge']),
            'continue_on' => true,
            'human_readable' => $this->describe($isPallet, $offset, $ceiling, $isLast, $base, $step, $increment, $hasIncrement),
            'json_logic' => json_encode($this->logicFor($isPallet, $lowerBound, $ceiling, $isLast, $isFirst)),
            'name' => $name,
            // max() guards the bracket's leading edge: a 99.5 lb parcel belongs to the 100-999 bracket,
            // but (99.5 - 100) is negative, and floor() would turn that into MINUS one increment -
            // charging less than the base price.
            'original_equation_string' => $hasIncrement
                ? "floor(max(0, total_weight_lbs - {$this->trim($offset)}) / {$this->trim($step)}) * {$this->trim($increment)} + {$this->trim($base)}"
                : null,
            'priority' => $priority,
            'ratesheet_id' => $ratesheet->ratesheet_id,
            'value' => $hasIncrement ? '0' : (string) $base,
            'value_type' => $hasIncrement ? 'equation' : 'amount',
        ];
    }

    /**
     * The weight charge never applied to a bill priced at the minimum, and still must not.
     */
    private function logicFor($isPallet, $lowerBound, $ceiling, $isLast, $isFirst)
    {
        $conditions = [
            ['==' => [['var' => 'package.is_minimum'], false]],
            ['==' => [['var' => 'package.is_pallet'], (bool) $isPallet]],
        ];

        if(!$isFirst)
            $conditions[] = ['>' => [['var' => 'package.total_weight_lbs'], $lowerBound]];

        if(!$isLast)
            $conditions[] = ['<=' => [['var' => 'package.total_weight_lbs'], $ceiling]];

        return ['and' => $conditions];
    }

    private function describe($isPallet, $offset, $ceiling, $isLast, $base, $step, $increment, $hasIncrement)
    {
        $what = $isPallet ? 'Pallets' : 'Packages';
        $range = $isLast
            ? "over {$this->trim($offset)} lb"
            : "between {$this->trim($offset)} and {$this->trim($ceiling)} lb";

        $charge = "\${$this->trim($base)}";
        if($hasIncrement)
            $charge .= " plus \${$this->trim($increment)} for every whole {$this->trim($step)} lb above {$this->trim($offset)}";

        return "{$what} {$range}: {$charge}";
    }

    /** 100.00 reads badly in a rule name and worse inside an equation. */
    private function trim($number)
    {
        return rtrim(rtrim(number_format((float) $number, 2, '.', ''), '0'), '.');
    }
}
