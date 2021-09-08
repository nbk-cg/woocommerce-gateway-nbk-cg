<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       www.nbk-cg.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Gateway_Nbk_Cg
 * @subpackage Woocommerce_Gateway_Nbk_Cg/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woocommerce_Gateway_Nbk_Cg
 * @subpackage Woocommerce_Gateway_Nbk_Cg/includes
 * @author     Nbk-cg <sileymane.djimera@nbk-cg.com>
 */
class Woocommerce_Gateway_Nbk_Cg_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'woocommerce-gateway-nbk-cg',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
