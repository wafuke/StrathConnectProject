<?php
class MpesaService {
    private $consumer_key;
    private $consumer_secret;
    private $access_token;
    private $shortcode = '174379'; // Default sandbox business shortcode
    private $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'; // Default sandbox passkey
    private $callback_url = 'https://yourdomain.com/mpesa_callback.php'; // Change this to your actual callback URL

    public function __construct($consumer_key, $consumer_secret) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->access_token = $this->getAccessToken();
    }

    private function getAccessToken() {
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        
        $headers = [
            'Authorization: Basic ' . $credentials
        ];

        $response = $this->makeHttpRequest($url, $headers);
        return $response->access_token ?? null;
    }

    public function stkPush($phone, $amount, $reference, $description) {
        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callback_url,
            'AccountReference' => $reference,
            'TransactionDesc' => $description
        ];

        $headers = [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ];

        return $this->makeHttpRequest($url, $headers, json_encode($payload));
    }

    private function makeHttpRequest($url, $headers = [], $post_data = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($post_data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response);
    }
}
?>