<?php
namespace App\Http\Repos;

use App\Models\Conditional;
use App\Models\Ratesheet;

class ConditionalRepo {
    public function Delete($conditionalId) {
        $conditional = Conditional::where('conditional_id', $conditionalId)->first();

        $conditional->delete();

        return;
    }

    public function GetById($conditionalId) {
        $conditional = Conditional::where('conditional_id', $conditionalId);

        return $conditional->first();
    }

    /**
     * @function GetByRatesheetId
     * @param $ratesheetId - the ID of the relevant ratesheet
     * @param $types, an array of desired types, defaults to false
     */
    public function GetByRatesheetId($ratesheetId, $types = null) {
        $conditionals = Conditional::where('ratesheet_id', $ratesheetId);

        if($types)
            $conditionals->whereIn('value_type', $types);

        return $conditionals->get();
    }

    public function Insert($conditional) {
        $new = new Conditional;

        return ($new->create($conditional));
    }

    public function Update($conditional) {
        $old = Conditional::where('conditional_id', $conditional['conditional_id'])->first();

        $old->action = $conditional['action'];
        $old->equation_string = $conditional['equation_string'];
        $old->human_readable = $conditional['human_readable'];
        $old->json_logic = $conditional['json_logic'];
        $old->name = $conditional['name'];
        $old->original_equation_string = $conditional['original_equation_string'] ?? null;
        $old->value = $conditional['value'];
        $old->value_type = $conditional['value_type'];

        $old->save();
        return $old;
    }
}

?>
