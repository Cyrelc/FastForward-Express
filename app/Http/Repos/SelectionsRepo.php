<?php
namespace App\Http\Repos;

use App\Selection;

class SelectionsRepo {
	public function GetSelectionsByType($type) {
		$selections = Selection::where('type', $type);

		return $selections->get();
	}

	public function GetSelectionByTypeAndValue($type, $value) {
		$selection = Selection::where('type', $type)
			->where('value', $value);

		return $selection->first();
	}

	public function GetSelectionsListByType($type) {
		$selections = Selection::where('type', $type)
			->select(
				'name as label',
				'selection_id as value'
			);

		return $selections->get();
	}

	public function Insert($selection) {
		$new = new Selection;

		return $new->create($selection);
	}

	public function List() {
		$selections = Selection::all();

		return $selections;
	}
}

?>
