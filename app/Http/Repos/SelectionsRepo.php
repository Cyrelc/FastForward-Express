<?php
namespace App\Http\Repos;

use App\Selection;

class SelectionsRepo {
	public function GetSelectionsByType($type) {
		$selections = Selection::where('type', $type);

		return $selections->get();
	}
}

?>
