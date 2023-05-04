<?php
namespace MonoGateway;

class Payment {

    private $token;
    protected $order;
    protected $refund_order = null;

    const API_URL = "https://api.monobank.ua/api/merchant";

    public function __construct($token) {
        $this->token = $token;
    }

    protected function _apiRequest($endpoint, $post_fields, $invoice_id = null) {

        $url = self::API_URL . $endpoint;
        if ($endpoint == "/invoice/status" && $invoice_id) {
            $url .= "/$invoice_id";
        }

        $headers = array(
            'Content-type'  => 'application/json',
            'X-Token' => $this->token,
        );

        $body = apply_filters('convertkit-call-args', $post_fields);

        $args = array(
            'method'      => ($endpoint == "/invoice/status") ? 'GET' : 'POST',
            'body'        => json_encode($body),
            'headers'     => $headers,
            'user-agent'  => 'WooCommerce/' . WC()->version,
        );

        $request = wp_safe_remote_post($url, $args);

        if ($request === false) {
            throw new \Exception("Connection error");
        }

        return json_decode($request['body']);
    }

    public function setOrder($order) {
        $this->order = $order;
    }

    public function create($hold = 'no') {
        $currencyCode = get_woocommerce_currency();
        $currencyDecode = 980;

        if($currencyCode == 'UAH'){
            $currencyDecode = 980;
        }

        if($currencyCode == 'USD'){
            $currencyDecode = 840;
        }

        if($currencyCode == 'EUR'){
            $currencyDecode = 978;
        }

        $stringOrderId = (string)$this->order->getId();
        if($hold == 'yes'){
            $body = array(
                'amount' => $this->order->getAmount(),
                'ccy' => $currencyDecode,
                'merchantPaymInfo' => array(
                    'reference' => $stringOrderId,
                    'destination' => $this->order->getDestination(),
                    'basketOrder' => $this->order->getBasketOrder(),
                ),
                'redirectUrl' => $this->order->getRedirectUrl(),
                'webHookUrl' => $this->order->getWebHookUrl(),
                'paymentType' => "hold"
            );
        }
        else{
            $body = array(
                'amount' => $this->order->getAmount(),
                'ccy' => $currencyDecode,
                'merchantPaymInfo' => array(
                    'reference' => $stringOrderId,
                    'destination' => $this->order->getDestination(),
                    'basketOrder' => $this->order->getBasketOrder(),
                ),
                'redirectUrl' => $this->order->getRedirectUrl(),
                'webHookUrl' => $this->order->getWebHookUrl()
            );
        }
      
        $response = $this->_apiRequest("/invoice/create", $body);
        return $response;
    }
//girsus end
    public function getStatus() {}

    public function setRefundOrder($refund_order) {
        $this->refund_order = $refund_order;
    }

    public function cancel() {
        $response = $this->_apiRequest("/invoice/cancel", $this->refund_order);
        return $response;
    }

    public function finalizeHold($holdData) {
        $response = $this->_apiRequest("/invoice/finalize", $holdData);
        error_log(json_encode($response));
        return $response;
    }

}