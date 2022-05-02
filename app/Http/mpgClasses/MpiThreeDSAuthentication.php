<?php

namespace App\Http\mpgClasses;

class MpiThreeDSAuthentication extends Transaction {
	
	private $template = array (
		"order_id" => null,
		"data_key" => null,
		"cardholder_name" => null,
		"pan" => null,
		"expdate" => null,
		"amount" => null,
		"currency" => null,
		"threeds_completion_ind" => null,
		"request_type" => null,
		"notification_url" => null,
		"purchase_date" => null,
		"challenge_windowsize" => null,
		"bill_address1" => null,
		"bill_province" => null,
		"bill_city" => null,
		"bill_postal_code" => null,
		"bill_country" => null,
		"ship_address1" => null,
		"ship_province" => null,
		"ship_city" => null,
		"ship_postal_code" => null,
		"ship_country" => null,
		"browser_useragent" => null,
		"browser_java_enabled" => null,
		"browser_screen_height" => null,
		"browser_screen_width" => null,
		"browser_language" => null,
		"email" => null,
		"request_challenge" => null
	);
	
	public function __construct()
	{
		$this->is3Dsecure2Transaction = true; 
		$this->rootTag = "threeds_authentication";
		$this->data = $this->template;
	}
	
	public function setOrderId($order_id)
	{
		$this->data["order_id"] = $order_id;
	}
	
	public function setDataKey($data_key)
	{
		$this->data["data_key"] = $data_key;
	}
	
	public function setCardholderName($cardholder_name)
	{
		$this->data["cardholder_name"] = $cardholder_name;
	}
	
	public function setPan($pan)
	{
		$this->data["pan"] = $pan;
	}
	
	public function setExpdate($expdate)
	{
		$this->data["expdate"] = $expdate;
	}
	
	public function setAmount($amount)
	{
		$this->data["amount"] = $amount;
	}
	
	public function setCurrency($currency)
	{
		$this->data["currency"] = $currency;
	}
	
	public function setThreeDSCompletionInd($threeds_completion_ind)
	{
		$this->data["threeds_completion_ind"] = $threeds_completion_ind;
	}
	
	public function setRequestType($request_type)
	{
		$this->data["request_type"] = $request_type;
	}
	
	public function setNotificationURL($notification_url)
	{
		$this->data["notification_url"] = $notification_url;
	}
	
	public function setPurchaseDate($purchase_date)
	{
		$this->data["purchase_date"] = $purchase_date;
	}
	
	public function setChallengeWindowSize($challenge_windowsize)
	{
		$this->data["challenge_windowsize"] = $challenge_windowsize;
	}
	
	public function setBillAddress1($bill_address1)
	{
		$this->data["bill_address1"] = $bill_address1;
	}
	
	public function setBillProvince($bill_province)
	{
		$this->data["bill_province"] = $bill_province;
	}
	
	public function setBillCity($bill_city)
	{
		$this->data["bill_city"] = $bill_city;
	}
	
	public function setBillPostalCode($bill_postal_code)
	{
		$this->data["bill_postal_code"] = $bill_postal_code;
	}
	
	public function setBillCountry($bill_country)
	{
		$this->data["bill_country"] = $bill_country;
	}
	
	public function setShipAddress1($ship_address1)
	{
		$this->data["ship_address1"] = $ship_address1;
	}
	
	public function setShipProvince($ship_province)
	{
		$this->data["ship_province"] = $ship_province;
	}
	
	public function setShipCity($ship_city)
	{
		$this->data["ship_city"] = $ship_city;
	}
	
	public function setShipPostalCode($ship_postal_code)
	{
		$this->data["ship_postal_code"] = $ship_postal_code;
	}
	
	public function setShipCountry($ship_country)
	{
		$this->data["ship_country"] = $ship_country;
	}
	
	public function setBrowserUserAgent($browser_useragent)
	{
		$this->data["browser_useragent"] = $browser_useragent;
	}
	
	public function setBrowserJavaEnabled($browser_java_enabled)
	{
		$this->data["browser_java_enabled"] = $browser_java_enabled;
	}
	
	public function setBrowserScreenHeight($browser_screen_height)
	{
		$this->data["browser_screen_height"] = $browser_screen_height;
	}
	
	public function setBrowserScreenWidth($browser_screen_width)
	{
		$this->data["browser_screen_width"] = $browser_screen_width;
	}
	
	public function setBrowserLanguage($browser_language)
	{
		$this->data["browser_language"] = $browser_language;
	}
	
	public function setEmail($email)
	{
		$this->data["email"] = $email;
	}
	
	public function setRequestChallenge($request_challenge)
	{
		$this->data["request_challenge"] = $request_challenge;
	}
}

?>
