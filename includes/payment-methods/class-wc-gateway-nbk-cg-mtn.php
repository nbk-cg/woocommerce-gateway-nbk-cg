<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function woomomo_adds_to_the_head() {

    wp_enqueue_script('Callbacks', plugins_url(
        'assets/js/mtn/load.js', WC_NBK_CG_MAIN_FILE ), array('jquery'));
    wp_enqueue_style( 'Responses', plugins_url('assets/css/mtn/style.css', WC_NBK_CG_MAIN_FILE),
        false,'1.1','all');

}
//Add the css and js files to the header.
add_action( 'wp_enqueue_scripts', 'woomomo_adds_to_the_head' );


add_action( 'init', function() {
    /** Add a custom path and set a custom query argument. */
    add_rewrite_rule( '^/payment/?([^/]*)/?', 'index.php?payment_action=1', 'top' );
} );



add_filter( 'query_vars', function( $query_vars ) {
    $query_vars []= 'payment_action';

    return $query_vars;
} );

add_action( 'wp', function() {
//var_dump(get_query_var( 'payment_action' ));die();
    /** This is an call for our custom action. */
    if ( get_query_var( 'payment_action' ) ) {
        var_dump('$query_vars');
        // your code here
        mtn_payment_request();
    }
} );


//Request payment function end
//Results scanner function start
add_action( 'init', function() {

    add_rewrite_rule( '^/scanner/?([^/]*)/?', 'index.php?scanner_action=1', 'top' );
} );
add_filter( 'query_vars', function( $query_vars ) {

    $query_vars []= 'scanner_action';
    return $query_vars;
} );

add_action( 'wp', function() {

    if ( get_query_var( 'scanner_action' ) ) {
        // invoke scanner function
        mtn_scan_transactions();
    }
} );

/**
 * Class that handles MTN payment method.
 *
 * @extends WC_Gateway_Nbk_Cg
 *
 * @since 4.0.0
 */
class WC_Gateway_Nbk_Cg_Mtn extends WC_Nbk_Cg_Payment_Gateway {

    const TYPE = "MTN";


    /**
     * Notices (array)
     *
     * @var array
     */
    public $notices = [];

    /**
     * Is test mode active?
     *
     * @var bool
     */
    public $testmode;

    /**
     * Alternate credit card statement name
     *
     * @var bool
     */
    public $statement_descriptor;

    /**
     * API access secret key
     *
     * @var string
     */
    public $secret_key;



    /**
     * @var string
     */
    public  $test_client_id;

    /**
     * @var string
     */
    public  $test_client_secret;

    /**
     * @var string
     */
    public  $client_id;

    /**
     * @var string
     */
    public  $client_secret;


    /**
     * Should we store the users credit cards?
     *
     * @var bool
     */
    public $saved_cards;


    /**
     * @var string
     *
     */
    public  $account_id;

    /**
     * @var string
     *
     */
    public  $user_id;

    /**
     * @var string
     *
     */
    public  $regions;



