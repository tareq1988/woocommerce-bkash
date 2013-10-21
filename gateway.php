<?php

/**
 * bKash Payment gateway
 *
 * @author Tareq Hasan
 */
class WC_Gateway_bKash extends WC_Payment_Gateway {

    const base_url = 'http://www.bkashcluster.com:9080/dreamwave/merchant/trxcheck/sendmsg';
    private $table = 'wc_bkash';

    function __construct() {
        $this->id = 'bKash';
        $this->icon = false;
        $this->has_fields = true;
        $this->method_title = __( 'bKash', 'wc-bkash' );
        $this->method_description = __( 'Pay via bKash payment', 'wc-bkash' );
        $this->icon = apply_filters( 'woo_bkash_logo', plugins_url( 'images/bkash-logo.png', __FILE__ ) );

        $title = $this->get_option( 'title' );
        $this->title = empty( $title ) ? __( 'bKash', 'wc-bkash' ) : $title;

        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options') );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'wc-bkash' ),
                'type' => 'checkbox',
                'label' => __( 'Enable bKash', 'wc-bkash' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __( 'Title', 'wc-bkash' ),
                'type' => 'text',
                'default' => __( 'bKash Payment', 'wc-bkash' ),
            ),
            'description' => array(
                'title' => __( 'Customer Message', 'wc-bkash' ),
                'type' => 'textarea',
                'default' => 'Enter your payment transaction ID'
            ),
            'username' => array(
                'title' => __( 'Merchant Username', 'wc-bkash' ),
                'type' => 'text',
            ),
            'pass' => array(
                'title' => __( 'Merchant password', 'wc-bkash' ),
                'type' => 'text',
            ),
            'mobile' => array(
                'title' => __( 'Merchant mobile no.', 'wc-bkash' ),
                'type' => 'text',
                'description' => __( 'Enter your registered merchant mobile number.', 'wc-bkash' ),
            ),
        );
    }

    public function payment_fields() {
        ?>
        <p class="form-row validate-required">
            <label>Transaction ID <span class="required">*</span></label>

            <input class="input-text" type="text" name="bkash_trxid" />
            <span class="description"><?php echo $this->get_option('description'); ?></span>
        </p>
        <?php

    }

    function do_request( $transaction_id ) {
        /*
          global $wp_version;

          $params = array(
          'timeout' => 15,
          'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
          'body' => array(
          'user' => $this->get_option( 'username' ),
          'pass' => $this->get_option( 'pass' ),
          'msisdn' => $this->get_option( 'mobile' ),
          'trxid' => $transaction_id
          )
          );

          $response = wp_remote_get( self::base_url, $params );
          $update = wp_remote_retrieve_body( $response );

          var_dump($response);

          if ( is_wp_error( $response ) || $response['response']['code'] != 200 ) {
          return false;
          }

          $response = json_decode( $update );
          var_dump($response);
         */

        $query = array(
            'user' => 'MelbwebHosting',
            'pass' => 'mikewisk3yh0t31',
            'msisdn' => '01779375624',
            'trxid' => $transaction_id
        );

        $url = self::base_url . '?' . http_build_query( $query, '', '&' );
        $response = file_get_contents( $url );

        if ( false !== $response ) {
            $response = json_decode( $response );
            return $response->transaction;
        }

        return false;
    }

    public function process_payment( $order_id ) {
        global $woocommerce;

        $order = new WC_Order( $order_id );

        //$transaction_id = '432881128';
        $transaction_id = sanitize_key( $_POST['bkash_trxid'] );
        $response = $this->do_request( $transaction_id );

        if ( !$response ) {
            $woocommerce->add_error( __( 'Something went wrong submitting the request', 'wc-bkash' ) );
            return;
        }

        if ( $this->transaction_exists( $response->trxId ) ) {
            $woocommerce->add_error( __('Transaction already been used!', 'wc-bkash' ) );
            return;
        }

        switch ($response->trxStatus) {

            case '0010':
            case '0011':
                $woocommerce->add_error( __( 'Transaction is pending, please try again later', 'wc-bkash' ) );
                return;

            case '1002':
                $woocommerce->add_error( __( 'Invalid transaction ID', 'wc-bkash' ) );
                return;

            case '0111':
                $woocommerce->add_error( __( 'Transaction is failed.', 'wc-bkash' ) );
                return;

            case '1003':
                $woocommerce->add_error( __( 'Authorization Error, please contact site admin.', 'wc-bkash' ) );
                return;

            case '1004':
                $woocommerce->add_error( __( 'Transaction ID not found.', 'wc-bkash' ) );
                return;

            case '9999':
                $woocommerce->add_error( __( 'System error, please contact site admin.', 'wc-bkash' ) );
                return;

            case '0000':
                $price = (float) $order->get_total();

                // check for BDT if exists
                $bdt_price = get_post_meta( $order->id, '_bdt', true );
                if ( $bdt_price != '' ) {
                    $price = $bdt_price;
                }

                if ( $price > (float) $response->amount ) {
                    $woocommerce->add_error( __( 'Transaction amount didn\'t match, are you cheating?', 'wc-bkash' ) );
                    return;
                }

                $order->add_order_note( sprintf( __( 'bKash payment completed with TrxID#%s! bKash amount: %s', 'wc-bkash' ), $response->trxId, $response->amount ) );
                $order->payment_complete();
                $this->insert_transaction( $response );
                $order->update_status( 'completed' );

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order )
                );

                break;
        }
    }

    public function validate_fields() {
        global $woocommerce;

        if ( empty( $_POST['bkash_trxid'] ) ) {
            $woocommerce->add_error( __( 'Please type the transaction ID.', 'wc-bkash' ) );
            return;
        }

        return true;
    }

    function insert_transaction( $response ) {
        global $wpdb;

        $wpdb->insert( $wpdb->prefix . $this->table, array(
            'trxId' => $response->trxId,
            'sender' => $response->sender,
            'ref' => $response->reference,
            'amount' => $response->amount
        ), array(
            '%d',
            '%s',
            '%s',
            '%s'
        ) );
    }

    function transaction_exists( $transaction_id ) {
        global $wpdb;

        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table} WHERE trxId = %d", $transaction_id ) );
        if ( $result ) {
            return true;
        }

        return false;
    }

}