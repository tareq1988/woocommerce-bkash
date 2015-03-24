<?php
/*
Plugin Name: bKash for WooCommerce
Plugin URI: http://wedevs.com
Description: bKash payment gateway integration for WooCommerce
Version: 0.1
Author: Tareq Hasan
Author URI: http://tareq.wedevs.com
*/

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * WooCommerce - bKash integration
 *
 * @author Tareq Hasan
 */
class WeDevs_bKash {

    private $db_version = '0.2';

    /**
     * Kick off the plugin
     */
    public function __construct() {
        add_action( 'plugins_loaded', array($this, 'init') );
        add_filter( 'woocommerce_payment_gateways', array($this, 'register_gateway') );

        register_activation_hook( __FILE__, array($this, 'install') );
    }

    /**
     * Load the plugin on `init` hook
     *
     * @return void
     */
    function init() {
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
            return;
        }

        require_once dirname( __FILE__ ) . '/includes/class-wc-bkash.php';
        require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-bkash.php';
    }

    /**
     * Register WooCommerce Gateway
     *
     * @param  array  $gateways
     *
     * @return array
     */
    function register_gateway( $gateways ) {
        $gateways[] = 'WC_Gateway_bKash';

        return $gateways;
    }

    /**
     * Create the transaction table
     *
     * @return void
     */
    function install() {
        global $wpdb;

        $query = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wc_bkash` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `trxId` bigint(20) DEFAULT NULL,
            `sender` varchar(15) DEFAULT NULL,
            `ref` varchar(100) DEFAULT NULL,
            `amount` varchar(10) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `trxId` (`trxId`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $wpdb->query( $query );

        update_option( '_bkash_version', $this->db_version );
    }
}

new WeDevs_bKash();