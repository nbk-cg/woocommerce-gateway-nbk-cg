<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Nbk_Cg_API class.
 *
 * Communicates with Nbk-Cg API.
 */
class WC_Nbk_Cg_API {

	/**
	 * Nbk-Cg API Endpoint
	 */
	//const ENDPOINT           = 'https://wallet-payment-svc-x6fr3lwlgq-nw.a.run.app';
    const CARD_ENDPOINT      = 'https://wallet-payment-svc-x6fr3lwlgq-nw.a.run.app';
    const MOBILE_ENDPOINT    = 'https://mobile-payment-svc-x6fr3lwlgq-ew.a.run.app';
	const NBK_CG_API_VERSION = '2019-09-09';

	const MOBILE_PAYMENT =  ['MTN', 'ORANGE-MONEY', 'ALIPAY'];
    const CARD_PAYMENT =  ['STRIPE', 'PAYPAL'];

	const TOKEN_URL = "https://nbk-wallet.auth.eu-west-1.amazoncognito.com/oauth2/token";

	/**
	 * Secret API Key.
	 *
	 * @var strings
	 */
	private static $secret_key = '';

    /**
     * Client Id .
     *
     * @var string
     */
    private static $client_id = '';

    /**
     * Client Secret .
     *
     * @var string
     */
    private static $client_secret = '';


    /**
     * Client Secret .
     *
     * @var string
     */
    private static $access_token = '';


    /**
     * form.
     *
     * @var string
     */
    private static $form = '';



    /**
	 * Set secret API Key.
	 *
	 * @param string $key
	 */
	public static function set_secret_key( $secret_key ) {
		self::$secret_key = $secret_key;
	}


    /**
     * Set Client Id.
     *
     * @param string $client_id
     */
    public static function set_client_id( $client_id ) {

        self::$client_id = $client_id;
    }

    /**
     * Set Client Secret.
     *
     * @param string $client_secret
     */
    public static function set_client_secret( $client_secret ) {
        self::$client_secret = $client_secret;
    }


    /**
     * Set Client Secret.
     *
     * @param string $client_secret
     */
    public static function set_access_token( ) {

        $fields = array(
            'grant_type'	=> 'client_credentials',
            'client_id'     => self::get_client_id(),
            'client_secret' => self::get_client_secret()
        );

       // $field_string = json_encode( $fields );

        $headers = array( 'Content-Type' => 'application/x-www-form-urlencoded', 'charset' => 'UTF - 8');

        $args = array(
            'method' =>'POST',
            'body' => $fields,
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,

        );

        $response = wp_remote_post( self::TOKEN_URL, $args );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
            exit();
        }

        $responseData =  wp_remote_retrieve_body($response);

        $responseData = json_decode($responseData, true);

        //var_dump($responseData['access_token']); die();

