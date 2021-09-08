<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_Nbk_Cg class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Nbk_Cg extends WC_Nbk_Cg_Payment_Gateway {
	/**
	 * The delay between retries.
	 *
	 * @var int
	 */
	public $retry_interval;

	/**
	 * Should we capture Credit cards
	 *
	 * @var bool
	 */
	public $capture;

	/**
	 * Alternate credit card statement name
	 *
	 * @var bool
	 */
	public $statement_descriptor;

	/**
	 * Should we store the users credit cards?
	 *
	 * @var bool
	 */
	public $saved_cards;

	/**
	 * API access secret key
	 *
	 * @var string
	 */
	public $secret_key;


	/**
	 * Do we accept Payment Request?
	 *
	 * @var bool
	 */
	public $payment_request;

	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Inline CC form styling
	 *
	 * @var string
	 */
	public $inline_cc_form;

	/**
	 * Pre Orders Object
	 *
	 * @var object
	 */
	public $pre_orders;

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
     * @var string
     */
    public  $regions;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->retry_interval = 1;
		$this->id             = 'nbk_cg';
		$this->method_title   = __( 'NBK-CG', 'woocommerce-gateway-nbk-cg' );
		/* translators: 1) link to Nbk-cg register page 2) link to Nbk-cg api keys page */
		$this->method_description = __( 'Nbk-cg works by adding payment fields on the checkout and then sending the details to Nbk-cg for verification.', 'woocommerce-gateway-nbk-cg' );
		$this->has_fields         = true;
		$this->supports           = [
			'products',
			'refunds',
			'tokenization',
			'add_payment_method',
			'pre-orders',
		];

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values.
		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->enabled              = $this->get_option( 'enabled' );
		$this->testmode             = 'yes' === $this->get_option( 'testmode' );
		$this->payment_request      = 'yes' === $this->get_option( 'payment_request', 'yes' );

        $this->account_id       = $this->get_option( 'account_id');
        //$this->test_client_id   = $this->testmode ? $this->get_option( 'test_client_id' ) : $this->get_option( 'test_client_id' );
        //$this->test_client_secret   = $this->testmode ? $this->get_option( 'test_client_secret' ) : $this->get_option( 'test_client_secret' );
        $this->client_id        = $this->testmode ? $this->get_option( 'test_client_id' ) : $this->get_option( 'client_id');
        $this->client_secret    = $this->testmode ? $this->get_option( 'test_client_secret' ) : $this->get_option( 'client_secret' );

        $this->regions           = $this->get_option( 'regions');


        WC_Nbk_Cg_API::set_client_id( $this->client_id );
        WC_Nbk_Cg_API::set_client_secret( $this->client_secret );

       WC_Nbk_Cg_API::set_access_token();

		// Hooks.
		add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_customer_save_address', [ $this, 'show_update_card_notice' ], 10, 2 );
		add_filter( 'woocommerce_payment_successful_result', [ $this, 'modify_successful_payment_result' ], 99999, 2 );
		add_action( 'set_logged_in_cookie', [ $this, 'set_cookie_on_current_request' ] );
		add_filter( 'woocommerce_get_checkout_payment_url', [ $this, 'get_checkout_payment_url' ], 10, 2 );

		// Note: display error is in the parent class.
		add_action( 'admin_notices', [ $this, 'display_errors' ], 9999 );

		if ( WC_Nbk_Cg_Helper::is_pre_orders_exists() ) {
			$this->pre_orders = new WC_Nbk_Cg_Pre_Orders_Compat();

			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, [ $this->pre_orders, 'process_pre_order_release_payment' ] );
		}
	}

	/**
	 * Checks if gateway should be available to use.
	 *
	 * @since 4.0.2
	 */
	public function is_available() {
		if ( is_add_payment_method_page() && ! $this->saved_cards ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Adds a notice for customer when they update their billing address.
	 *
	 * @since 4.1.0
	 * @param int    $user_id      The ID of the current user.
	 * @param string $load_address The address to load.
	 */
	public function show_update_card_notice( $user_id, $load_address ) {
		if ( ! $this->saved_cards || ! WC_Nbk_Cg_Payment_Tokens::customer_has_saved_methods( $user_id ) || 'billing' !== $load_address ) {
			return;
		}

		/* translators: 1) Opening anchor tag 2) closing anchor tag */
		wc_add_notice( sprintf( __( 'If your billing address has been changed for saved payment methods, be sure to remove any %1$ssaved payment methods%2$s on file and re-add them.', 'woocommerce-gateway-nbk-cg' ), '<a href="' . esc_url( wc_get_endpoint_url( 'payment-methods' ) ) . '" class="wc-nbk-cg-update-card-notice" style="text-decoration:underline;">', '</a>' ), 'notice' );
	}

	/**
	 * Get_icon function.
	 *
	 * @since 1.0.0
	 * @version 4.9.0
	 * @return string
	 */
	public function get_icon() {
		$icons                 = $this->payment_icons();
		$supported_card_brands = WC_Nbk_Cg_Helper::get_supported_card_brands();

		$icons_str = '';

		foreach ( $supported_card_brands as $brand ) {
			$icons_str .= isset( $icons[ $brand ] ) ? $icons[ $brand ] : '';
		}

		return apply_filters( 'woocommerce_gateway_icon', $icons_str, $this->id );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = require dirname( __FILE__ ) . '/admin/nbk-cg-settings.php';
	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		global $wp;
		$user                 = wp_get_current_user();
		$display_tokenization = $this->supports( 'tokenization' ) && is_checkout() && $this->saved_cards;
		$total                = WC()->cart->total;
		$user_email           = '';
		$description          = $this->get_description();
		$description          = ! empty( $description ) ? $description : '';
		$firstname            = '';
		$lastname             = '';

		// If paying from order, we need to get total from order not cart.
		if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) { // wpcs: csrf ok.
			$order      = wc_get_order( wc_clean( $wp->query_vars['order-pay'] ) ); // wpcs: csrf ok, sanitization ok.
			$total      = $order->get_total();
			$user_email = $order->get_billing_email();
		} else {
			if ( $user->ID ) {
				$user_email = get_user_meta( $user->ID, 'billing_email', true );
				$user_email = $user_email ? $user_email : $user->user_email;
			}
		}

		if ( is_add_payment_method_page() ) {
			$firstname = $user->user_firstname;
			$lastname  = $user->user_lastname;
		}

		ob_start();

		echo '<div
			id="nbk-cg-payment-data"
			data-email="' . esc_attr( $user_email ) . '"
			data-full-name="' . esc_attr( $firstname . ' ' . $lastname ) . '"
			data-currency="' . esc_attr( strtolower( get_woocommerce_currency() ) ) . '"
		>';

		if ( $this->testmode ) {
			// translators: link to Nbk-cg testing page
			$description .= ' ' . sprintf( __( 'TEST MODE ENABLED. In test mode, you can use the card number 4242424242424242 with any CVC and a valid expiration date or check the <a href="%s" target="_blank">Testing Nbk-cg documentation</a> for more card numbers.', 'woocommerce-gateway-nbk-cg' ), 'https://nbk-cg.com/docs/testing' );
		}

		$description = trim( $description );

		echo apply_filters( 'wc_nbk_cg_description', wpautop( wp_kses_post( $description ) ), $this->id ); // wpcs: xss ok.

		if ( $display_tokenization ) {
			$this->tokenization_script();
			$this->saved_payment_methods();
		}

		$this->elements_form($user_email,
                        $total,
                        get_woocommerce_currency(),
                        $this->account_id,
                        'payment'
        );

        if ( apply_filters( 'wc_nbk_cg_display_save_payment_method_checkbox', $display_tokenization ) && ! is_add_payment_method_page() && ! isset( $_GET['change_payment_method'] ) ) { // wpcs: csrf ok.

			$this->save_payment_method_checkbox();
		}

		do_action( 'wc_nbk_cg_cards_payment_fields', $this->id );

		echo '</div>';

		ob_end_flush();
	}

    /*public function validate_fields(){

        if( empty( $_POST[ 'cardnumber' ]) ) {
            wc_add_notice(  'cardnumber  is required!', 'error' );
            return false;
        }

        if( empty( $_POST[ 'exp-date' ]) ) {
            wc_add_notice(  'exp-date is required!', 'error' );
            return false;
        }

        if( empty( $_POST[ 'cvc' ]) ) {
            wc_add_notice(  'cvc is required!', 'error' );
            return false;
        }

        if( empty( $_POST[ 'zipcode' ]) ) {
            wc_add_notice(  'First name is required!', 'error' );
            return false;
        }
        return true;

    }*/

	/**
	 * Renders the Nbk-cg elements form.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public function elements_form($email, $amount, $currency, $accountId, $description) {
		?>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
                <div class="sr-combo-inputs-row">
                    <div class="sr-input sr-card-element" id="card-element"></div>
                </div>

				<div class="clear"></div>

			<!-- Used to display form errors -->
                <div class="sr-field-error" id="card-errors" role="alert"></div>
			<br />
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>

        <script>
            var originator= {
                originatorType: "User Email",
                originatorId: "<?php echo $email ?>"
            };
            var orderData = {
                currency: "<?php echo $currency ?>",
                amount: <?php echo $amount ?>,
                accountId: "<?php echo $accountId ?>",
                description: "<?php echo $description ?>",
                regions: ["5e99a07063389569485205f3"],
                originator: originator

            };
            var accessToken = "<?php echo  WC_Nbk_Cg_API::get_token() ?>"
        </script>
		<?php
	}

	/**
	 * Load admin scripts.
	 *
	 * @since 3.1.0
	 * @version 3.1.0
	 */
	public function admin_scripts() {
		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'woocommerce_Nbk_Cg_admin', plugins_url( 'assets/js/nbk-cg-admin' . $suffix . '.js', WC_NBK_CG_MAIN_FILE ), [], WC_NBK_CG_VERSION, true );

		$params = [
			'time'             => time(),
			'i18n_out_of_sync' => wp_kses(
				__( '<strong>Warning:</strong> your site\'s time does not match the time on your browser and may be incorrect. Some payment methods depend on webhook verification and verifying webhooks with a signing secret depends on your site\'s time being correct, so please check your site\'s time before setting a webhook secret. You may need to contact your site\'s hosting provider to correct the site\'s time.', 'woocommerce-gateway-nbk-cg' ),
				[ 'strong' => [] ]
			),
		];
		wp_localize_script( 'woocommerce_Nbk_Cg_admin', 'wc_Nbk_Cg_settings_params', $params );

		wp_enqueue_script( 'woocommerce_Nbk_Cg_admin' );
	}

	/**
	 * Returns the JavaScript configuration object used on the product, cart, and checkout pages.
	 *
	 * @return array  The configuration object to be loaded to JS.
	 */
	public function javascript_params() {
		global $wp;

		$nbk_cg_params = [
			'key'                  => $this->client_id,
			'i18n_terms'           => __( 'Please accept the terms and conditions first', 'woocommerce-gateway-nbk-cg' ),
			'i18n_required_fields' => __( 'Please fill in required checkout fields first', 'woocommerce-gateway-nbk-cg' ),
            'home_url'             => home_url(),
            'endpoint'             => WC_Nbk_Cg_API::CARD_ENDPOINT
		];

		// If we're on the pay page we need to pass Nbk-cg.js the address of the order.
		if ( isset( $_GET['pay_for_order'] ) && 'true' === $_GET['pay_for_order'] ) { // wpcs: csrf ok.
			$order_id = wc_clean( $wp->query_vars['order-pay'] ); // wpcs: csrf ok, sanitization ok, xss ok.
			$order    = wc_get_order( $order_id );

			if ( is_a( $order, 'WC_Order' ) ) {
				$nbk_cg_params['billing_first_name'] = $order->get_billing_first_name();
				$nbk_cg_params['billing_last_name']  = $order->get_billing_last_name();
				$nbk_cg_params['billing_address_1']  = $order->get_billing_address_1();
				$nbk_cg_params['billing_address_2']  = $order->get_billing_address_2();
				$nbk_cg_params['billing_state']      = $order->get_billing_state();
				$nbk_cg_params['billing_city']       = $order->get_billing_city();
				$nbk_cg_params['billing_postcode']   = $order->get_billing_postcode();
				$nbk_cg_params['billing_country']    = $order->get_billing_country();
			}
		}

		$sepa_elements_options = apply_filters(
			'wc_Nbk_Cg_sepa_elements_options',
			[
				'supportedCountries' => [ 'SEPA' ],
				'placeholderCountry' => WC()->countries->get_base_country(),
				'style'              => [ 'base' => [ 'fontSize' => '15px' ] ],
			]
		);

		$nbk_cg_params['nbk_cg_locale']             = WC_Nbk_Cg_Helper::convert_wc_locale_to_Nbk_Cg_locale( get_locale() );
		$nbk_cg_params['no_prepaid_card_msg']       = __( 'Sorry, we\'re not accepting prepaid cards at this time. Your credit card has not been charged. Please try with alternative payment method.', 'woocommerce-gateway-nbk-cg' );
		$nbk_cg_params['payment_intent_error']      = __( 'We couldn\'t initiate the payment. Please try again.', 'woocommerce-gateway-nbk-cg' );
		$nbk_cg_params['allow_prepaid_card']        = apply_filters( 'wc_Nbk_Cg_allow_prepaid_card', true ) ? 'yes' : 'no';
		$nbk_cg_params['is_checkout']               = ( is_checkout() && empty( $_GET['pay_for_order'] ) ) ? 'yes' : 'no'; // wpcs: csrf ok.
		$nbk_cg_params['return_url']                = $this->get_Nbk_Cg_return_url();
		$nbk_cg_params['ajaxurl']                   = WC_AJAX::get_endpoint( '%%endpoint%%' );
		$nbk_cg_params['nbk_cg_nonce']              = wp_create_nonce( '_wc_Nbk_Cg_nonce' );
		$nbk_cg_params['statement_descriptor']      = $this->statement_descriptor;
		$nbk_cg_params['elements_options']          = apply_filters( 'wc_Nbk_Cg_elements_options', [] );
		$nbk_cg_params['invalid_owner_name']        = __( 'Billing First Name and Last Name are required.', 'woocommerce-gateway-nbk-cg' );
		$nbk_cg_params['is_change_payment_page']    = isset( $_GET['change_payment_method'] ) ? 'yes' : 'no'; // wpcs: csrf ok.
		$nbk_cg_params['is_add_payment_page']       = is_wc_endpoint_url( 'add-payment-method' ) ? 'yes' : 'no';
		$nbk_cg_params['is_pay_for_order_page']     = is_wc_endpoint_url( 'order-pay' ) ? 'yes' : 'no';
		$nbk_cg_params['elements_styling']          = apply_filters( 'wc_Nbk_Cg_elements_styling', false );
		$nbk_cg_params['elements_classes']          = apply_filters( 'wc_Nbk_Cg_elements_classes', false );
		$nbk_cg_params['add_card_nonce']            = wp_create_nonce( 'wc_Nbk_Cg_create_si' );
        $nbk_cg_params['home_url']                  = home_url();

		// Merge localized messages to be use in JS.
		$nbk_cg_params = array_merge( $nbk_cg_params, WC_Nbk_Cg_Helper::get_localized_messages() );

		return $nbk_cg_params;
	}

	/**
	 * Payment_scripts function.
	 *
	 * Outputs scripts used for Nbk-cg payment
	 *
	 * @since 3.1.0
	 * @version 4.0.0
	 */
	public function payment_scripts() {

		if (
			! is_product()
			&& ! WC_Nbk_Cg_Helper::has_cart_or_checkout_on_current_page()
			&& ! isset( $_GET['pay_for_order'] ) // wpcs: csrf ok.
			&& ! is_add_payment_method_page()
			&& ! isset( $_GET['change_payment_method'] ) // wpcs: csrf ok.
			&& ! ( ! empty( get_query_var( 'view-subscription' ) ) && is_callable( 'WCS_Early_Renewal_Manager::is_early_renewal_via_modal_enabled' ) && WCS_Early_Renewal_Manager::is_early_renewal_via_modal_enabled() )
			|| ( is_order_received_page() )
		) {
			return;
		}

		// If Nbk-cg is not enabled bail.
		if ( 'no' === $this->enabled ) {
			return;
		}

		// If keys are not set bail.
		if ( ! $this->are_keys_set() ) {
			WC_Nbk_Cg_Logger::log( 'Keys are not set correctly.' );
			return;
		}

		// If no SSL bail.
		/*if ( ! $this->testmode && ! is_ssl() ) {
			WC_Nbk_Cg_Logger::log( 'Nbk-cg live mode requires SSL.' );
			return;
		}*/

        wp_register_style( 'nbk-cg-styles-normalize', plugins_url( 'assets/css/stripe/normalize.css', WC_NBK_CG_MAIN_FILE ), [], WC_NBK_CG_VERSION );
        wp_register_style( 'nbk-cg-styles-global', plugins_url( 'assets/css/stripe/global.css', WC_NBK_CG_MAIN_FILE ), [], WC_NBK_CG_VERSION );
		wp_enqueue_style( 'nbk-cg-styles-normalize');
        wp_enqueue_style( 'nbk-cg-styles-global' );

        wp_enqueue_script( '',
            'https://js.stripe.com/v3', array(),
            '3.0',false
        );

        wp_register_script( 'woocommerce_nbk_cg', plugins_url(
               'assets/js/stripe/script.js', WC_NBK_CG_MAIN_FILE ),
            [], WC_NBK_CG_VERSION, false);

        wp_localize_script(
			'woocommerce_nbk_cg',
			'wc_nbk_cg_params',
			apply_filters( 'wc_nbk_cg_params', $this->javascript_params() )
		);

		$this->tokenization_script();
		wp_enqueue_script( 'woocommerce_nbk_cg' );
	}



	/**
	 * Completes an order without a positive value.
	 *
	 * @since 4.2.0
	 * @param WC_Order $order             The order to complete.
	 * @param WC_Order $prepared_source   Payment source and customer data.
	 * @param boolean  $force_save_source Whether the payment source must be saved, like when dealing with a Subscription setup.
	 * @return array                      Redirection data for `process_payment`.
	 */
	public function complete_free_order( $order, $prepared_source, $force_save_source ) {
		if ( $force_save_source ) {
			$intent_secret = $this->setup_intent( $order, $prepared_source );

			if ( ! empty( $intent_secret ) ) {
				// `get_return_url()` must be called immediately before returning a value.
				return [
					'result'              => 'success',
					'redirect'            => $this->get_return_url( $order ),
					'setup_intent_secret' => $intent_secret,
				];
			}
		}

		// Remove cart.
		WC()->cart->empty_cart();

		$order->payment_complete();

		// Return thank you page redirect.
		return [
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		];
	}

    public function webhook() {
        $order = wc_get_order( $_GET['id'] );
        $order->payment_complete();
        $order->reduce_order_stock();

        update_option('webhook_debug', $_GET);
    }



	/**
	 * Process the payment
	 *
	 * @since 1.0.0
	 * @since 4.1.0 Add 4th parameter to track previous error.
	 * @param int  $order_id Reference.
	 * @param bool $retry Should we retry on fail.
	 * @param bool $force_save_source Force save the payment source.
	 * @param mix  $previous_error Any error message from previous request.
	 * @param bool $use_order_source Whether to use the source, which should already be attached to the order.
	 *
	 * @throws Exception If payment will not be accepted.
	 * @return array|void
	 */
	public function process_payment( $order_id, $retry = true, $force_save_source = false, $previous_error = false, $use_order_source = false ) {


	    try {
			$order = wc_get_order( $order_id );


			// ToDo: `process_pre_order` saves the source to the order for a later payment.
			// This might not work well with PaymentIntents.
			if ( $this->maybe_process_pre_orders( $order_id ) ) {
				return $this->pre_orders->process_pre_order( $order_id );
			}


			// This will throw exception if not valid.
			$this->validate_minimum_order_amount( $order );

			WC_Nbk_Cg_Logger::log( "Info: Begin processing payment for order $order_id for the amount of {$order->get_total()}" );

			if ( ! empty( $intent ) ) {
				// Use the last charge within the intent to proceed.
				$response = end( $intent->charges->data );

				// If the intent requires a 3DS flow, redirect to it.
				if ( 'requires_action' === $intent->status ) {
					$this->unlock_order_payment( $order );

					if ( is_wc_endpoint_url( 'order-pay' ) ) {
						$redirect_url = add_query_arg( 'wc-nbk-cg-confirmation', 1, $order->get_checkout_payment_url( false ) );

						return [
							'result'   => 'success',
							'redirect' => $redirect_url,
						];
					} else {
						/**
						 * This URL contains only a hash, which will be sent to `checkout.js` where it will be set like this:
						 * `window.location = result.redirect`
						 * Once this redirect is sent to JS, the `onHashChange` function will execute `handleCardPayment`.
						 */

						return [
							'result'                => 'success',
							'redirect'              => $this->get_return_url( $order ),
							'payment_intent_secret' => $intent->client_secret,
						];
					}
				}
			}

			// Process valid response.
			$this->process_response( $response, $order );

			// Remove cart.
			if ( isset( WC()->cart ) ) {
				WC()->cart->empty_cart();
                $order->payment_complete();
                $order->reduce_order_stock();
			}

			// Unlock the order.
			$this->unlock_order_payment( $order );

			// Return thank you page redirect.
			return [
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			];

		} catch ( WC_Nbk_Cg_Exception $e ) {
			wc_add_notice( $e->getLocalizedMessage(), 'error' );
			WC_Nbk_Cg_Logger::log( 'Error: ' . $e->getMessage() );

			do_action( 'WC_Gateway_Nbk_Cgprocess_payment_error', $e, $order );

			/* translators: error message */
			$order->update_status( 'failed' );

			return [
				'result'   => 'fail',
				'redirect' => '',
			];
		}
	}

	/**
	 * Saves payment method
	 *
	 * @param object $source_object
	 * @throws WC_Nbk_Cg_Exception
	 */
	public function save_payment_method( $source_object ) {
		$user_id  = get_current_user_id();
		$customer = new WC_Nbk_Cg_Customer( $user_id );

		if ( ( $user_id && 'reusable' === $source_object->usage ) ) {
			$response = $customer->add_source( $source_object->id );

			if ( ! empty( $response->error ) ) {
				throw new WC_Nbk_Cg_Exception( print_r( $response, true ), $this->get_localized_error_message_from_response( $response ) );
			}
			if ( is_wp_error( $response ) ) {
				throw new WC_Nbk_Cg_Exception( $response->get_error_message(), $response->get_error_message() );
			}
		}
	}


	/**
	 * Generates a localized message for an error from a response.
	 *
	 * @since 4.3.2
	 *
	 * @param stdClass $response The response from the Nbk-cg API.
	 *
	 * @return string The localized error message.
	 */
	public function get_localized_error_message_from_response( $response ) {
		$localized_messages = WC_Nbk_Cg_Helper::get_localized_messages();

		if ( 'card_error' === $response->error->type ) {
			$localized_message = isset( $localized_messages[ $response->error->code ] ) ? $localized_messages[ $response->error->code ] : $response->error->message;
		} else {
			$localized_message = isset( $localized_messages[ $response->error->type ] ) ? $localized_messages[ $response->error->type ] : $response->error->message;
		}

		return $localized_message;
	}



	/**
	 * Retries the payment process once an error occured.
	 *
	 * @since 4.2.0
	 * @param object   $response          The response from the Nbk-cg API.
	 * @param WC_Order $order             An order that is being paid for.
	 * @param bool     $retry             A flag that indicates whether another retry should be attempted.
	 * @param bool     $force_save_source Force save the payment source.
	 * @param mixed    $previous_error    Any error message from previous request.
	 * @param bool     $use_order_source  Whether to use the source, which should already be attached to the order.
	 * @throws WC_Nbk_Cg_Exception        If the payment is not accepted.
	 * @return array|void
	 */
	public function retry_after_error( $response, $order, $retry, $force_save_source, $previous_error, $use_order_source ) {
		if ( ! $retry ) {
			$localized_message = __( 'Sorry, we are unable to process your payment at this time. Please retry later.', 'woocommerce-gateway-nbk-cg' );
			$order->add_order_note( $localized_message );
			throw new WC_Nbk_Cg_Exception( print_r( $response, true ), $localized_message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.
		}

		// Don't do anymore retries after this.
		if ( 5 <= $this->retry_interval ) {
			return $this->process_payment( $order->get_id(), false, $force_save_source, $response->error, $previous_error );
		}

		sleep( $this->retry_interval );
		$this->retry_interval++;

		return $this->process_payment( $order->get_id(), true, $force_save_source, $response->error, $previous_error, $use_order_source );
	}

	/**
	 * Adds the necessary hooks to modify the "Pay for order" page in order to clean
	 * it up and prepare it for the Nbk-cg PaymentIntents modal to confirm a payment.
	 *
	 * @since 4.2
	 * @param WC_Payment_Gateway[] $gateways A list of all available gateways.
	 * @return WC_Payment_Gateway[]          Either the same list or an empty one in the right conditions.
	 */
	public function prepare_order_pay_page( $gateways ) {
		if ( ! is_wc_endpoint_url( 'order-pay' ) || ! isset( $_GET['wc-nbk-cg-confirmation'] ) ) { // wpcs: csrf ok.
			return $gateways;
		}

		try {
			$this->prepare_intent_for_order_pay_page();
		} catch ( WC_Nbk_Cg_Exception $e ) {
			// Just show the full order pay page if there was a problem preparing the Payment Intent
			return $gateways;
		}

		add_filter( 'woocommerce_checkout_show_terms', '__return_false' );
		add_filter( 'woocommerce_pay_order_button_html', '__return_false' );
		add_filter( 'woocommerce_available_payment_gateways', '__return_empty_array' );
		add_filter( 'woocommerce_no_available_payment_methods_message', [ $this, 'change_no_available_methods_message' ] );
		add_action( 'woocommerce_pay_order_after_submit', [ $this, 'render_payment_intent_inputs' ] );

		return [];
	}

	/**
	 * Changes the text of the "No available methods" message to one that indicates
	 * the need for a PaymentIntent to be confirmed.
	 *
	 * @since 4.2
	 * @return string the new message.
	 */
	public function change_no_available_methods_message() {
		return wpautop( __( "Almost there!\n\nYour order has already been created, the only thing that still needs to be done is for you to authorize the payment with your bank.", 'woocommerce-gateway-nbk-cg' ) );
	}


	/**
	 * Adds an error message wrapper to each saved method.
	 *
	 * @since 4.2.0
	 * @param WC_Payment_Token $token Payment Token.
	 * @return string                 Generated payment method HTML
	 */
	public function get_saved_payment_method_option_html( $token ) {
		$html          = parent::get_saved_payment_method_option_html( $token );
		$error_wrapper = '<div class="nbk-cg-source-errors" role="alert"></div>';

		return preg_replace( '~</(\w+)>\s*$~', "$error_wrapper</$1>", $html );
	}





	/**
	 * Proceed with current request using new login session (to ensure consistent nonce).
	 */
	public function set_cookie_on_current_request( $cookie ) {
		$_COOKIE[ LOGGED_IN_COOKIE ] = $cookie;
	}


	/**
	 * Preserves the "wc-nbk-cg-confirmation" URL parameter so the user can complete the SCA authentication after logging in.
	 *
	 * @param string   $pay_url Current computed checkout URL for the given order.
	 * @param WC_Order $order Order object.
	 *
	 * @return string Checkout URL for the given order.
	 */
	public function get_checkout_payment_url( $pay_url, $order ) {
		global $wp;
		if ( isset( $_GET['wc-nbk-cg-confirmation'] ) && isset( $wp->query_vars['order-pay'] ) && $wp->query_vars['order-pay'] == $order->get_id() ) {
			$pay_url = add_query_arg( 'wc-nbk-cg-confirmation', 1, $pay_url );
		}
		return $pay_url;
	}

	/**
	 * Checks whether new keys are being entered when saving options.
	 */
	public function process_admin_options() {
		// Load all old values before the new settings get saved.
		$old_client_id      = $this->get_option( 'client_id' );
		$old_client_secret           = $this->get_option( 'client_secret' );
		$old_test_client_id = $this->get_option( 'test_client_id' );
		$old_test_client_secret      = $this->get_option( 'test_client_secret' );

		parent::process_admin_options();

		// Load all old values after the new settings have been saved.
		$new_client_id      = $this->get_option( 'client_id' );
		$new_client_secret           = $this->get_option( 'client_secret' );
		$new_test_client_id = $this->get_option( 'test_client_id' );
		$new_test_client_secret      = $this->get_option( 'test_client_secret' );

		// Checks whether a value has transitioned from a non-empty value to a new one.
		$has_changed = function( $old_value, $new_value ) {
			return ! empty( $old_value ) && ( $old_value !== $new_value );
		};

		// Look for updates.
		if (
			$has_changed( $old_client_id, $new_client_id )
			|| $has_changed( $old_client_secret, $new_client_secret )
			|| $has_changed( $old_test_client_id, $new_test_client_id )
			|| $has_changed( $old_test_client_secret, $new_test_client_secret )
		) {
			update_option( 'wc_Nbk_Cg_show_changed_keys_notice', 'yes' );
		}
	}

	public function validate_publishable_key_field( $key, $value ) {
		$value = $this->validate_text_field( $key, $value );
		if ( ! empty( $value ) && ! preg_match( '/^pk_live_/', $value ) ) {
			throw new Exception( __( 'The "Live Publishable Key" should start with "pk_live", enter the correct key.', 'woocommerce-gateway-nbk-cg' ) );
		}
		return $value;
	}

	public function validate_secret_key_field( $key, $value ) {
		$value = $this->validate_text_field( $key, $value );
		if ( ! empty( $value ) && ! preg_match( '/^[rs]k_live_/', $value ) ) {
			throw new Exception( __( 'The "Live Secret Key" should start with "sk_live" or "rk_live", enter the correct key.', 'woocommerce-gateway-nbk-cg' ) );
		}
		return $value;
	}

	public function validate_test_publishable_key_field( $key, $value ) {
		$value = $this->validate_text_field( $key, $value );
		if ( ! empty( $value ) && ! preg_match( '/^pk_test_/', $value ) ) {
			throw new Exception( __( 'The "Test Publishable Key" should start with "pk_test", enter the correct key.', 'woocommerce-gateway-nbk-cg' ) );
		}
		return $value;
	}

	public function validate_test_secret_key_field( $key, $value ) {
		$value = $this->validate_text_field( $key, $value );
		if ( ! empty( $value ) && ! preg_match( '/^[rs]k_test_/', $value ) ) {
			throw new Exception( __( 'The "Test Secret Key" should start with "sk_test" or "rk_test", enter the correct key.', 'woocommerce-gateway-nbk-cg' ) );
		}
		return $value;
	}



}
