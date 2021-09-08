<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that represents admin notices.
 *
 * @since 4.1.0
 */
class WC_Nbk_Cg_Admin_Notices {
	/**
	 * Notices (array)
	 *
	 * @var array
	 */
	public $notices = [];

	/**
	 * Constructor
	 *
	 * @since 4.1.0
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
		add_action( 'wp_loaded', [ $this, 'hide_notices' ] );
		add_action( 'woocommerce_Nbk_Cg_updated', [ $this, 'stripe_updated' ] );
	}

	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 */
	public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
		$this->notices[ $slug ] = [
			'class'       => $class,
			'message'     => $message,
			'dismissible' => $dismissible,
		];
	}

	/**
	 * Display any notices we've collected thus far.
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Main Nbk-CG payment method.
		$this->stripe_check_environment();

		// All other payment methods.
		$this->payment_methods_check_environment();

		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo '<div class="' . esc_attr( $notice['class'] ) . '" style="position:relative;">';

			if ( $notice['dismissible'] ) {
				?>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-nbk-cg-hide-notice', $notice_key ), 'wc_nbk_cg_hide_notices_nonce', '_wc_nbk_cg_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0px 9px 9px 9px;text-decoration:none;"></a>
				<?php
			}

			echo '<p>';
			echo wp_kses(
				$notice['message'],
				[
					'a' => [
						'href'   => [],
						'target' => [],
					],
				]
			);
			echo '</p></div>';
		}
	}

	/**
	 * List of available payment methods.
	 *
	 * @since 4.1.0
	 * @return array
	 */
	public function get_payment_methods() {
		return [
			'Alipay'     => 'WC_Gateway_Nbk_Cg_Alipay',
            'Mtn'        => 'WC_Gateway_Nbk_Cg_Mtn',
            'Paypal'     => 'WC_Gateway_Nbk_Cg_Paypal'
		];
	}

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation. Also handles upgrade routines.
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 */
	public function stripe_check_environment() {
		$show_style_notice   = get_option( 'wc_nbk_cg_show_style_notice' );
		$show_ssl_notice     = get_option( 'wc_nbk_cg_show_ssl_notice' );
		$show_keys_notice    = get_option( 'wc_nbk_cg_show_keys_notice' );
		$show_3ds_notice     = get_option( 'wc_nbk_cg_show_3ds_notice' );
		$show_phpver_notice  = get_option( 'wc_nbk_cg_show_phpver_notice' );
		$show_wcver_notice   = get_option( 'wc_nbk_cg_show_wcver_notice' );
		$show_curl_notice    = get_option( 'wc_nbk_cg_show_curl_notice' );
		$show_sca_notice     = get_option( 'wc_nbk_cg_show_sca_notice' );
		$changed_keys_notice = get_option( 'wc_nbk_cg_show_changed_keys_notice' );
		$options             = get_option( 'woocommerce_nbk_cg_settings' );

		//var_dump($options);die();
		$testmode            = ( isset( $options['testmode'] ) && 'yes' === $options['testmode'] ) ? true : false;
        $test_client_id        = isset( $options['test_client_id'] ) ? $options['test_client_id'] : '';
        $test_client_secret     = isset( $options['test_client_secret'] ) ? $options['test_client_secret'] : '';
        $live_client_id        = isset( $options['client_id'] ) ? $options['client_id'] : '';
        $live_client_secret     = isset( $options['client_secret'] ) ? $options['client_secret'] : '';
        $three_d_secure      = isset( $options['three_d_secure'] ) && 'yes' === $options['three_d_secure'];

		if ( isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {
			/*if ( empty( $show_3ds_notice ) && $three_d_secure ) {
				$url = 'https://stripe.com/docs/payments/3d-secure#three-ds-radar';

				// translators: 1) A URL that explains Nbk-CG Radar.
				$message = __( 'WooCommerce Nbk-CG - We see that you had the "Require 3D secure when applicable" setting turned on. This setting is not available here anymore, because it is now replaced by Nbk-CG Radar. You can learn more about it <a href="%s" target="_blank">here</a>.', 'woocommerce-gateway-nbk-cg' );

				$this->add_admin_notice( '3ds', 'notice notice-warning', sprintf( $message, $url ), true );
			}*/

			if ( empty( $show_style_notice ) ) {
				/* translators: 1) int version 2) int version */
				$message = __( 'WooCommerce Nbk-CG - We recently made changes to Nbk-CG that may impact the appearance of your checkout. If your checkout has changed unexpectedly, please follow these <a href="https://docs.woocommerce.com/document/Nbk-CG/#styling" target="_blank">instructions</a> to fix.', 'woocommerce-gateway-nbk-cg' );

				$this->add_admin_notice( 'style', 'notice notice-warning', $message, true );

				return;
			}

			if ( empty( $show_phpver_notice ) ) {
				if ( version_compare( phpversion(), WC_NBK_CG_MIN_PHP_VER, '<' ) ) {
					/* translators: 1) int version 2) int version */
					$message = __( 'WooCommerce Nbk-CG - The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-nbk-cg' );

					$this->add_admin_notice( 'phpver', 'error', sprintf( $message, WC_NBK_CG_MIN_PHP_VER, phpversion() ), true );

					return;
				}
			}

			if ( empty( $show_wcver_notice ) ) {
				if ( WC_Nbk_Cg_Helper::is_wc_lt( WC_NBK_CG_FUTURE_MIN_WC_VER ) ) {
					/* translators: 1) int version 2) int version */
					$message = __( 'WooCommerce Nbk-CG - This is the last version of the plugin compatible with WooCommerce %1$s. All furture versions of the plugin will require WooCommerce %2$s or greater.', 'woocommerce-gateway-nbk-cg' );
					$this->add_admin_notice( 'wcver', 'notice notice-warning', sprintf( $message, WC_NBK_CG_VERSION, WC_NBK_CG_FUTURE_MIN_WC_VER ), true );
				}
			}

			if ( empty( $show_curl_notice ) ) {
				if ( ! function_exists( 'curl_init' ) ) {
					$this->add_admin_notice( 'curl', 'notice notice-warning', __( 'WooCommerce Nbk-CG - cURL is not installed.', 'woocommerce-gateway-nbk-cg' ), true );
				}
			}

			//if ( empty( $show_keys_notice ) ) {
				$secret = WC_Nbk_Cg_API::get_client_secret();

				if ( empty( $secret ) && ! ( isset( $_GET['page'], $_GET['section'] ) && 'wc-settings' === $_GET['page'] && 'nbk_cg' === $_GET['section'] ) ) {
					$setting_link = $this->get_setting_link();
					/* translators: 1) link */
					$this->add_admin_notice( 'keys', 'notice notice-warning', sprintf( __( 'Nbk-CG is almost ready. To get started, <a href="%s">set your Nbk-CG account keys</a>.', 'woocommerce-gateway-nbk-cg' ), $setting_link ), true );
				}

				// Check if keys are entered properly per live/test mode.
				if ( $testmode ) {
					if (
						 empty( $test_client_id ) /* && ! preg_match( '/^client_id_test_/', $test_client_id )*/
						|| empty( $test_client_secret ) /*&& ! preg_match( '/^[rs]secret_k_test_/', $test_secret_key )*/ ) {
					    //die('vvvv');
						$setting_link = $this->get_setting_link();
						/* translators: 1) link */
						$this->add_admin_notice( 'keys', 'notice notice-error', sprintf( __( 'Nbk-CG is in test mode however your test keys may not be valid. Test keys start with pk_test and sk_test or rk_test. Please go to your settings and, <a href="%s">set your Nbk-CG account keys</a>.', 'woocommerce-gateway-nbk-cg' ), $setting_link ), true );
					}
				} else {
					if (
						! empty( $live_client_id ) /*&& ! preg_match( '/^pk_live_/', $live_client_id )*/
						|| ! empty( $live_client_secret ) /*&& ! preg_match( '/^[rs]k_live_/', $live_secret_key )*/ ) {
						$setting_link = $this->get_setting_link();
						/* translators: 1) link */
						$this->add_admin_notice( 'keys', 'notice notice-error', sprintf( __( 'Nbk-CG is in live mode however your live keys may not be valid. Live keys start with pk_live and sk_live or rk_live. Please go to your settings and, <a href="%s">set your Nbk-CG account keys</a>.', 'woocommerce-gateway-nbk-cg' ), $setting_link ), true );
					}
				}
			//}

			/*if ( empty( $show_ssl_notice ) ) {
				// Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected.
				if ( ! wc_checkout_is_https() ) {
					 //translators: 1) link
					$this->add_admin_notice( 'ssl', 'notice notice-warning', sprintf( __( 'Nbk-CG is enabled, but a SSL certificate is not detected. Your checkout may not be secure! Please ensure your server has a valid <a href="%1$s" target="_blank">SSL certificate</a>', 'woocommerce-gateway-nbk-cg' ), 'https://en.wikipedia.org/wiki/Transport_Layer_Security' ), true );
				}
			}*/

			if ( empty( $show_sca_notice ) ) {
				/* translators: %1 is the URL for the link */
				$this->add_admin_notice( 'sca', 'notice notice-success', sprintf( __( 'Nbk-CG is now ready for Strong Customer Authentication (SCA) and 3D Secure 2! <a href="%1$s" target="_blank">Read about SCA</a>', 'woocommerce-gateway-nbk-cg' ), 'https://woocommerce.com/posts/introducing-strong-customer-authentication-sca/' ), true );
			}

			if ( 'yes' === $changed_keys_notice ) {
				// translators: %s is a the URL for the link.
				$this->add_admin_notice( 'changed_keys', 'notice notice-warning', sprintf( __( 'The public and/or secret keys for the Nbk-CG gateway have been changed. This might cause errors for existing customers and saved payment methods. <a href="%s" target="_blank">Click here to learn more</a>.', 'woocommerce-gateway-nbk-cg' ), 'https://docs.woocommerce.com/document/nbk-cg-fixing-customer-errors/' ), true );
			}
		}
	}

	/**
	 * Environment check for all other payment methods.
	 *
	 * @since 4.1.0
	 */
	public function payment_methods_check_environment() {
		$payment_methods = $this->get_payment_methods();

		foreach ( $payment_methods as $method => $class ) {
			$show_notice = get_option( 'wc_nbk_cg_show_' . strtolower( $method ) . '_notice' );
			$gateway     = new $class();

			if ( 'yes' !== $gateway->enabled || 'no' === $show_notice ) {
				continue;
			}

			if ( ! in_array( get_woocommerce_currency(), $gateway->get_supported_currency() ) ) {

				/* translators: %1$s Payment method, %2$s List of supported currencies */
				$this->add_admin_notice( $method, 'notice notice-error', sprintf( __( '%1$s is enabled - it requires store currency to be set to %2$s', 'woocommerce-gateway-nbk-cg' ), $method, implode( ', ', $gateway->get_supported_currency() ) ), true );
			}
		}
	}

	/**
	 * Hides any admin notices.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public function hide_notices() {
		if ( isset( $_GET['wc-nbk-cg-hide-notice'] ) && isset( $_GET['_wc_Nbk_Cg_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( wc_clean( wp_unslash( $_GET['_wc_nbk_cg_notice_nonce'] ) ), 'wc_nbk_cg_hide_notices_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-gateway-nbk-cg' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-gateway-nbk-cg' ) );
			}

			$notice = wc_clean( wp_unslash( $_GET['wc-nbk-cg-hide-notice'] ) );

			switch ( $notice ) {
				case 'style':
					update_option( 'wc_nbk_ng_show_style_notice', 'no' );
					break;
				case 'phpver':
					update_option( 'wc_nbk_cg_show_phpver_notice', 'no' );
					break;
				case 'wcver':
					update_option( 'wc_nbk_cg_show_wcver_notice', 'no' );
					break;
				case 'curl':
					update_option( 'wc_nbk_cg_show_curl_notice', 'no' );
					break;
				case 'ssl':
					update_option( 'wc_nbk_cg_show_ssl_notice', 'no' );
					break;
				case 'keys':
					update_option( 'wc_nbk_cg_show_keys_notice', 'no' );
					break;
				case '3ds':
					update_option( 'wc_nbk_cg_show_3ds_notice', 'no' );
					break;
				case 'Alipay':
					update_option( 'wc_nbk_cg_show_alipay_notice', 'no' );
					break;
                case 'MTN':
                    update_option( 'wc_nbk_cg_show_mtn_notice', 'no' );
                    break;
                case 'Paypal':
                    update_option( 'wc_nbk_cg_show_paypal_notice', 'no' );
                    break;
			}
		}
	}

	/**
	 * Get setting link.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting link
	 */
	public function get_setting_link() {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nbk_cg' );
	}

	/**
	 * Saves options in order to hide notices based on the gateway's version.
	 *
	 * @since 4.3.0
	 */
	public function stripe_updated() {
		$previous_version = get_option( 'WC_NBK_CG_VERSION' );

		// Only show the style notice if the plugin was installed and older than 4.1.4.
		if ( empty( $previous_version ) || version_compare( $previous_version, '4.1.4', 'ge' ) ) {
			update_option( 'wc_nbk_cg_show_style_notice', 'no' );
		}

		// Only show the SCA notice on pre-4.3.0 installs.
		if ( empty( $previous_version ) || version_compare( $previous_version, '4.3.0', 'ge' ) ) {
			update_option( 'wc_nbk_cg_show_sca_notice', 'no' );
		}
	}
}

new WC_Nbk_Cg_Admin_Notices();
