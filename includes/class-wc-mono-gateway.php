<?php
use MonoGateway\Order;
use MonoGateway\Payment;

class WC_Gateway_Mono extends WC_Payment_Gateway
{
    private $token;
    //private $api_url;

    public function __construct()
    {
        loadMonoLibrary();
        $this->id = 'mono_gateway';
        $this->icon = '';

     
        $this->has_fields = false;
        $this->method_title = _x('Monobank Payment', 'womono');
        $this->method_description = __('Accept credit card payments on your website via Monobank payment gateway.', 'womono');

        $this->supports = array('products','refunds');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description  = $this->get_option( 'description' );
        $this->token = $this->get_option('API_KEY');
        $this->destination = $this->get_option('destination');
        $this->redirect = $this->get_option('redirect');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_mono_gateway', array($this, 'callback_success'));
        add_action('woocommerce_order_status_processing', array($this, 'mono_pay_status'));
       
      
    }

   

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'womono' ),
                'type' => 'checkbox',
                'label' => __( 'Enable MonoGateway Payment', 'womono' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __( 'Title', 'womono' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'womono' ),
                'default' => __( 'Оплата онлайн з monopay', 'womono' ),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __( 'Description', 'womono' ),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __( 'This controls the description which the user sees during checkout.', 'womono' ),
                'default' => __( '', 'womono' ),
            ),
            'API_KEY' => array(
                'title' => __( 'Api token', 'womono' ),
                'type' => 'text',
                'description' => __( 'You can find out your X-Token by the link: <a href="https://web.monobank.ua/" target="blank">web.monobank.ua</a>', 'womono' ),
                'default' => '',
            ),
            'destination' => array(
                'title' => __( 'Destination', 'womono' ),
                'type' => 'text',
                'description' => __( 'Призначення платежу', 'womono' ),
                'default' => '',
            ),
            'holdmode' => array(
                'title' => __( 'Enable/Disable Hold', 'womono' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Hold', 'womono' ),
                'default' => 'no'
            ),
            'redirect' => array(
                'title' => __( 'Redirect URL' ),
                'type' => 'text',
                'description' => __( 'You can do this by configuring a setting called the Callback URL in your WP site.', 'womono' ),
                'default' => '',
            )
        );
    }

   
    public function get_icon() {

        $plugin_dir = plugin_dir_url(__FILE__);
        $icon_html = '<img src="'.MONOGATEWAY_PATH.'assets/images/footer_monopay_light_bg.svg" style="width: 85px;"alt="Mono" />';

        return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
    }

    public function process_payment( $order_id ) {

        $token = $this->getToken();
        $redirect_url = $this->getUrlToRedirectMono();
        // $destination=$this->getDestination();

        global $woocommerce, $woocommerce_wpml;

        if ( ! $order_id ) {
            return;
        }

        if ( ! is_object( $woocommerce_wpml ) && class_exists( 'woocommerce_wpml' ) ) {
            $wc_wpml = new woocommerce_wpml();
        } else {
            $wc_wpml = $woocommerce_wpml;
        }

        $order = new WC_Order( $order_id );

        $cart_info = $order->get_items();
        $basket_info = [];

        if ( WC()->cart->get_cart_discount_total() <> 0 ) {

            $count_cart_item = WC()->cart->get_cart_contents_count();
            $sum_product = round((($order->get_total())/$count_cart_item)*100);

            foreach ($cart_info as $item_id => $item_data) {

                $product = $item_data->get_product();
                $item_price = wc_get_price_including_tax( $product );
                $skucode_product = $product->get_id();
                $image_elem = $product->get_image();
                $image = [];
                preg_match_all('/src="(.+)" class/', $image_elem, $image);

                $basket_info[] = [
                    "name" => $product->get_name(),
                    "qty"  => $item_data->get_quantity(),
                    "sum"  => $sum_product,
                    "code" => 'code'.$skucode_product,
                    "icon" => $image[1][0]
                ];

            }

        } else {

            foreach ($cart_info as $item_id => $item_data) {

                $product = $item_data->get_product();
                $item_price = wc_get_price_including_tax( $product );
                $skucode_product = $product->get_id();
                $image_elem = $product->get_image();
                $image = [];
                preg_match_all('/src="(.+)" class/', $image_elem, $image);

                $basket_info[] = [
                    "name" => $product->get_name(),
                    "qty"  => $item_data->get_quantity(),
                    "sum"  => round($item_price*100),
                    "code" => 'code'.$skucode_product,
                    "icon" => $image[1][0]
                ];

            }

        }

        $destination=$order->get_id();

        $monoOrder = new Order();
        $monoOrder->setId($order->get_id());
        $monoOrder->setReference($order->get_id());
        $monoOrder->setDestination('Оплата замовлення #'.$destination);
        $monoOrder->setAmount(round($order->get_total()*100));
        $monoOrder->setBasketOrder($basket_info);

        if(!empty($redirect_url)){
            $monoOrder->setRedirectUrl('https://' . $_SERVER['HTTP_HOST'] . $redirect_url);
        }
         else{
            $monoOrder->setRedirectUrl('https://' . $_SERVER['HTTP_HOST']);
        }

        $monoOrder->setWebHookUrl('https://' . $_SERVER['HTTP_HOST'] . '/?wc-api=mono_gateway');

        $payment = new Payment($token);
        $payment->setOrder($monoOrder);

        $holdMode = $this->get_option( 'holdmode' );
        try {
            $invoice = $payment->create($holdMode);

            if ( !empty($invoice) ) {
                if ($order->get_status() == 'pending') {
                    $inv_id = $invoice->invoiceId;
                    $order->set_transaction_id($inv_id);
                    $order->save();
                }

            } else {
                throw new \Exception("Bad request");
            }
        } catch (\Exception $e) {
            wc_add_notice(  'Request error ('. $e->getMessage() . ')', 'error' );
            return false;
        }
        return [
            'result'   => 'success',
            'redirect' => $invoice->pageUrl,
        ];
    }

    public function callback_success() {

        $holdMode = $this->get_option( 'holdmode' );

        $callback_json = @file_get_contents('php://input');
        $callback = json_decode($callback_json, true);

        $response = new \MonoGateway\Response($callback);
      
        if($response->isComplete($holdMode)) {
            // global $woocommerce;

            $order_id = (int)$response->getOrderId();
            $order = wc_get_order( $order_id );

            WC()->cart->empty_cart();

            $transaction_id = $response->getInvoiceId();
          
            if($holdMode == 'yes'){
                $order->update_status( 'on-hold' );
            }
            else{
                $order->update_status( 'processing' );
            }
                        
        }
    }


    public function can_refund_order( $order ) {

        $has_api_creds = $this->get_option( 'API_KEY' );
        return $order && $order->get_transaction_id() && $has_api_creds;

    }

    public function process_refund( $order_id, $amount = null, $reason = '' ) {

        $order = wc_get_order( $order_id );

        $cart_info = $order->get_items();
        $basket_info = [];

        foreach ($cart_info as $product) {

            $basket_info[] = [
                "name" => $product['name'],
                "qty"  => $product['quantity'],
                "sum"  => round($product['line_total']*100)
            ];
        }
        
        $transaction_id = $order->get_transaction_id();

        if ( ! $this->can_refund_order( $order ) ) {
            return new WP_Error( 'error', __( 'Refund failed.', 'womono' ) );
        }

        $token = $this->getToken();
        $payment = new Payment($token);
        $stringOrderId = (string)$order_id;
        $refund_order = array(
            "invoiceId" => $transaction_id,
            "extRef"=> $stringOrderId,
            "amount" => $amount*100,
            "items" => $basket_info
        );
   
        $payment->setRefundOrder($refund_order);
        try {
            $result = $payment->cancel();
            
            if ( is_wp_error( $result ) ) {
                // $this->log( 'Refund Failed: ' . $result->get_error_message(), 'error' );
                return new WP_Error( 'error', $result->get_error_message() );
            }

            if ($result->status == "reversed") {
                $order->add_order_note(
                    sprintf( __( 'Refunded %1$s - Refund ID: %2$s', 'womono' ), $amount, $result->cancelRef )
                );
                return true;
            }
        } catch (\Exception $e) {
            wc_add_notice('Request error (' . $e->getMessage() . ')', 'error');
            return false;
        }
        return true;
    }

    /*protected function getApiUrl() {
        return $this->api_url;
    }*/

    protected function getToken() {
        return $this->token;
    }

    protected function getUrlToRedirectMono() {
        return $this->redirect;
    }

    protected function getDestination() {
        return $this->destination;
    }

    public function mono_pay_status($order_id) {
        $holdMode = $this->get_option( 'holdmode' );

        if($holdMode == 'yes'){
            $order = wc_get_order( $order_id );
        
            $transaction_id = $order->get_transaction_id();
            $amount = $order->get_total();
            $token = $this->getToken();
            $payment = new Payment($token);
         
            $holdData = array(
                'invoiceId' => $transaction_id,
                'amount' => $amount*100
            );
       
            $payment->finalizeHold($holdData);
        }
      
        return true;
    }


    

   
}
