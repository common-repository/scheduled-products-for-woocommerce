<?php

/*
Plugin Name: Scheduled Products for WooCommerce
Description: Schedule publishing and unpublishing of WooCommerce products.
Version:     1.0.0
Author:      Lauri Karisola / WooElements.com
Author URI:  https://wooelements.com
Text Domain: woo-scheduled-products
Domain Path: /languages
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin textdomain
 *
 * @return void
 */
add_action( 'plugins_loaded', 'woo_scheduled_products_load_textdomain' );
function woo_scheduled_products_load_textdomain() {
  load_plugin_textdomain( 'woo-scheduled-products', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

class Woo_Scheduled_Products {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
	}

	/**
	 * Include required files
	 */
	public function includes() {
		if ( is_admin() ) {
			$this->admin_includes();
		}

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/frontend/class-woo-scheduled-products-frontend.php', 'Woo_Scheduled_Products_Frontend' );
	}

	/**
	 * Include admin files
	 */
	private function admin_includes() {
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/admin/class-woo-scheduled-products-admin.php', 'Woo_Scheduled_Products_Admin' );
	}

	/**
	 * Load class
	 */
	private function load_class( $filepath, $class_name ) {
		include_once( $filepath );

		if ( $class_name ) {
			return new $class_name;
		}

		return TRUE;
	}
}

new Woo_Scheduled_Products();
