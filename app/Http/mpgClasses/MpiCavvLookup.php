<?php

namespace App\Http\mpgClasses;

class MpiCavvLookup extends Transaction {
	
	private $template = array (
		"cres" => null
	);
	
	public function __construct()
	{
		$this->is3Dsecure2Transaction = true;
		$this->rootTag = "cavv_lookup";
		$this->data = $this->template;
	}
	
	public function setCRes($cres)
	{
		$this->data["cres"] = $cres;
	}
}

?>
