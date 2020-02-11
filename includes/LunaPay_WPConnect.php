<?php

class LunaPayWooCommerceWPConnect
{

    private $x_signature_key;
    private $client_id;
	private $client_secret;
	private $luna_signature;

    private $process; //cURL or GuzzleHttp
    public $is_staging;
    public $detect_mode;
    public $url;
    public $webhook_rank;

    public $header;

    const TIMEOUT = 10; //10 Seconds

    public function __construct($secret_cod, $current_url)
    {
//         $this->secret_code = $secret_code;
		$this->client_secret = $client_secret;
		$this->luna_signature = $luna_signature;
		

        $this->header = array(
            'Authorization' => 'Basic ' . base64_encode($this->client_secret . ':')
        );

        $this->current_url = $current_url;
		


    }

    public function getToken($tokenParameter){

        $url = $this->current_url.'/oauth/token';

        $data = array(
                'grant_type' => 'client_credentials',
                'client_id' => $tokenParameter['client_id'],
                'client_secret' => $tokenParameter['client_secret']
            );

        $wp_remote_data['body'] = http_build_query($data);
        $wp_remote_data['method'] = 'POST';
        $response = \wp_remote_post($url, $wp_remote_data);
        $body = \wp_remote_retrieve_body($response);
        $newbody = json_decode($body);

        $token = $newbody->access_token;

        return $token;
    }
	
	public function sentPaymentSecure($token, $paymentParameter, $lunaSignature){
		
		
 		$url = $this->current_url.'/api/payments/payment/secure';
		
		  $string = 'amount'.$paymentParameter['amount']
          .'|email'.$paymentParameter['email']
          .'|item'.$paymentParameter['item']
          .'|name'.$paymentParameter['name']
          .'|callback_url'.$paymentParameter['callback_url']
          .'|reference_no'.$paymentParameter['reference_no'];
		
          $checksum = hash_hmac('sha256', $string, $lunaSignature);
		
		  $paymentParameter["checksum"] =  $checksum;
		
		
		$header = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token
        );
		
		 $wp_remote_data['headers'] = $header;
         $wp_remote_data['body'] = http_build_query($paymentParameter);
         $wp_remote_data['method'] = 'POST';

         $response = \wp_remote_post($url, $wp_remote_data);
         $body = \wp_remote_retrieve_body($response);
		
       $newbody = json_decode($body);
		
       	$payment_url = $newbody->payment_url;
       	$payment_id = $newbody->payment_id;

         return array($payment_url, $payment_id);
		
	}

    public function sentPayment($token, $paymentParameter){
		
        $url = $this->current_url.'/api/payments/payment';

        $header = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token
        );

         $wp_remote_data['headers'] = $header;
         $wp_remote_data['body'] = http_build_query($paymentParameter);
         $wp_remote_data['method'] = 'POST';

         $response = \wp_remote_post($url, $wp_remote_data);
         $body = \wp_remote_retrieve_body($response);
		
// 		return $body;

         $newbody = json_decode($body);
         $payment_url = $newbody->payment_url;
         $payment_id = $newbody->payment_id;


         return array($payment_url, $payment_id);
    }

    public function getPaymentStatus($token, $payment_id){

         $url = $this->current_url.'/api/payments/'.$payment_id.'/status';

        $header = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token
        );

        $data = array(
            'payment_id' => $payment_id
        );

         $wp_remote_data['headers'] = $header;
         $wp_remote_data['method'] = 'GET';

         $response = \wp_remote_get($url, $wp_remote_data);
         $body = \wp_remote_retrieve_body($response);

         $newbody = json_decode($body);

         $payment_id = $newbody->payment_id;
         $status = $newbody->status;


        return array($payment_id, $status);
    }

    public static function afterPayment(){
		
//  		return $_POST;

        if(isset($_POST['payment_id']) && isset($_POST['order_id']) && isset($_POST['status'])) {

            $data = array(            
                'payment_id' => $_POST['payment_id'],
				   'order_id' => $_POST['order_id'],
                'status' => $_POST['status']
            );
        }
       
        return $data;
    }

}
