<?php

namespace App\Http\mpgClasses;

class ApplePayTokenPurchase extends Transaction
{
	
	private $template = array (
		"order_id" => null,
		"cust_id" => null,
		"amount" => null,
		"displayName" => null,
		"network" => null,
		"version" => null,
		"data" => null,
		"signature" => null,
		"header" => null,
		"type" => null,
		"dynamic_descriptor" => null,
		"token_originator" => null
	);
	
	public function __construct()
	{
		$this->rootTag = "applepay_token_purchase";
		$this->data = $this->template;
	}
	
	public function setOrderId($order_id)
	{
		$this->data["order_id"] = $order_id;
	}
	
	public function setCustId($cust_id)
	{
		$this->data["cust_id"] = $cust_id;
	}
	
	public function setAmount($amount)
	{
		$this->data["amount"] = $amount;
	}
	
	public function setDisplayName($display_name)
	{
		$this->data["displayName"] = $display_name;
	}
	
	public function setNetwork($network)
	{
		$this->data["network"] = $network;
	}
	
	public function setVersion($version)
	{
		$this->data["version"] = $version;
	}
	
	public function setData($data)
	{
		$this->data["data"] = $data;
	}
	
	public function setSignature($signature)
	{
		$this->data["signature"] = $signature;
	}
	
	public function setHeader($public_key_hash, $ephemeral_public_key, $transaction_id)
	{
		
		$this->data["header"] = array(
			"public_key_hash" => $public_key_hash,
			"ephemeral_public_key" => $ephemeral_public_key,
			"transaction_id" => $transaction_id
		);
	}
	
	public function setType($type)
	{
		$this->data["type"] = $type;
	}
	
	public function setDynamicDescriptor($dynamic_descriptor)
	{
		$this->data["dynamic_descriptor"] = $dynamic_descriptor;
	}
	
	public function setTokenOriginator($store_id, $api_token)
	{
		$this->data["token_originator"] = array (
			"store_id" => $store_id,
			"api_token" => $api_token
		);
	}

}

?>
