<?php
namespace App\Http\Repos;

use App\Conditional;
use App\Ratesheet;

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

    public function GetByRatesheetId($ratesheetId) {
        $conditionals = Conditional::where('ratesheet_id', $ratesheetId);

        return $conditionals->get();
    }

    public function Insert($conditional) {
        $new = new Conditional;

        return ($new->create($conditional));
    }

    public function Update($conditional) {
        $old = Conditional::where('conditional_id', $conditional['conditional_id'])->first();

        $old->action = $conditional['action'];
        $old->human_readable = $conditional['human_readable'];
        $old->json_logic = $conditional['json_logic'];
        $old->name = $conditional['name'];
        $old->value = $conditional['value'];
        $old->value_type = $conditional['value_type'];

        $old->save();
        return $old;
    }
}

?>
