<?php
/**
 * Plugin-Bootstrap: Hooks, Submodule und WooCommerce-Prüfung.
 *
 * @package Kipphard\CheckoutFelder
 */

namespace Kipphard\CheckoutFelder;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton-Einstiegspunkt.
 */
final class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	/**
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private Konstruktor (Singleton).
	 */
	private function __construct() {}

	/**
	 * Aktivierung: Standard-Felder aus WooCommerce ableiten und speichern.
	 */
	public static function activate() {
		// Felder nur anlegen wenn die Option noch nicht existiert.
		if ( false === get_option( Helpers::OPT_FIELDS, false ) ) {
			// WooCommerce muss für defaults_from_wc() geladen sein – falls nicht, leeres Array.
			$fields = class_exists( 'WooCommerce' ) ? Fields::defaults_from_wc() : array();
			add_option( Helpers::OPT_FIELDS, $fields );
		}
		if ( false === get_option( Helpers::OPT_SETTINGS, false ) ) {
			add_option( Helpers::OPT_SETTINGS, Helpers::defaults() );
		}
	}

	/**
	 * Laufzeit-Hooks registrieren.
	 */
	public function boot() {
		load_plugin_textdomain(
			'checkout-felder',
			false,
			dirname( plugin_basename( CKF_FILE ) ) . '/languages'
		);

		// WooCommerce ist Pflicht – ohne es läuft nichts.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_woocommerce_missing' ) );
			return;
		}

		( new Checkout() )->hooks();

		if ( is_admin() ) {
			( new Admin() )->hooks();
		}

		// Pro-only: nur laden wenn die Datei im Build vorhanden ist.
		if ( class_exists( __NAMESPACE__ . '\\Pro_Fields' ) ) {
			( new Pro_Fields() )->hooks();
		}
	}

	/**
	 * Admin-Hinweis wenn WooCommerce nicht aktiv ist.
	 */
	public function notice_woocommerce_missing() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Checkout-Felder', 'checkout-felder' ); ?>:</strong>
				<?php esc_html_e( 'WooCommerce muss installiert und aktiviert sein, damit dieses Plugin funktioniert.', 'checkout-felder' ); ?>
			</p>
		</div>
		<?php
	}
}
