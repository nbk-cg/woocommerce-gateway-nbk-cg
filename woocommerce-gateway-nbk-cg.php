<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              www.nbk-cg.com
 * @since             1.0.0
 * @package           Woocommerce_Gateway_Nbk_Cg
 *
 * @wordpress-plugin
 * Plugin Name:       E-Bongo
 * Plugin URI:        nbk-cg.com/woocommerce-gateway-nbk-cg
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Nbk-cg
 * Author URI:        www.nbk-cg.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-gateway-nbk-cg
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */

/**
 * Required minimums and constants
 */
define( 'WC_Gateway_Nbk_CgVERSION', '1.0.0' );
define( 'WC_NBK_CG_VERSION', '1.0.0' ); // WRCS: DEFINED_VERSION.
define( 'WC_NBK_CG_MIN_PHP_VER', '5.6.0' );
define( 'WC_NBK_CG_MIN_WC_VER', '0.0.0' );
define( 'WC_NBK_CG_FUTURE_MIN_WC_VER', '1.0.0' );
define( 'WC_NBK_CG_MAIN_FILE', __FILE__ );
define( 'WC_NBK_CG_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_NBK_CG_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

// phpcs:disable WordPress.Files.FileName

/**
 * WooCommerce fallback notice.
 *
 * @since 4.1.2
 */
function woocommerce_nbk_cg_missing_wc_notice() {
    /* translators: 1. URL link. */
    echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Nbk cg requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-gateway-nbk-cg' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

/**
 * WooCommerce not supported fallback notice.
 *
 * @since 4.4.0
 */
function woocommerce_nbk_cg_wc_not_supported() {
    /* translators: $1. Minimum WooCommerce version. $2. Current WooCommerce version. */
    echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Nbk cg requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is no longer supported.', 'woocommerce-gateway-nbk-cg' ), WC_NBK_CG_MIN_WC_VER, WC_VERSION ) . '</strong></p></div>';
}

function woocommerce_gateway_nbk_cg() {

    static $plugin;

    if ( ! isset( $plugin ) ) {

        class WC_Nbk_Cg {

            /**
             * The *Singleton* instance of this class
             *
             * @var Singleton
             */
            private static $instance;

            /**
             * Returns the *Singleton* instance of this class.
             *
             * @return Singleton The *Singleton* instance.
             */
            public static function get_instance() {
                if ( null === self::$instance ) {
                    self::$instance = new self();
                }
                return self::$instance;
            }

            /**
             * Nbk cg Connect API
             *
             * @var WC_Nbk_Cg_Connect_API
             */
            private $api;

            /**
             * Nbk cg Connect
             *
             * @var WC_Nbk_Cg_Connect
             */
            public $connect;

            /**
             * Nbk cg Payment Request configurations.
             *
             * @var wc_nbk_cg_payment_request
             */
            public $payment_request_configuration;

            /**
             * Private clone method to prevent cloning of the instance of the
             * *Singleton* instance.
             *
             * @return void
             */
            public function __clone() {}

            /**
             * Private unserialize method to prevent unserializing of the *Singleton*
             * instance.
             *
             * @return void
             */
            public function __wakeup() {}

            /**
             * Protected constructor to prevent creating a new instance of the
             * *Singleton* via the `new` operator from outside of this class.
             */
            public function __construct() {
                add_action( 'admin_init', [ $this, 'install' ] );

                $this->init();

                $this->api                           = new WC_Nbk_Cg_Connect_API();
                $this->connect                       = new WC_Nbk_Cg_Connect( $this->api );
                $this->payment_request_configuration = new wc_nbk_cg_payment_request();

                add_action( 'rest_api_init', [ $this, 'register_connect_routes' ] );
            }

            /**
             * Init the plugin after plugins_loaded so environment variables are set.
             *
             * @since 1.0.0
             * @version 5.0.0
             */
            public function init() {
                if ( is_admin() ) {
                    require_once dirname( __FILE__ ) . '/includes/admin/class-wc-nbk-cg-privacy.php';
                }

                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-exception.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-logger.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-helper.php';
                include_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-api.php';
                require_once dirname( __FILE__ ) . '/includes/abstracts/abstract-wc-nbk-cg-payment-gateway.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-webhook-state.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-webhook-handler.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-apple-pay-registration.php';
                require_once dirname( __FILE__ ) . '/includes/compat/class-wc-nbk-cg-pre-orders-compat.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-nbk-cg.php';
                require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-gateway-nbk-cg-alipay.php';
                require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-gateway-nbk-cg-mtn.php';
                require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-gateway-nbk-cg-paypal.php';
                require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-nbk-cg-payment-request.php';
                require_once dirname( __FILE__ ) . '/includes/compat/class-wc-nbk-cg-subs-compat.php';
                require_once dirname( __FILE__ ) . '/includes/compat/class-wc-nbk-cg-woo-compat-utils.php';
                require_once dirname( __FILE__ ) . '/includes/connect/class-wc-nbk-cg-connect.php';
                require_once dirname( __FILE__ ) . '/includes/connect/class-wc-nbk-cg-connect-api.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-order-handler.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-payment-tokens.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-customer.php';
                require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-intent-controller.php';
                require_once dirname( __FILE__ ) . '/includes/admin/class-wc-nbk-cg-inbox-notes.php';

                if ( is_admin() ) {
                    require_once dirname( __FILE__ ) . '/includes/admin/class-wc-nbk-cg-admin-notices.php';
                }

                // REMOVE IN THE FUTURE.
                require_once dirname( __FILE__ ) . '/includes/deprecated/class-wc-nbk-cg-apple-pay.php';

                add_filter( 'woocommerce_payment_gateways', [ $this, 'add_gateways' ] );
                add_filter( 'pre_update_option_woocommerce_Nbk_Cg_settings', [ $this, 'gateway_settings_update' ], 10, 2 );
                add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'plugin_action_links' ] );
                add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

                // Modify emails emails.
                add_filter( 'woocommerce_email_classes', [ $this, 'add_emails' ], 20 );

                if ( version_compare( WC_NBK_CG_VERSION, '0.0.0', '<' ) ) {
                    add_filter( 'woocommerce_get_sections_checkout', [ $this, 'filter_gateway_order_admin' ] );
                }

            }

            /**
             * Updates the plugin version in db
             *
             * @since 3.1.0
             * @version 4.0.0
             */
            public function update_plugin_version() {
                delete_option( 'WC_NBK_CG_VERSION' );
                update_option( 'WC_NBK_CG_VERSION', WC_NBK_CG_VERSION );
            }

            /**
             * Handles upgrade routines.
             *
             * @since 3.1.0
             * @version 3.1.0
             */
            public function install() {
                if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
                    return;
                }

                if ( ! defined( 'IFRAME_REQUEST' ) && ( WC_NBK_CG_VERSION !== get_option( 'WC_NBK_CG_VERSION' ) ) ) {
                    do_action( 'woocommerce_Nbk_Cg_updated' );

                    if ( ! defined( 'WC_Nbk_Cg_INSTALLING' ) ) {
                        define( 'WC_Nbk_Cg_INSTALLING', true );
                    }

                    add_woocommerce_inbox_variant();
                    $this->update_plugin_version();
                }
            }

            /**
             * Add plugin action links.
             *
             * @since 1.0.0
             * @version 4.0.0
             */
            public function plugin_action_links( $links ) {
                $plugin_links = [
                    '<a href="admin.php?page=wc-settings&tab=checkout&section=nbk_cg">' . esc_html__( 'Settings', 'woocommerce-gateway-nbk-cg' ) . '</a>',
                ];
                return array_merge( $plugin_links, $links );
            }

            /**
             * Add plugin action links.
             *
             * @since 4.3.4
             * @param  array  $links Original list of plugin links.
             * @param  string $file  Name of current file.
             * @return array  $links Update list of plugin links.
             */
            public function plugin_row_meta( $links, $file ) {
                if ( plugin_basename( __FILE__ ) === $file ) {
                    $row_meta = [
                        'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_gateway_Nbk_Cg_docs_url', 'https://docs.woocommerce.com/document/nbk-cg/' ) ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-gateway-nbk-cg' ) ) . '">' . __( 'Docs', 'woocommerce-gateway-nbk-cg' ) . '</a>',
                        'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_gateway_Nbk_Cg_support_url', 'https://woocommerce.com/my-account/create-a-ticket?select=18627' ) ) . '" title="' . esc_attr( __( 'Open a support request at WooCommerce.com', 'woocommerce-gateway-nbk-cg' ) ) . '">' . __( 'Support', 'woocommerce-gateway-nbk-cg' ) . '</a>',
                    ];
                    return array_merge( $links, $row_meta );
                }
                return (array) $links;
            }

            /**
             * Add the gateways to WooCommerce.
             *
             * @since 1.0.0
             * @version 4.0.0
             */
            public function add_gateways( $methods ) {
                if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
                    $methods[] = 'WC_Nbk_Cg_Subs_Compat';
                } else {
                    $methods[] = 'WC_Gateway_nbk_cg';
                }

                $methods[] = 'WC_Gateway_Nbk_Cg_Alipay';
                $methods[] = 'WC_Gateway_Nbk_Cg_Mtn';
                $methods[] = 'WC_Gateway_Nbk_Cg_Paypal';

                return $methods;
            }

            /**
             * Modifies the order of the gateways displayed in admin.
             *
             * @since 4.0.0
             * @version 4.0.0
             */
            public function filter_gateway_order_admin( $sections ) {
                unset( $sections['nbk_cg'] );
                unset( $sections['nbk_cg_alipay'] );
                unset( $sections['nbk_cg_mtn'] );
                unset( $sections['nbk_cg_paypal'] );


                $sections['nbk_cg']             = 'Nbk Cg';
                $sections['nbk_cg_alipay']      = __( 'Nbk Cg Alipay', 'woocommerce-gateway-nbk-cg' );
                $sections['nbk_cg_mtn']         = __( 'Nbk Cg MTN', 'woocommerce-gateway-nbk-cg' );
                $sections['nbk_cg_paypal']      = __( 'Nbk Cg Paypal', 'woocommerce-gateway-nbk-cg' );

                return $sections;
            }

            /**
             * Provide default values for missing settings on initial gateway settings save.
             *
             * @since 4.5.4
             * @version 4.5.4
             *
             * @param array      $settings New settings to save.
             * @param array|bool $old_settings Existing settings, if any.
             * @return array New value but with defaults initially filled in for missing settings.
             */
            public function gateway_settings_update( $settings, $old_settings ) {
                if ( false === $old_settings ) {
                    $gateway  = new WC_Gateway_nbk_cg();
                    $fields   = $gateway->get_form_fields();
                    $defaults = array_merge( array_fill_keys( array_keys( $fields ), '' ), wp_list_pluck( $fields, 'default' ) );
                    return array_merge( $defaults, $settings );
                }
                return $settings;
            }

            /**
             * Adds the failed SCA auth email to WooCommerce.
             *
             * @param WC_Email[] $email_classes All existing emails.
             * @return WC_Email[]
             */
            public function add_emails( $email_classes ) {
                require_once WC_NBK_CG_PLUGIN_PATH . '/includes/compat/class-wc-nbk-cg-email-failed-authentication.php';
                require_once WC_NBK_CG_PLUGIN_PATH . '/includes/compat/class-wc-nbk-cg-email-failed-renewal-authentication.php';
                require_once WC_NBK_CG_PLUGIN_PATH . '/includes/compat/class-wc-nbk-cg-email-failed-preorder-authentication.php';
                require_once WC_NBK_CG_PLUGIN_PATH . '/includes/compat/class-wc-nbk-cg-email-failed-authentication-retry.php';

                // Add all emails, generated by the gateway.
                $email_classes['WC_Nbk_Cg_Email_Failed_Renewal_Authentication']  = new WC_Nbk_Cg_Email_Failed_Renewal_Authentication( $email_classes );
                $email_classes['WC_Nbk_Cg_Email_Failed_Preorder_Authentication'] = new WC_Nbk_Cg_Email_Failed_Preorder_Authentication( $email_classes );
                $email_classes['WC_Nbk_Cg_Email_Failed_Authentication_Retry']    = new WC_Nbk_Cg_Email_Failed_Authentication_Retry( $email_classes );

                return $email_classes;
            }

            /**
             * Register Nbk Cg connect rest routes.
             */
            public function register_connect_routes() {

                require_once WC_NBK_CG_PLUGIN_PATH . '/includes/abstracts/abstract-wc-nbk-cg-connect-rest-controller.php';
                require_once WC_NBK_CG_PLUGIN_PATH . '/includes/connect/class-wc-nbk-cg-connect-rest-oauth-init-controller.php';
                require_once WC_NBK_CG_PLUGIN_PATH . '/includes/connect/class-wc-nbk-cg-connect-rest-oauth-connect-controller.php';

                $oauth_init    = new WC_Nbk_Cg_Connect_REST_Oauth_Init_Controller( $this->connect, $this->api );
                $oauth_connect = new WC_Nbk_Cg_Connect_REST_Oauth_Connect_Controller( $this->connect, $this->api );

                $oauth_init->register_routes();
                $oauth_connect->register_routes();
            }
        }

        $plugin = WC_nbk_cg::get_instance();

    }

    return $plugin;
}

