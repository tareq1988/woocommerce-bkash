<?php

/**
 * bKash Class
 */
class WC_bKash {

    const base_url = 'http://www.bkashcluster.com:9080/dreamwave/merchant/trxcheck/sendmsg';
    private $table = 'wc_bkash';

    function __construct() {
        add_action( 'wp_ajax_wc-bkash-confirm-trx', array($this, 'process_form') );

        add_action( 'woocommerce_order_details_after_order_table', array($this, 'transaction_form_order_view') );
    }

    function transaction_form_order_view( $order ) {

        if ( $order->has_status( 'on-hold' ) && $order->payment_method == 'bKash' && is_view_order_page() ) {
            self::tranasaction_form( $order->id );
        }
    }

    /**
     * Show the payment field in checkout
     *
     * @return void
     */
    public static function tranasaction_form( $order_id ) {
        $option = get_option( 'woocommerce_bKash_settings', array() );
        ?>

        <div class="wc-bkash-form-wrap" style="background: #eee;padding: 15px;border: 1px solid #ddd; margin: 15px 0;">
            <div id="wc-bkash-result"></div>
            <form action="" method="post" id="wc-bkash-confirm" class="wc-bkash-form">
                <p class="form-row validate-required">
                    <label><?php _e( 'Transaction ID', 'wc-bkash' ) ?>: <span class="required">*</span></label>

                    <input class="input-text" type="text" name="bkash_trxid" required />
                    <span class="description"><?php echo isset( $option['trans_help'] ) ? $option['trans_help'] : ''; ?></span>
                </p>

                <p class="form-row">
                    <?php wp_nonce_field( 'wc-bkash-confirm-trx' ); ?>
                    <input type="hidden" name="action" value="wc-bkash-confirm-trx">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

                    <?php $pay_order_button_text = apply_filters( 'wc_bkash_pay_order_button_text', __( 'Confirm Payment', 'wc-bkash' ) ); ?>
                    <input type="submit" class="button alt" id="wc-bkash-submit" value="<?php echo esc_attr( $pay_order_button_text ); ?>" />
                </p>
            </form>
        </div>

        <script type="text/javascript">
            jQuery(function($) {
                $('form#wc-bkash-confirm').on('submit', function(event) {
                    event.preventDefault();

                    var submit = $(this).find('input[type=submit]');
                    submit.attr('disabled', 'disabled');

                    $.post('<?php echo admin_url( 'admin-ajax.php'); ?>', $(this).serialize(), function(data, textStatus, xhr) {
                        submit.removeAttr('disabled');

                        if ( data.success ) {
                            window.location.href = data.data;
                        } else {
                            $('#wc-bkash-result').html('<ul class="woocommerce-error"><li>' + data.data + '</li></ul>');
                        }
                    });
                });
            });
        </script>
        <?php
    }

    public function process_form() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wc-bkash-confirm-trx' ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'wc-bkash' ) );
        }

        $order_id       = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $transaction_id = sanitize_key( $_POST['bkash_trxid'] );

        $order          = wc_get_order( $order_id );
        $response       = $this->do_request( $transaction_id );

        if ( ! $response ) {
            wp_send_json_error( __( 'Something went wrong submitting the request', 'wc-bkash' ) );
            return;
        }

        if ( $this->transaction_exists( $response->trxId ) ) {
            wp_send_json_error( __('This transaction has already been used!', 'wc-bkash' ) );
            return;
        }

        switch ($response->trxStatus) {

            case '0010':
            case '0011':
                wp_send_json_error( __( 'Transaction is pending, please try again later', 'wc-bkash' ) );
                return;

            case '0100':
                wp_send_json_error( __( 'Transaction ID is valid but transaction has been reversed. ', 'wc-bkash' ) );
                return;

            case '0111':
                wp_send_json_error( __( 'Transaction is failed.', 'wc-bkash' ) );
                return;

            case '1001':
                wp_send_json_error( __( 'Invalid MSISDN input. Try with correct mobile no.', 'wc-bkash' ) );
                break;

            case '1002':
                wp_send_json_error( __( 'Invalid transaction ID', 'wc-bkash' ) );
                return;

            case '1003':
                wp_send_json_error( __( 'Authorization Error, please contact site admin.', 'wc-bkash' ) );
                return;

            case '1004':
                wp_send_json_error( __( 'Transaction ID not found.', 'wc-bkash' ) );
                return;

            case '9999':
                wp_send_json_error( __( 'System error, could not process request. Please contact site admin.', 'wc-bkash' ) );
                return;

            case '0000':
                $price = (float) $order->get_total();

                // check for BDT if exists
                $bdt_price = get_post_meta( $order->id, '_bdt', true );
                if ( $bdt_price != '' ) {
                    $price = $bdt_price;
                }

                if ( $price > (float) $response->amount ) {
                    wp_send_json_error( __( 'Transaction amount didn\'t match, are you cheating?', 'wc-bkash' ) );
                    return;
                }

                $this->insert_transaction( $response );

                $order->add_order_note( sprintf( __( 'bKash payment completed with TrxID#%s! bKash amount: %s', 'wc-bkash' ), $response->trxId, $response->amount ) );
                $order->payment_complete();

                wp_send_json_success( $order->get_view_order_url() );

                break;
        }

        wp_send_json_error();
    }

    /**
     * Do the remote request
     *
     * For some reason, WP_HTTP doesn't work here. May be
     * some implementation related problem in their side.
     *
     * @param  string  $transaction_id
     *
     * @return object
     */
    function do_request( $transaction_id ) {

        $option = get_option( 'woocommerce_bKash_settings', array() );
        $query = array(
            'user'   => isset( $option['username'] ) ? $option['username'] : '',
            'pass'   => isset( $option['pass'] ) ? $option['pass'] : '',
            'msisdn' => isset( $option['mobile'] ) ? $option['mobile'] : '',
            'trxid'  => $transaction_id
        );

        $url      = self::base_url . '?' . http_build_query( $query, '', '&' );
        $response = file_get_contents( $url );

        if ( false !== $response ) {
            $response = json_decode( $response );

            return $response->transaction;
        }

        return false;
    }

    /**
     * Insert transaction info in the db table
     *
     * @param  object  $response
     *
     * @return void
     */
    function insert_transaction( $response ) {
        global $wpdb;

        $wpdb->insert( $wpdb->prefix . $this->table, array(
            'trxId'  => $response->trxId,
            'sender' => $response->sender,
            'ref'    => $response->reference,
            'amount' => $response->amount
        ), array(
            '%d',
            '%s',
            '%s',
            '%s'
        ) );
    }

    /**
     * Check if a transaction exists
     *
     * @param  string  $transaction_id
     *
     * @return bool
     */
    function transaction_exists( $transaction_id ) {
        global $wpdb;

        $query  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}{$this->table} WHERE trxId = %d", $transaction_id );
        $result = $wpdb->get_row( $query );

        if ( $result ) {
            return true;
        }

        return false;
    }
}

new WC_bKash();