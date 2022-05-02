<?php

namespace App\Http\mpgClasses;

###################### Transaction #########################################
class Transaction 
{
	protected $data;
	protected $rootTag;
	protected $is3Dsecure2Transaction = false;
	
	public function __construct()
	{
		
	}
	
	public function getTransactionType()
	{
		return $this->rootTag;
	}
	
	public function getIs3DSecure2Transaction()
	{
		return $this->is3Dsecure2Transaction;	
	}
	
	public function toXML()
	{		
		$xmlString = "<" . $this->rootTag . ">";
		$xmlString .= $this->toXML_low($this->data, $this->rootTag);
		$xmlString .= "</" . $this->rootTag . ">";
		
		return $xmlString;
	}
	
	private function toXML_low($dataArray, $root)
	{
		$xmlRoot = "";
		
		foreach ($dataArray as $key => $value)
		{
			if(!is_numeric($key) && $value != "" && $value != null)
			{
				$xmlRoot .= "<$key>";
			}
			else if(is_numeric($key) && $key != "0")
			{
				$xmlRoot .= "</$root><$root>";
			}

			if(is_array($value))
			{
				$xmlRoot .= $this->toXML_low($value, $key);
			}
			else
			{
				$xmlRoot .= $value;
			}
			
			if(!is_numeric($key) && $value != "" && $value != null)
			{
				$xmlRoot .= "</$key>";
			}
		}
		
		return $xmlRoot;
	}
}

?>
