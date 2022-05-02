<?php

namespace App\Http\mpgClasses;

class GooglePayPurchase extends Transaction
{
	
	private $template = array (
		"order_id" => null,
		"amount" => null,
		"cust_id" => null,
		"network" => null,
		"payment_token" => null,
		"dynamic_descriptor" => null
	);
	
	public function __construct()
	{
		$this->rootTag = "googlepay_purchase";
		$this->data = $this->template;
	}
	
	public function setOrderId($order_id)
	{
		$this->data["order_id"] = $order_id;
	}
	
	public function setAmount($amount)
	{
		$this->data["amount"] = $amount;
	}
	
	public function setCustId($cust_id)
	{
		$this->data["cust_id"] = $cust_id;
	}
	
	public function setNetwork($network)
	{
		$this->data["network"] = $network;
	}
	
	public function setDynamicDescriptor($dynamicDescriptor)
	{
		$this->data["dynamic_descriptor"] = $dynamicDescriptor;
	}
	
	public function setPaymentToken($signature, $protocol_version, $signed_message)
	{
		
		$this->data["payment_token"] = array (
			"signature" => $signature,
			"protocol_version" => $protocol_version,
			"signed_message" => $signed_message
		);
	}
}

?>
