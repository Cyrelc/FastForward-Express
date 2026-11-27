<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Links deductions taken before source_chargeback_id existed back to the chargeback they came from, where that can
 * be told reliably, so their manifests can be deleted (which hands each deduction back to its source).
 *
 * A deduction is a copy of its source (same employee and name) carrying a manifest_id. In order of trust:
 *  1. Activity log: the copy's "created" entry and the source's count_remaining dropping by one were logged within
 *     a minute of each other. This is a record of what actually happened, so it wins.
 *  2. Same employee, name and amount, with the latest start date on or before the manifest's end date. Recurring
 *     chargebacks are often re-created each period with the same name, and amounts change between periods.
 * Only a single clear match is used. Anything else stays NULL, and ManifestRepo::delete still refuses those
 * manifests with instructions for undoing the deductions by hand.
 */
return new class extends Migration {
    public function up() {
        $byActivityLog = collect(DB::select("
            select d.chargeback_id as deduction_id, min(s.chargeback_id) as source_id
            from chargebacks d
            join activity_log c on c.subject_type = ? and c.subject_id = d.chargeback_id and c.description = 'created'
            join chargebacks s on s.manifest_id is null and s.employee_id = d.employee_id and s.name = d.name
            join activity_log u on u.subject_type = ? and u.subject_id = s.chargeback_id and u.description = 'updated'
                and abs(timestampdiff(second, u.created_at, c.created_at)) <= 60
                and json_extract(u.properties, '$.old.count_remaining') = json_extract(u.properties, '$.attributes.count_remaining') + 1
            where d.manifest_id is not null and d.source_chargeback_id is null
            group by d.chargeback_id
            having count(distinct s.chargeback_id) = 1
        ", [App\Models\Chargeback::class, App\Models\Chargeback::class]))->pluck('source_id', 'deduction_id');

        $byAmountAndDate = collect(DB::select("
            with candidates as (
                select d.chargeback_id as deduction_id, s.chargeback_id as source_id,
                    rank() over (partition by d.chargeback_id order by coalesce(s.start_date, '1000-01-01') desc) as recency
                from chargebacks d
                join manifests m on m.manifest_id = d.manifest_id
                join chargebacks s on s.manifest_id is null and s.employee_id = d.employee_id and s.name = d.name
                    and s.amount = d.amount and (s.start_date is null or s.start_date <= m.end_date)
                where d.manifest_id is not null and d.source_chargeback_id is null
            )
            select deduction_id, min(source_id) as source_id from candidates where recency = 1
            group by deduction_id having count(*) = 1
        "))->pluck('source_id', 'deduction_id');

        // + keeps the activity log's match where both found one
        foreach($byActivityLog->all() + $byAmountAndDate->all() as $deductionId => $sourceId)
            DB::table('chargebacks')->where('chargeback_id', $deductionId)->whereNull('source_chargeback_id')
                ->update(['source_chargeback_id' => $sourceId]);
    }

    public function down() {
        // Not reversible: backfilled links can't be told apart from ones saved by a manifest run
    }
};