    /**
     * Constructor
     */
    public function __construct() {

        if(!isset($_SESSION)){
            session_start();
        }
        $this->id           = 'nbk_cg_mtn';
        $this->method_title = __( 'NBK-CG MTN', 'woocommerce-gateway-nbk-cg' );
        /* translators: link */
        $this->method_description = sprintf( __( 'All other general NBK settings can be adjusted <a href="%s">here</a>.', 'woocommerce-gateway-nbk-cg' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nbk_cg' ) );
        $this->supports           = [
            'products',
        ];

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        $main_settings              = get_option( 'woocommerce_nbk_cg_settings' );
        $this->title                = $this->get_option( 'title' );
        $this->description          = $this->get_option( 'description' );
        $this->enabled              = $this->get_option( 'enabled' );
        $this->testmode             = ( ! empty( $main_settings['testmode'] ) && 'yes' === $main_settings['testmode'] ) ? true : false;
        $this->saved_cards          = ( ! empty( $main_settings['saved_cards'] ) && 'yes' === $main_settings['saved_cards'] ) ? true : false;
        $this->client_id      = ! empty( $main_settings['client_id'] ) ? $main_settings['client_id'] : '';
        $this->client_secret           = ! empty( $main_settings['client_secret'] ) ? $main_settings['client_secret'] : '';
        $this->statement_descriptor = ! empty( $main_settings['statement_descriptor'] ) ? $main_settings['statement_descriptor'] : '';

        $this->account_id       = ! empty( $main_settings['account_id'] ) ? $main_settings['account_id'] : '';
        $this->user_id           = ! empty( $main_settings['user_id'] ) ? $main_settings['user_id'] : '';
        $this->regions           = ! empty( $main_settings['regions'] ) ? $main_settings['regions'] : '';

        $_SESSION['order_status'] = $this->get_option('order_status');
        $_SESSION['currency']   					= $this->get_option( 'currency' );
        if ( $this->testmode ) {
            $this->client_id = ! empty( $main_settings['test_client_id'] ) ? $main_settings['client_id'] : '';
            $this->client_secret      = ! empty( $main_settings['test_client_secret'] ) ? $main_settings['client_secret'] : '';
        }

        WC_Nbk_Cg_API::set_client_id( $this->client_id );
        WC_Nbk_Cg_API::set_client_secret( $this->client_secret );
        add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] );

       // add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

        //add_action('init', array(&$this, 'check_expresspay_response'));
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        }
        add_action( 'woocommerce_receipt_mtn',[ $this, 'receipt_page' ]);
    }

    public function is_test_mode() {
        return $this->testmode;
    }


    /**
     * Returns all supported currencies for this payment method.
     *
     * @since 4.0.0
     * @version 4.0.0
     * @return array
     */
    public function get_supported_currency() {

        $supported_currencies = [
            //'EUR',
            'GHS',
            'UGX',
            'XAF',
            'RWF',
            'XOF',
            'XOF',
            'XAF',
            'SZL',
            'GNF',
            'ZMW',
            'ZAR',
            'LRD'
        ];

        if ($this->is_test_mode()) {
            $supported_currencies[] = 'EUR';
        }
        return apply_filters(
            'wc_nbk_cg_mtn_supported_currencies',
            $supported_currencies
        );
    }

    /**
     * Checks to see if all criteria is met before showing payment method.
     *
     * @since 4.0.0
     * @version 4.0.0
     * @return bool
     */
    public function is_available() {
        if ( ! in_array( get_woocommerce_currency(), $this->get_supported_currency() ) ) {
            return false;
        }

        return parent::is_available();
    }

    /**
     * Get_icon function.
     *
     * @since 1.0.0
     * @version 4.0.0
     * @return string
     */
    public function get_icon() {
        $icons = $this->payment_icons();

        $icons_str = '';

        $icons_str .= isset( $icons['mtn'] ) ? $icons['mtn'] : '';

        return apply_filters( 'woocommerce_gateway_icon', $icons_str, $this->id );
    }

    /**
     * Payment_scripts function.
     *
     * @since 4.0.0
     * @version 4.0.0
     */
    public function payment_scripts()
    {
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order']) && !is_add_payment_method_page()) {
            return;
        }

        /*  wp_enqueue_script( 'nbk_cg_mtn',
              'https://widget.northeurope.cloudapp.azure.com:9443/v0.1.0/mobile-money-widget-mtn.js',
              [],
              '0.1.0',false
          );*/

        // wp_register_style( 'nbk_cg_mtn_load-display', plugins_url( 'assets/css/stripe/normalize.css', WC_NBK_CG_MAIN_FILE ), [], WC_NBK_CG_VERSION );


        /* wp_register_script( 'nbk_cg_mtn_load', plugins_url(
             'assets/js/mtn/load.js', WC_NBK_CG_MAIN_FILE ),
             [], WC_NBK_CG_VERSION, false);

         wp_enqueue_script( 'nbk_cg_mtn_load' );*/
    }

    /**
     * Receipt Page
     **/
    public function receipt_page( $order_id ) {
die('iiii');
        echo $this->generate_iframe( $order_id );
    }



    /**
     * Function that posts the params to momo and generates the html for the page
     */
    public function generate_iframe( $order_id ) {
        var_dump('frf');die();
        // $this->mtn_payment_request();
        global $woocommerce;
        $order = new WC_Order ( $order_id );
        $_SESSION['total'] = (int)$order->order_total;
        $tel = $order->billing_phone;
        //cleanup the phone number and remove unecessary symbols
        $tel = str_replace("-", "", $tel);
        $tel = str_replace( array(' ', '<', '>', '&', '{', '}', '*', "+", '!', '@', '#', "$", '%', '^', '&'), "", $tel );

        $_SESSION['tel'] = substr($tel, -11);


        /**
         * Make the payment here by clicking on pay button and confirm by clicking on complete order button
         */
        if ($_GET['transactionType']=='checkout') {

            echo "<h4>Payment Instructions:</h4>";
            echo "
		  1. Click on the <b>Pay</b> button in order to initiate the MTN Mobile Money payment.<br/>
		  2. Check your mobile phone for a prompt requesting authorization of payment.<br/>
    	  3. Authorize the payment and it will be deducted from your MTN Mobile Money balance.<br/>  	
    	  4. After receiving the MTN Mobile Money payment confirmation message please click on the <b>Complete Order</b> button below to complete the order and confirm the payment made.<br/>";
            echo "<br/>";?>

            <input type="hidden" value="" id="txid"/>
            <?php echo $_SESSION['response_status']; ?>
            <div id="commonname"></div>
            <button onClick="pay()" id="pay_btn">Pay</button>
            <button onClick="complete()" id="complete_btn">Complete Order</button>
            <?php
            echo "<br/>";
        }
    }


    /**
     * Initialize Gateway Settings Form Fields.F
     */
    public function init_form_fields() {
        $this->form_fields = require WC_NBK_CG_PLUGIN_PATH . '/includes/admin/nbk-cg-mtn-settings.php';
    }

    /**
     * Payment form on checkout page
     */
    public function payment_fields() {
        global $wp;
        $user        = wp_get_current_user();
        $total       = WC()->cart->total;
        $description = $this->get_description();

        // If paying from order, we need to get total from order not cart.
        if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) {
            $order = wc_get_order( wc_clean( $wp->query_vars['order-pay'] ) );
            $total = $order->get_total();
        }



        /* echo '<div
              class="mobile-money-qr-payment"
              data-api-user-id="8ad7c789-0c28-48a3-be48-3dbca347d7e"
              data-amount="' . esc_attr( WC_Nbk_Cg_Helper::get_nbk_cg_amount( $total ) ) . '"
              data-currency="' . esc_attr(get_woocommerce_currency() ) . '"
             >
             ';*/

        /* echo '<div
               class="mobile-money-qr-payment"
               data-api-user-id="89e6dbf0-6e61-4aa9-8366-b69f7b69fd44"
               data-amount="300"
               data-currency="' . esc_attr(get_woocommerce_currency() ) . '"
               data-external-id="6121301776b04277bb6d5a73"
                   >';*/
        //</div>

        if ( $description ) {
            echo apply_filters( 'wc_nbk_cg_description', wpautop( wp_kses_post( $description ) ), $this->id );
        }

        // echo '</div>';
    }

    public function cash_in_params($params) {
        return array(
            'amount'	                => (WC()->cart->total * 100),
            'type'                      => self::TYPE,
            'currency'                  => get_woocommerce_currency(),
            'originator'                => [
                'originatorType'  => "MobileNumber",
                'originatorId'    => $params['phoneNumber']
            ],
            'description'               => 'Products payment',
            'regions'                   => [$this->regions],
        );
    }

    /**
     * @return string
     */
    public function get_cash_in_url()
    {
        return sprintf(
            '%s/v1/mtn/payments/accounts/%s/cash-in',
            WC_Nbk_Cg_API::MOBILE_ENDPOINT,
            $this->account_id
        );
    }


    public function get_cash_status_url(string  $transactionId){
        return sprintf(
            '%s/v1/mtn/payments/transactions​/%s/cash-in',
            WC_Nbk_Cg_API::MOBILE_ENDPOINT,
            $transactionId
        );
    }



    /**
     * Process the payment
     *
     * @param int  $order_id Reference.
     * @param bool $retry Should we retry on fail.
     * @param bool $force_save_source Force payment source to be saved.
     *
     * @throws Exception If payment will not be accepted.
     *
     * @return array|void
     */
    public function process_payment( $order_id, $retry = true, $force_save_save = false ) {

        try {
            $params = [];

            $order = new WC_Order($order_id);

            $order->get_billing_phone();

            $params['phoneNumber'] = $order->get_billing_phone();


            $payload = $this->cash_in_params($params);

            $dataResponse = WC_Nbk_Cg_API::request_cash_in(
                $payload,
                $this->get_cash_in_url()
            );

            if (isset($dataResponse['status']) && $dataResponse['status'] === "success") {

                    $response = WC_Nbk_Cg_API::request_cash_in_transaction(
                        $this->get_cash_in_url()
                    );

                if (
                        isset($response['status']) &&
                        $response['status'] === "success" &&
                        isset($response['data']['CashIn']['status']) &&
                        $response['data']['CashIn']['status'] == "success"
                ) {
                    // Payment complete
                    $order->payment_complete($response['data']['CashIn']['transactionId'] );
                    // Add order note
                    $order->add_order_note( sprintf( __( '%s payment approved! Trnsaction ID: %s', 'woocommerce' ), $this->title, $response['data']['CashIn']['transactionId'] ) );

                    // Remove cart
                    if ( isset( WC()->cart ) ) {
                        WC()->cart->empty_cart();
                        $order->reduce_order_stock();
                    }
                    return [
                        'result'                => 'success',
                        'redirect'              => $this->get_return_url( $order ),
                    ];
                }

            }
        } catch (WC_Nbk_Cg_Exception $e) {
            wc_add_notice($e->getLocalizedMessage(), 'error');
            WC_Nbk_Cg_Logger::log('Error: ' . $e->getMessage());

            do_action('WC_Gateway_Nbk_Cgprocess_payment_error', $e, $order);

            $statuses = ['pending', 'failed'];

            if ($order->has_status($statuses)) {
                $this->send_failed_order_email($order_id);
            }

            return [
                'result' => 'fail',
                'redirect' => '',
            ];
        }


       /* $_SESSION['orderID'] = $order->get_id();
        $checkout_url = $order->get_checkout_payment_url(true);
        $checkout_edited_url = $checkout_url."&transactionType=checkout";
        return array(
            'result' => 'success',
            'redirect' => add_query_arg('order-pay', $order->get_id(),
                add_query_arg('key', $order->get_order_key(), $checkout_edited_url))
        );*/

        /*  if ( ! is_ajax() ) {
              wp_safe_redirect(
                  apply_filters( 'woocommerce_checkout_no_payment_needed_redirect', $order->get_checkout_order_received_url(), $order )
              );
              exit;
          }

          wp_send_json(
              array(
                  'result'   => 'success',
                  'redirect' => apply_filters( 'woocommerce_checkout_no_payment_needed_redirect', $order->get_checkout_order_received_url(), $order ),
              )
          );*/
    }
}


function mtn_payment_request() {

    $var = new WC_Gateway_Nbk_Cg_Mtn();

    $params = $var->cash_in_params();

    $dataResponse = WC_Nbk_Cg_API::request_cash_in(
        $params,
        $var->get_cash_in_url()
    );
    //var_dump($dataResponse['status']);die();

    if (isset($dataResponse['status']) && $dataResponse['status'] ===  "success") {
        echo json_encode(array("rescode" => "0", "resmsg" => "Request accepted for processing, please authorize the transaction"));
    } else {
        echo json_encode(array("rescode" => $dataResponse['status'], "resmsg" => "Payment request failed, please try again"));
    }

    exit();

}

function  mtn_scan_transactions(){

    echo json_encode(array("rescode" => "9999", "resmsg" => "Payment status confirmation has been disabled, please request for the Pro Version"));

    exit();
}
