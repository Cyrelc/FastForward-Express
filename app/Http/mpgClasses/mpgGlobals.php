<?php

namespace App\Http\mpgClasses;

#################### mpgGlobals #############################################

class mpgGlobals
{
	var $Globals=array(
        	        'MONERIS_PROTOCOL' => 'https',
					'MONERIS_HOST' => 'mpg1.moneris.io', //default
					'MONERIS_TEST_HOST' => 'mpg1t.moneris.io',
					'MONERIS_US_HOST' => 'esplus.moneris.com',
					'MONERIS_US_TEST_HOST' => 'esplusqa.moneris.com',
        	        'MONERIS_PORT' =>'443',
					'MONERIS_FILE' => '/gateway2/servlet/MpgRequest',
					'MONERIS_US_FILE' => '/gateway_us/servlet/MpgRequest',
					'MONERIS_MPI_FILE' => '/mpi/servlet/MpiServlet',
					'MONERIS_MPI_2_FILE' => '/mpi2/servlet/MpiServlet',
					'MONERIS_US_MPI_FILE' => '/mpi/servlet/MpiServlet',
                  	'API_VERSION'  => 'PHP NA - 1.0.22',
					'CONNECT_TIMEOUT' => '20',
                  	'CLIENT_TIMEOUT' => '35'
                 	);

 	public function __construct()
 	{
 		// default
 	}

 	public function getGlobals()
 	{
  		return($this->Globals);
 	}

}//end class mpgGlobals

?>