        self::$access_token = $responseData['access_token'];
    }

    /**
     * @param $params
     * @return mixed|string
     */
    public static function request_cash_in($fields, $url)
    {
        $args = array(
            'method' =>'POST',
            'body' => wp_json_encode($fields, true),
            'data_format' => 'body',
            'timeout' => '45',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array( 'Content-Type' => 'application/json',
                'Authorization' => sprintf(
                    'Bearer %s',
                    self::get_access_token()
                )
            ),
        );

        $response = wp_remote_post(
            $url,
            $args
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
            exit();
        }

        $responseData =  wp_remote_retrieve_body($response);

        $responseData = json_decode($responseData, true);

        return $responseData;
    }


    public static function request_cash_in_transaction($url)
    {
        $response = wp_remote_get(
            $url
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
            exit();
        }

        $responseData =  wp_remote_retrieve_body($response);

        $responseData = json_decode($responseData, true);

        return $responseData;
    }


    /**
     * @param $params
     * @return mixed|string
     */
    public static function execute_payment($fields, $account_id) {

        /*$fields = array(
            'amount'	                => $params['amount'],
            'currency'                  => $params['currency'],
            'originator'                => [
                'originatorType'  => "User",
                'originatorId'    => $params['originatorId']
            ],
            'description'               => $params['description'],
            'regions'                   => $params['regions'],
            'applicationContext'        =>  [
                'successUrl'        => $params['successUrl'],
                'cancelOrFailUrl'   => $params['cancelOrFailUrl']
            ]
        );*/


        $headers = array( 'Content-Type' => 'application/json',
            'Authorization' => sprintf(
                'Bearer %s',
                self::get_access_token()
            )
        );

        $args = array(
            'method' =>'POST',
            'body' => wp_json_encode($fields, true),
            'data_format' => 'body',
            'timeout' => '45',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,

        );

        $response = wp_remote_post(
            sprintf(
                '%s/v1/paypal/payments/execute/%s',
                self::CARD_ENDPOINT,
                $account_id
            ),
            $args
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
            exit();
        }

        $responseData =  wp_remote_retrieve_body($response);

        $responseData = json_decode($responseData, true);

        return $responseData;

    }


    /**
	 * Get secret key.
	 *
	 * @return string
	 */
	public static function get_secret_key() {
		if ( ! self::$secret_key ) {
			$options = get_option( 'woocommerce_nbk_cg_settings' );

			if ( isset( $options['testmode'], $options['secret_key'], $options['test_secret_key'] ) ) {
				self::set_secret_key( 'yes' === $options['testmode'] ? $options['test_secret_key'] : $options['secret_key'] );
			}
		}
		return self::$secret_key;
	}

    /**
     * Get secret key.
     *
     * @return string
     */
    public static function get_token() {
        self::set_access_token();

        return  self::get_access_token();
    }


    /**
     * Get secret key.
     *
     * @return string
     */
    public static function get_client_id() {

        if ( ! self::$client_id ) {
            $options = get_option( 'woocommerce_nbk_cg_settings' );

            if ( isset( $options['testmode'], $options['client_id'], $options['test_client_id'] ) ) {
                self::set_client_id( 'yes' === $options['testmode'] ? $options['test_client_id'] : $options['client_id'] );
            }
        }
        return self::$client_id;
    }


    /**
     * Get secret key.
     *
     * @return string
     */
    public static function get_client_secret() {
        if ( ! self::$client_secret ) {
            $options = get_option( 'woocommerce_nbk_cg_settings' );

            if ( isset( $options['testmode'], $options['client_secret'], $options['test_client_secret'] ) ) {
                self::set_client_secret( 'yes' === $options['testmode'] ? $options['test_client_secret'] : $options['client_secret'] );
            }
        }
        return self::$client_secret;
    }


    /**
     * Get secret key.
     *
     * @return string
     */
    public static function get_access_token()
    {
        return self::$access_token;
    }



	/**
	 * Generates the user agent we use to pass to API request so
	 * Nbk-Cg can identify our application.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public static function get_user_agent() {
		$app_info = [
			'name'       => 'WooCommerce Nbk-cg Gateway',
			'version'    =>  WC_NBK_CG_VERSION,
			'url'        => 'https://woocommerce.com/products/Nbk-Cg/',
			'partner_id' => 'pp_partner_EYuSt9peR0WTMg',
		];

		return [
			'lang'         => 'php',
			'lang_version' => phpversion(),
			'publisher'    => 'woocommerce',
			'uname'        => php_uname(),
			'application'  => $app_info,
		];
	}

	/**
	 * Generates the headers to pass to API request.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public static function get_headers() {
		$user_agent = self::get_user_agent();
		$app_info   = $user_agent['application'];


		$headers = apply_filters(
			'woocommerce_Nbk_Cg_request_headers',
			[
                'Authorization' => sprintf(
                    'Bearer %s',
                    self::get_access_token()
                ),
				'nbk-cg-Version' => self::NBK_CG_API_VERSION,
			]
		);

		// These headers should not be overridden for this gateway.
		$headers['User-Agent']                 = $app_info['name'] . '/' . $app_info['version'] . ' (' . $app_info['url'] . ')';
		$headers['X-Nbk-Cg-Client-User-Agent'] = wp_json_encode( $user_agent );

		return $headers;
	}

	/**
	 * Send the request to Nbk-Cg's API
	 *
	 * @since 3.1.0
	 * @version 4.0.6
	 * @param array  $request
	 * @param string $api
	 * @param string $method
	 * @param bool   $with_headers To get the response with headers.
	 * @return stdClass|array
	 * @throws WC_Nbk_Cg_Exception
	 */
	public static function request( $request, $api = 'charges', $method = 'POST', $with_headers = false ) {
		WC_Nbk_Cg_Logger::log( "{$api} request: " . print_r( $request, true ) );

		$headers         = self::get_headers();


		$idempotency_key = '';

		if ( 'charges' === $api && 'POST' === $method ) {
			$customer        = ! empty( $request['customer'] ) ? $request['customer'] : '';
			$source          = ! empty( $request['source'] ) ? $request['source'] : $customer;
			$idempotency_key = apply_filters( 'wc_Nbk_Cg_idempotency_key', $request['metadata']['order_id'] . '-' . $source, $request );

			$headers['Idempotency-Key'] = $idempotency_key;
		}

		$response = wp_safe_remote_post(
			self::CARD_ENDPOINT .'/'. $api,
			[
				'method'  => $method,
				'headers' => $headers,
				'body'    => apply_filters( 'woocommerce_Nbk_Cg_request_body', $request, $api ),
				'timeout' => 70,
			]
		);

		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			WC_Nbk_Cg_Logger::log(
				'Error Response: ' . print_r( $response, true ) . PHP_EOL . PHP_EOL . 'Failed request: ' . print_r(
					[
						'api'             => $api,
						'request'         => $request,
						'idempotency_key' => $idempotency_key,
					],
					true
				)
			);

			throw new WC_Nbk_Cg_Exception( print_r( $response, true ), __( 'There was a problem connecting to the Nbk-Cg API endpoint.', 'woocommerce-gateway-nbk-cg' ) );
		}

		if ( $with_headers ) {
			return [
				'headers' => wp_remote_retrieve_headers( $response ),
				'body'    => json_decode( $response['body'] ),
			];
		}

		return json_decode( $response['body'] );
	}

	/**
	 * Retrieve API endpoint.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param string $api
	 */
	public static function retrieve( $api ) {
		WC_Nbk_Cg_Logger::log( "{$api}" );

		$response = wp_safe_remote_get(
			self::CARD_ENDPOINT . $api,
			[
				'method'  => 'GET',
				'headers' => self::get_headers(),
				'timeout' => 70,
			]
		);

		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			WC_Nbk_Cg_Logger::log( 'Error Response: ' . print_r( $response, true ) );
			return new WP_Error( 'stripe_error', __( 'There was a problem connecting to the Nbk-Cg API endpoint.', 'woocommerce-gateway-nbk-cg' ) );
		}

		return json_decode( $response['body'] );
	}

	/**
	 * Send the request to Nbk-Cg's API with level 3 data generated
	 * from the order. If the request fails due to an error related
	 * to level3 data, make the request again without it to allow
	 * the payment to go through.
	 *
	 * @since 4.3.2
	 * @version 5.1.0
	 *
	 * @param array    $request     Array with request parameters.
	 * @param string   $api         The API path for the request.
	 * @param array    $level3_data The level 3 data for this request.
	 * @param WC_Order $order       The order associated with the payment.
	 *
	 * @return stdClass|array The response
	 */
	public static function request_with_level3_data( $request, $api, $level3_data, $order ) {
		// 1. Do not add level3 data if the array is empty.
		// 2. Do not add level3 data if there's a transient indicating that level3 was
		// not accepted by Nbk-Cg in the past for this account.
		// 3. Do not try to add level3 data if merchant is not based in the US.
		// https://Nbk-Cg.com/docs/level3#level-iii-usage-requirements
		// (Needs to be authenticated with a level3 gated account to see above docs).
		if (
			empty( $level3_data ) ||
			get_transient( 'wc_Nbk_Cg_level3_not_allowed' ) ||
			'US' !== WC()->countries->get_base_country()
		) {
			return self::request(
				$request,
				$api
			);
		}

		// Add level 3 data to the request.
		$request['level3'] = $level3_data;

		$result = self::request(
			$request,
			$api
		);

		$is_level3_param_not_allowed = (
			isset( $result->error )
			&& isset( $result->error->code )
			&& 'parameter_unknown' === $result->error->code
			&& isset( $result->error->param )
			&& 'level3' === $result->error->param
		);

		$is_level_3data_incorrect = (
			isset( $result->error )
			&& isset( $result->error->type )
			&& 'invalid_request_error' === $result->error->type
		);

		if ( $is_level3_param_not_allowed ) {
			// Set a transient so that future requests do not add level 3 data.
			// Transient is set to expire in 3 months, can be manually removed if needed.
			set_transient( 'wc_Nbk_Cg_level3_not_allowed', true, 3 * MONTH_IN_SECONDS );
		} elseif ( $is_level_3data_incorrect ) {
			// Log the issue so we could debug it.
			WC_Nbk_Cg_Logger::log(
				'Level3 data sum incorrect: ' . PHP_EOL
				. print_r( $result->error->message, true ) . PHP_EOL
				. print_r( 'Order line items: ', true ) . PHP_EOL
				. print_r( $order->get_items(), true ) . PHP_EOL
				. print_r( 'Order shipping amount: ', true ) . PHP_EOL
				. print_r( $order->get_shipping_total(), true ) . PHP_EOL
				. print_r( 'Order currency: ', true ) . PHP_EOL
				. print_r( $order->get_currency(), true )
			);
		}

		// Make the request again without level 3 data.
		if ( $is_level3_param_not_allowed || $is_level_3data_incorrect ) {
			unset( $request['level3'] );
			return self::request(
				$request,
				$api
			);
		}

		return $result;
	}
}
