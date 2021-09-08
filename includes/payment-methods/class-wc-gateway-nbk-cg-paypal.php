<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class that handles PayPal payment method.
 *
 * @extends WC_Gateway_Nbk_Cg
 *
 * @since 4.0.0
 */
class WC_Gateway_Nbk_Cg_Paypal extends WC_Nbk_Cg_Payment_Gateway
{
    const TYPE = "PAYPAL";
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
     * Api access publishable key
     *
     * @var string
     */
    public $publishable_key;

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
    public function __construct()
    {
        $this->id = 'nbk_cg_paypal';
        $this->method_title = __('NBK-CG PayPal', 'woocommerce-gateway-nbk-cg');
        /* translators: link */
        $this->method_description = sprintf(__('All other general Stripe settings can be adjusted <a href="%s">here</a>.', 'woocommerce-gateway-nbk-cg'), admin_url('admin.php?page=wc-settings&tab=checkout&section=nbk_cg'));
        $this->supports = [
            'products',
            'refunds',
        ];

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        $main_settings = get_option('woocommerce_nbk_cg_settings');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = (!empty($main_settings['testmode']) && 'yes' === $main_settings['testmode']) ? true : false;
        $this->saved_cards = (!empty($main_settings['saved_cards']) && 'yes' === $main_settings['saved_cards']) ? true : false;
        $this->publishable_key = !empty($main_settings['publishable_key']) ? $main_settings['publishable_key'] : '';
        $this->secret_key = !empty($main_settings['secret_key']) ? $main_settings['secret_key'] : '';

        $this->client_id      = ! empty( $main_settings['client_id'] ) ? $main_settings['client_id'] : '';
        $this->client_secret           = ! empty( $main_settings['client_secret'] ) ? $main_settings['client_secret'] : '';
        $this->statement_descriptor = ! empty( $main_settings['statement_descriptor'] ) ? $main_settings['statement_descriptor'] : '';

        $this->account_id       = ! empty( $main_settings['account_id'] ) ? $main_settings['account_id'] : '';
        $this->regions           = ! empty( $main_settings['regions'] ) ? $main_settings['regions'] : '';

        if ( $this->testmode ) {
            $this->client_id = ! empty( $main_settings['test_client_id'] ) ? $main_settings['client_id'] : '';
            $this->client_secret      = ! empty( $main_settings['test_client_secret'] ) ? $main_settings['client_secret'] : '';
        }

        WC_Nbk_Cg_API::set_client_id( $this->client_id );
        WC_Nbk_Cg_API::set_client_secret( $this->client_secret );

        // Save settings
        if ( is_admin() ) {
            // Versions over 2.0
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        //add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('wp_enqueue_scripts', [$this, 'payment_scripts']);

        add_action( 'check_nbkpaypal', array( $this, 'check_response') );
    }

    /**
     * Returns all supported currencies for this payment method.
     *
     * @return array
     * @version 4.0.0
     * @since 4.0.0
     */
    public function get_supported_currency()
    {
        return apply_filters(
            'wc_nbk_cg_paypal_supported_currencies',
            [
                'EUR',
                'AUD',
                'CAD',
                'CNY',
                'GBP',
                'HKD',
                'JPY',
                'NZD',
                'SGD',
                'USD',
            ]
        );
    }

    /**
     * Checks to see if all criteria is met before showing payment method.
     *
     * @return bool
     * @version 4.0.0
     * @since 4.0.0
     */
    public function is_available()
    {
        if (!in_array(get_woocommerce_currency(), $this->get_supported_currency())) {
            return false;
        }

        return parent::is_available();
    }

    /**
     * Get_icon function.
     *
     * @return string
     * @version 4.0.0
     * @since 1.0.0
     */
    public function get_icon()
    {
        $icons = $this->payment_icons();

        $icons_str = '';

        $icons_str .= isset($icons['paypal']) ? $icons['paypal'] : '';

        return apply_filters('woocommerce_gateway_icon', $icons_str, $this->id);
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

        wp_enqueue_style('stripe_styles');
        wp_enqueue_script('woocommerce_stripe');
    }

    /**
     * Initialize Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = require WC_NBK_CG_PLUGIN_PATH . '/includes/admin/nbk-cg-paypal-settings.php';
    }

    /**
     * Payment form on checkout page
     */
    public function payment_fields()
    {
        global $wp;
        $user = wp_get_current_user();
        $total = WC()->cart->total;
        $description = $this->get_description();

        // If paying from order, we need to get total from order not cart.
        if (isset($_GET['pay_for_order']) && !empty($_GET['key'])) {
            $order = wc_get_order(wc_clean($wp->query_vars['order-pay']));
            $total = $order->get_total();
        }

        if (is_add_payment_method_page()) {
            $pay_button_text = __('Add Payment', 'woocommerce-gateway-nbk-cg');
            $total = '';
        } else {
            $pay_button_text = '';
        }

        echo '<div
			id="nbk-cg-paypal-payment-data"
			data-amount="' . esc_attr(WC_Nbk_Cg_Helper::get_nbk_cg_amount($total)) . '"
			data-currency="' . esc_attr(strtolower(get_woocommerce_currency())) . '">';

        if ($description) {
            echo apply_filters('wc_Nbk_Cg_description', wpautop(wp_kses_post($description)), $this->id);
        }

        echo '</div>';

    }

    public function cash_in_params($params) {
        return array(
            'amount'	                => (WC()->cart->total * 100),
            'type'                      => self::TYPE,
            'currency'                  => get_woocommerce_currency(),
            'originator'                => [
                'originatorType'  => "User Email",
                'originatorId'    => $params['email']
            ],
            'description'               => 'Products payment',
            'regions'                   => [$this->regions],
            'applicationContext'        =>  [
                'successUrl'        => $params['successUrl'],
                'cancelOrFailUrl'   => $params['cancelOrFailUrl']
            ]
        );
    }


    /**
     * Creates the source for charge.
     *
     * @param object $order
     * @return mixed
     * @since 4.0.0
     * @version 4.0.0
     */
    public function create_source($order)
    {
        $currency = $order->get_currency();
        $return_url = $this->get_Nbk_Cg_return_url($order);
        $post_data = [];
        $post_data['amount'] = WC_Nbk_Cg_Helper::get_nbk_cg_amount($order->get_total(), $currency);
        $post_data['currency'] = strtolower($currency);
        $post_data['type'] = 'paypal';
        $post_data['owner'] = $this->get_owner_details($order);
        $post_data['redirect'] = ['return_url' => $return_url];

        if (!empty($this->statement_descriptor)) {
            $post_data['statement_descriptor'] = WC_Nbk_Cg_Helper::clean_statement_descriptor($this->statement_descriptor);
        }

        WC_Nbk_Cg_Logger::log('Info: Begin creating PayPal source');

        return WC_Nbk_Cg_API::request(apply_filters('wc_nbk_cg_mtn_source', $post_data, $order), 'sources');
    }

    /**
     * @return string
     */
    public function get_cash_in_url() {
        return sprintf(
            '%s/v1/paypal/payments/execute/%s',
            WC_Nbk_Cg_API::CARD_ENDPOINT,
            $this->account_id
        );
    }

    /**
     * Process the payment
     *
     * @param int $order_id Reference.
     * @param bool $retry Should we retry on fail.
     * @param bool $force_save_source Force payment source to be saved.
     *
     * @return array|void
     * @throws Exception If payment will not be accepted.
     *
     */
    public function process_payment($order_id, $retry = true, $force_save_save = false)
    {
        try {
            $order = wc_get_order($order_id);

            // This will throw exception if not valid.
            $this->validate_minimum_order_amount($order);

            $redirectUrlsParams = [];

            $baseUrl = $this->get_return_url($order);

            if (strpos($baseUrl, '?') !== false) {
                $baseUrl .= '&';
            } else {
                $baseUrl .= '?';
            }

            $successUrl = sprintf(
                '%nbkpaypal=true&order_id=%s',
                $baseUrl,
                $order_id
            );

            $cancelOrFailUrl = sprintf(
                '%nbkpaypal=cancel&order_id=%s',
                $baseUrl,
                $order_id
            );

            $redirectUrlsParams['email'] = $order->get_billing_email();

            $redirectUrlsParams['successUrl'] = $successUrl;
            $redirectUrlsParams['cancelOrFailUrl'] = $cancelOrFailUrl;

            $params = $this->cash_in_params($redirectUrlsParams);

            $dataResponse = WC_Nbk_Cg_API::request_cash_in(
                $params,
                $this->get_cash_in_url()
            );

            if (
                isset($dataResponse['status']) &&
                $dataResponse['status'] === 'success' &&
                isset($dataResponse['data']['CashIn']['extras']['approveUrl'])
            ) {

                $approveUrl =  $dataResponse['data']['CashIn']['extras']['approveUrl'];

                    $order->save();

                    WC_Nbk_Cg_Logger::log('Info: Redirecting to PayPal...');

                    return [
                        'result' => 'success',
                        'redirect' => esc_url_raw($approveUrl),
                    ];

            }else{

                return [
                    'result' => 'fail',
                    'redirect' => '',
                ];
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
    }

    public function check_response() {
        global $woocommerce;

        if( isset( $_GET['nbkpaypal'] ) ) {

            $woopaypal = $_GET['nbkpaypal'];
            $order_id = $_GET['order_id'];

            if( $order_id == 0 || $order_id == '' ) {
                return;
            }

            $order = new WC_Order( $order_id );

            if( $order->has_status('completed') || $order->has_status('processing')) {
                return;
            }

            if( $woopaypal == 'true' ) {

                $paymentId = $_GET['paymentId'];

                // Payment complete
                $order->payment_complete( $paymentId );
                // Add order note
                $order->add_order_note( sprintf( __( '%s payment approved! Trnsaction ID: %s', 'woocommerce' ), $this->title, $paymentId ) );

                // Remove cart
                $order->cart->empty_cart();
            }

            if( $woopaypal == 'cancel' ) {
                $order = new WC_Order( $order_id );
                $order->update_status('cancelled', sprintf( __( '%s payment cancelled! Transaction ID: %d', 'woocommerce' ), $this->title, $paymentId ) );
            }
        }
        return;
    }
}
