<?php

/*
  Plugin Name: bKash for WooCommerce
  Plugin URI: http://wedevs.com
  Description: bKash payment gateway integration for WooCommerce
  Version: 0.1
  Author: Tareq Hasan
  Author URI: http://tareq.wedevs.com
 */

/**
 * WooCommerce - bKash integration
 * 
 * @author Tareq Hasan
 */
class WC_bKash {

    function __construct() {
        add_action( 'plugins_loaded', array($this, 'init') );
        add_filter( 'woocommerce_payment_gateways', array($this, 'register_gateway') );
        
        register_activation_hook( __FILE__, array($this, 'install') );
    }

    function init() {
        if ( !class_exists( 'WC_Payment_Gateway' ) ) {
            return;
        }

        require_once dirname( __FILE__ ) . '/gateway.php';

        new WC_Gateway_bKash();
    }

    function register_gateway( $gateways ) {
        $gateways[] = 'WC_Gateway_bKash';

        return $gateways;
    }
    
    function install() {
        global $wpdb;
        
        $query = "CREATE TABLE `{$wpdb->prefix}wc_bkash` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `trxId` int(11) DEFAULT NULL,
            `sender` varchar(15) DEFAULT NULL,
            `ref` varchar(100) DEFAULT NULL,
            `amount` varchar(10) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `trxId` (`trxId`)
          ) ENGINE=InnoDBDEFAULT CHARSET=utf8;";
        
        $wpdb->query( $query );
    }

}

new WC_bKash();