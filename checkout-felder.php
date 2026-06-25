<?php
/**
 * Plugin Name:       Checkout-Felder – Checkout Field Editor für WooCommerce
 * Plugin URI:        https://products.kipphard.com/checkout-felder
 * Description:       Bearbeite, sortiere und ergänze die WooCommerce-Bestellfelder (Rechnung, Versand, Zusätzlich) ohne Code. Felder werden in Bestellungen gespeichert und in E-Mails angezeigt.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            André Kipphard
 * Author URI:        https://kipphard.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       checkout-felder
 * Domain Path:       /languages
 *
 * @package Kipphard\CheckoutFelder
 */

defined( 'ABSPATH' ) || exit;

define( 'CKF_VERSION', '0.1.0' );
define( 'CKF_FILE', __FILE__ );
define( 'CKF_DIR', plugin_dir_path( __FILE__ ) );
define( 'CKF_URL', plugin_dir_url( __FILE__ ) );
define( 'CKF_SLUG', 'checkout-felder' );

/**
 * Minimaler PSR-4-Autoloader für den Kipphard\CheckoutFelder\-Namespace.
 * Kipphard\CheckoutFelder\Foo_Bar → includes/class-foo-bar.php
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Kipphard\\CheckoutFelder\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		$path     = CKF_DIR . 'includes/' . $file;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( '\Kipphard\CheckoutFelder\Plugin', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\Kipphard\CheckoutFelder\Plugin::instance()->boot();
	}
);
