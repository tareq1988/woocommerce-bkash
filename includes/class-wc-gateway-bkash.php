<?php

/**
 * bKash Payment gateway
 *
 * @author Tareq Hasan
 */
class WC_Gateway_bKash extends WC_Payment_Gateway {

    /**
     * Initialize the gateway
     */
    function __construct() {
        $this->id                 = 'bKash';
        $this->icon               = false;
        $this->has_fields         = true;
        $this->method_title       = __( 'bKash', 'wc-bkash' );
        $this->method_description = __( 'Pay via bKash payment', 'wc-bkash' );
        $this->icon               = apply_filters( 'woo_bkash_logo', plugins_url( 'images/bkash-logo.png', dirname( __FILE__ ) ) );

        $title                    = $this->get_option( 'title' );
        $this->title              = empty( $title ) ? __( 'bKash', 'wc-bkash' ) : $title;
        $this->description        = $this->get_option( 'description' );
        $this->instructions       = $this->get_option( 'instructions', $this->description );

        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        add_action( 'woocommerce_thankyou_bKash', array( $this, 'thankyou_page' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options') );
    }

    /**
     * Admin configuration parameters
     *
     * @return void
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'wc-bkash' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable bKash', 'wc-bkash' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title'   => __( 'Title', 'wc-bkash' ),
                'type'    => 'text',
                'default' => __( 'bKash Payment', 'wc-bkash' ),
            ),
            'description' => array(
                'title'       => __( 'Description', 'wc-bkash' ),
                'type'        => 'textarea',
                'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-bkash' ),
                'default'     => __( 'Send your payment directly to +8801****** via bKash. Please use your Order ID as the payment reference. Your order won\'t be shipped until the funds have cleared in our account.', 'wc-bkash' ),
                'desc_tip'    => true,
            ),
            'instructions' => array(
                'title'       => __( 'Instructions', 'wc-bkash' ),
                'type'        => 'textarea',
                'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-bkash' ),
                'default'     => __( 'Send your payment directly to +8801****** via bKash. Please use your Order ID as the payment reference. Your order won\'t be shipped until the funds have cleared in our account.', 'wc-bkash' ),
                'desc_tip'    => true,
            ),
            'trans_help' => array(
                'title'       => __( 'Transaction Help Text', 'wc-bkash' ),
                'type'        => 'textarea',
                'description' => __( 'Instructions that will be added to the transaction form.', 'wc-bkash' ),
                'default'     => __( 'Please enter your transaction ID from the bKash payment to confirm the order.', 'wc-bkash' ),
                'desc_tip'    => true,
            ),
            'username' => array(
                'title' => __( 'Merchant Username', 'wc-bkash' ),
                'type'  => 'text',
            ),
            'pass' => array(
                'title' => __( 'Merchant password', 'wc-bkash' ),
                'type'  => 'text',
            ),
            'mobile' => array(
                'title'       => __( 'Merchant mobile no.', 'wc-bkash' ),
                'type'        => 'text',
                'description' => __( 'Enter your registered merchant mobile number.', 'wc-bkash' ),
            ),
        );
    }

    /**
     * Output for the order received page.
     *
     * @return void
     */
    public function thankyou_page( $order_id ) {

        if ( $this->instructions ) {
            echo wpautop( wptexturize( wp_kses_post( $this->instructions ) ) );
        }

        $order = wc_get_order( $order_id );

        if ( $order->has_status( 'on-hold' ) ) {
            WC_bKash::tranasaction_form( $order_id );
        }
    }

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     *
     * @return void
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

        if ( ! $sent_to_admin && 'bKash' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
            if ( $this->instructions ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

    }

    /**
     * Process the gateway integration
     *
     * @param  int  $order_id
     *
     * @return void
     */
    public function process_payment( $order_id ) {

        $order = wc_get_order( $order_id );

        // Mark as on-hold (we're awaiting the payment)
        $order->update_status( 'on-hold', __( 'Awaiting bKash payment', 'wc-bkash' ) );

        // Remove cart
        WC()->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result'    => 'success',
            'redirect'  => $this->get_return_url( $order )
        );
    }

    /**
     * Validate place order submission
     *
     * @return bool
     */
    public function validate_fieldss() {
        global $woocommerce;

        if ( empty( $_POST['bkash_trxid'] ) ) {
            wc_add_notice( __( 'Please type the transaction ID.', 'wc-bkash' ), 'error' );
            return;
        }

        return true;
    }

}