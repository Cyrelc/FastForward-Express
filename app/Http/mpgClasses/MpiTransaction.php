<?php

namespace App\Http\mpgClasses;

################# mpiTransaction ############################################

class MpiTransaction
{
	var $txn;

	public function __construct($txn)
	{
		$this->txn=$txn;
	}

	public function getTransaction()
	{
		return $this->txn;
	}
}//end class MpiTransaction

?>
