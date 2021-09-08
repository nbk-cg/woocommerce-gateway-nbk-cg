<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DEPRECATED DO NOT USE!!
 */
if ( ! class_exists( 'WC_Nbk_Cg_Apple_Pay' ) ) {
	class WC_Nbk_Cg_Apple_Pay {
		/**
		 * This Instance.
		 *
		 * @var
		 */
		private static $_this;

		public function __construct() {
			self::$_this = $this;
		}

		public static function instance() {
			WC_Nbk_Cg_Logger::log( 'DEPRECATED! WC_Nbk_Cg_Apple_Pay class has been hard deprecated. Please remove any code that references this class or instance. This class will be removed by version 4.2' );
			return self::$_this;
		}

		public function __get( $var ) {
			return null;
		}

		public function __call( $name, $arguments ) {
			return null;
		}
	}
}

new WC_Nbk_Cg_Apple_Pay();