add_action( 'plugins_loaded', 'woocommerce_gateway_Nbk_Cg_init' );

function woocommerce_gateway_Nbk_Cg_init() {
    load_plugin_textdomain( 'woocommerce-gateway-nbk-cg', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'woocommerce_Nbk_Cg_missing_wc_notice' );
        return;
    }

    if ( version_compare( WC_NBK_CG_VERSION, WC_NBK_CG_MIN_WC_VER, '<' ) ) {
        add_action( 'admin_notices', 'woocommerce_Nbk_Cg_wc_not_supported' );
        return;
    }

    woocommerce_gateway_nbk_cg();
}

/**
 * Add woocommerce_inbox_variant for the Remote Inbox Notification.
 *
 * P2 post can be found at https://wp.me/paJDYF-1uJ.
 */
if ( ! function_exists( 'add_woocommerce_inbox_variant' ) ) {
    function add_woocommerce_inbox_variant() {
        $config_name = 'woocommerce_inbox_variant_assignment';
        if ( false === get_option( $config_name, false ) ) {
            update_option( $config_name, wp_rand( 1, 12 ) );
        }
    }
}
register_activation_hook( __FILE__, 'add_woocommerce_inbox_variant' );

// Hook in Blocks integration. This action is called in a callback on plugins loaded, so current Nbk Cg plugin class
// implementation is too late.
add_action( 'woocommerce_blocks_loaded', 'woocommerce_gateway_Nbk_Cg_woocommerce_block_support' );

function woocommerce_gateway_Nbk_Cg_woocommerce_block_support() {
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        require_once dirname( __FILE__ ) . '/includes/class-wc-nbk-cg-blocks-support.php';
        // priority is important here because this ensures this integration is
        // registered before the WooCommerce Blocks built-in Nbk Cg registration.
        // Blocks code has a check in place to only register if 'Nbk Cg' is not
        // already registered.
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $container = Automattic\WooCommerce\Blocks\Package::container();
                // registers as shared instance.
                $container->register(
                    WC_Nbk_Cg_Blocks_Support::class,
                    function() {
                        if ( class_exists( 'WC_nbk_cg' ) ) {
                            return new WC_Nbk_Cg_Blocks_Support( WC_nbk_cg::get_instance()->payment_request_configuration );
                        } else {
                            return new WC_Nbk_Cg_Blocks_Support();
                        }
                    }
                );
                $payment_method_registry->register(
                    $container->get( WC_Nbk_Cg_Blocks_Support::class )
                );
            },
            5
        );
    }
}

