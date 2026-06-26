<?php
namespace App\Http\Repos;

use App\Models\TimingConditional;

class TimingConditionalRepo {
    public function Delete($timingConditionalId) {
        $timingConditional = TimingConditional::where('timing_conditional_id', $timingConditionalId)->first();

        $timingConditional->delete();

        return;
    }

    public function GetById($timingConditionalId) {
        $timingConditional = TimingConditional::where('timing_conditional_id', $timingConditionalId);

        return $timingConditional->first();
    }

    public function GetByRatesheetId($ratesheetId) {
        return TimingConditional::where('ratesheet_id', $ratesheetId)->get();
    }

    public function GetByRatesheetIdAndName($ratesheetId, $name) {
        return TimingConditional::where('ratesheet_id', $ratesheetId)
            ->where('name', $name)
            ->first();
    }

    public function Insert($timingConditional) {
        $new = new TimingConditional;

        return ($new->create($timingConditional));
    }

    public function Update($timingConditional) {
        $old = TimingConditional::where('timing_conditional_id', $timingConditional['timing_conditional_id'])->first();

        $old->action = $timingConditional['action'];
        $old->continue_on = $timingConditional['continue_on'];
        $old->human_readable = $timingConditional['human_readable'];
        $old->json_logic = $timingConditional['json_logic'];
        $old->name = $timingConditional['name'];
        $old->priority = $timingConditional['priority'];
        $old->ratesheet_id = $timingConditional['ratesheet_id'];
        $old->value = $timingConditional['value'];
        $old->value_type = $timingConditional['value_type'];

        $old->save();
        return $old;
    }
}

?>
