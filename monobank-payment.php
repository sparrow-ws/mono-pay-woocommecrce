<?php

/**
 * Plugin Name: Monobank WP Payment
 * Description: Перероблений офіційний модуль від monobank для підключення інтернет-еквайрингу. За основу було взято версію 1.0.4.
 * Version: 1.0.0
 */

define('MONOGATEWAY_DIR', plugin_dir_path(__FILE__));
define('MONOGATEWAY_PATH', plugin_dir_url(__FILE__));

add_action( 'plugins_loaded', 'init_mono_gateway_class', 11 );
add_action( 'plugins_loaded', 'true_load_plugin_textdomain', 11 );
add_filter( 'woocommerce_payment_gateways', 'add_mono_gateway_class' );

function true_load_plugin_textdomain() {
    $plugin_path = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
    load_plugin_textdomain( 'womono', false, $plugin_path );
}

function init_mono_gateway_class() {
    require_once MONOGATEWAY_DIR . 'includes/class-wc-mono-gateway.php';
}

function add_mono_gateway_class( $methods ) {
    $currency_code = get_woocommerce_currency();
    if ($currency_code == 'UAH') {
        $methods[] = 'WC_Gateway_Mono';
    }
    if ($currency_code == 'USD') {
        $methods[] = 'WC_Gateway_Mono';
    }
    if ($currency_code == 'EUR') {
        $methods[] = 'WC_Gateway_Mono';
    }
    return $methods;
}

function loadMonoLibrary() {
    require_once MONOGATEWAY_DIR . 'includes/classes/Payment.php';
    require_once MONOGATEWAY_DIR . 'includes/classes/Order.php';
    require_once MONOGATEWAY_DIR . 'includes/classes/Response.php';
}



