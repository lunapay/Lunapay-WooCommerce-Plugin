<?php

class LunaPayWooCommerceAPI
{
    private $connect;

    public function __construct($connect)
    {
        $this->connect = $connect;
    }

    public function setConnect($connect)
    {
        $this->connect = $connect;
    }

    public function getToken($tokenParameter){
        return $this->connect->getToken($tokenParameter);
    }
	
	public function sentPaymentSecure($token, $lunaSignature, $paymentParameter){
        return $this->connect->sentPaymentSecure($token, $lunaSignature, $paymentParameter);
    }

    public function sentPayment($token, $paymentParameter){
        return $this->connect->sentPayment($token, $paymentParameter);
    }

    public function getPaymentStatus($token, $payment_id){
        return $this->connect->getPaymentStatus($token, $payment_id);
    }
}
