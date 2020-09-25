<?php
namespace App\Http\Repos;

use App\Selection;

class SelectionsRepo {
	public function GetSelectionsByType($type) {
		$selections = Selection::where('type', $type);

		return $selections->get();
	}

	public function GetSelectionsListByType($type) {
		$selections = Selection::where('type', $type)
			->select(
				'name as label',
				'selection_id as value'
			);

		return $selections->get();
	}
}

?>
