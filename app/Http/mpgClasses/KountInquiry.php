<?php

namespace App\Http\mpgClasses;

class KountInquiry extends Transaction
{
	
	private $template = array (
		"kount_merchant_id" => null,
		"kount_api_key" => null,
		"order_id" => null,
		"call_center_ind" => null,
		"currency" => null,
		"email" => null,
		"data_key" => null,
		"customer_id" => null,
		"auto_number_id" => null,
		"financial_order_id" => null,
		"payment_token" => null,
		"payment_type" => null,
		"ip_address" => null,
		"session_id" => null,
		"website_id" => null,
		"amount" => null,
		"payment_response" => null,
		"avs_response" => null,
		"cvd_response" => null,
		"bill_street_1" => null,
		"bill_street_2" => null,
		"bill_country" => null,
		"bill_city" => null,
		"bill_postal_code" => null,
		"bill_phone" => null,
		"bill_province" => null,
		"dob" => null,
		"epoc" => null,
		"gender" => null,
		"last4" => null,
		"customer_name" => null,
		"ship_street_1" => null,
		"ship_street_2" => null,
		"ship_country" => null,
		"ship_city" => null,
		"ship_email" => null,
		"ship_name" => null,
		"ship_postal_code" => null,
		"ship_phone" => null,
		"ship_province" => null,
		"ship_type" => null,
		"products" => null,
		"udf" => null
	);

	private $products;
	private $udf;
	
	public function __construct()
	{
		$this->rootTag = "kount_inquiry";
		$this->data = $this->template;
	}
	
	public function setKountMerchantId($kount_merchant_id)
	{
		$this->data["kount_merchant_id"] = $kount_merchant_id;
	}
	
	public function setKountApiKey($kount_api_key)
	{
		$this->data["kount_api_key"] = $kount_api_key;
	}
	
	public function setOrderId($order_id)
	{
		$this->data["order_id"] = $order_id;
	}
	
	public function setCallCenterInd($call_center_ind)
	{
		$this->data["call_center_ind"] = $call_center_ind;
	}
	
	public function setCurrency($currency)
	{
		$this->data["currency"] = $currency;
	}
	
	public function setEmail($email)
	{
		$this->data["email"] = $email;
	}
	
	public function setDataKey($data_key)
	{
		$this->data["data_key"] = $data_key;
	}
	
	public function setCustomerId($customer_id)
	{
		$this->data["customer_id"] = $customer_id;
	}
	
	public function setAutoNumberId($auto_number_id)
	{
		$this->data["auto_number_id"] = $auto_number_id;
	}
	
	public function setFinancialOrderId($financial_order_id)
	{
		$this->data["financial_order_id"] = $financial_order_id;
	}
	
	public function setPaymentToken($payment_token)
	{
		$this->data["payment_token"] = $payment_token;
	}
	
	public function setPaymentType($payment_type)
	{
		$this->data["payment_type"] = $payment_type;
	}
	
	public function setIpAddress($ip_address)
	{
		$this->data["ip_address"] = $ip_address;
	}
	
	public function setSessionId($session_id)
	{
		$this->data["session_id"] = $session_id;
	}
	
	public function setWebsiteId($website_id)
	{
		$this->data["website_id"] = $website_id;
	}
	
	public function setAmount($amount)
	{
		$this->data["amount"] = $amount;
	}
	
	public function setPaymentResponse($payment_response)
	{
		$this->data["payment_response"] = $payment_response;
	}
	
	public function setAvsResponse($avs_response)
	{
		$this->data["avs_response"] = $avs_response;
	}
	
	public function setCvdResponse($cvd_response)
	{
		$this->data["cvd_response"] = $cvd_response;
	}
	
	public function setBillStreet1($bill_street_1)
	{
		$this->data["bill_street_1"] = $bill_street_1;
	}
	
	public function setBillStreet2($bill_street_2)
	{
		$this->data["bill_street_2"] = $bill_street_2;
	}
	
	public function setBillCountry($bill_country)
	{
		$this->data["bill_country"] = $bill_country;
	}
	
	public function setBillCity($bill_city)
	{
		$this->data["bill_city"] = $bill_city;
	}
	
	public function setBillPostalCode($bill_postal_code)
	{
		$this->data["bill_postal_code"] = $bill_postal_code;
	}
	
	public function setBillPhone($bill_phone)
	{
		$this->data["bill_phone"] = $bill_phone;
	}
	
	public function setBillProvince($bill_province)
	{
		$this->data["bill_province"] = $bill_province;
	}
	
	public function setDob($dob)
	{
		$this->data["dob"] = $dob;
	}
	
	public function setEpoc($epoc)
	{
		$this->data["epoc"] = $epoc;
	}
	
	public function setGender($gender)
	{
		$this->data["gender"] = $gender;
	}
	
	public function setLast4($last4)
	{
		$this->data["last4"] = $last4;
	}
	
	public function setCustomerName($customer_name)
	{
		$this->data["customer_name"] = $customer_name;
	}
	
	public function setShipStreet1($ship_street_1)
	{
		$this->data["ship_street_1"] = $ship_street_1;
	}
	
	public function setShipStreet2($ship_street_2)
	{
		$this->data["ship_street_2"] = $ship_street_2;
	}
	
	public function setShipCountry($ship_country)
	{
		$this->data["ship_country"] = $ship_country;
	}
	
	public function setShipCity($ship_city)
	{
		$this->data["ship_city"] = $ship_city;
	}
	
	public function setShipEmail($ship_email)
	{
		$this->data["ship_email"] = $ship_email;
	}
	
	public function setShipName($ship_name)
	{
		$this->data["ship_name"] = $ship_name;
	}
	
	public function setShipPostalCode($ship_postal_code)
	{
		$this->data["ship_postal_code"] = $ship_postal_code;
	}
	
	public function setShipPhone($ship_phone)
	{
		$this->data["ship_phone"] = $ship_phone;
	}
	
	public function setShipProvince($ship_province)
	{
		$this->data["ship_province"] = $ship_province;
	}
	
	public function setShipType($ship_type)
	{
		$this->data["ship_type"] = $ship_type;
	}
	
	public function setProduct($item_number, $product_type, $product_item, $product_desc, $product_quant, $product_price)
	{
		$this->data["prod_type_" . $item_number] = $product_type;
		$this->data["prod_item_" . $item_number] = $product_item;
		$this->data["prod_desc_" . $item_number] = $product_desc;
		$this->data["prod_quant_" . $item_number] = $product_quant;
		$this->data["prod_price_" . $item_number] = $product_price;
	}
	
	public function setUdfField($udf_attribute, $udf_attribute_value)
	{
		$this->udf[$udf_attribute] = $udf_attribute_value;
	}
	
	public function setUdf()
	{
		$this->data["udf"] = $this->udf;
	}
}

?>
