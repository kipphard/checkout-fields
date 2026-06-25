<?php
/**
 * Gemeinsame Hilfsmethoden: Rechte, Optionen, Sanitisierung, Pro-Gate.
 *
 * @package Kipphard\CheckoutFelder
 */

namespace Kipphard\CheckoutFelder;

defined( 'ABSPATH' ) || exit;

/**
 * Zustandslose Hilfsmethoden, die im gesamten Plugin genutzt werden.
 */
class Helpers {

	/** Erforderliche Berechtigung für alle Admin-Aktionen. */
	const CAP = 'manage_woocommerce';

	/** Options-Key für die Feldkonfiguration. */
	const OPT_FIELDS = 'ckf_fields';

	/** Options-Key für allgemeine Plugin-Einstellungen. */
	const OPT_SETTINGS = 'ckf_settings';

	/**
	 * Prüft ob die Pro-Lizenz aktiv ist. Standardmäßig false.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return (bool) apply_filters( 'ckf_is_pro', defined( 'CKF_PRO' ) && CKF_PRO );
	}

	/**
	 * Liefert die erlaubten Feldtypen abhängig von der Lizenz.
	 * Kostenlose Version: text, textarea. Pro: zusätzlich select, checkbox, radio, date, number.
	 *
	 * @return array<string>
	 */
	public static function allowed_types() {
		$free = array( 'text', 'textarea' );
		if ( self::is_pro() ) {
			return array_merge( $free, array( 'select', 'checkbox', 'radio', 'date', 'number' ) );
		}
		return $free;
	}

	/**
	 * Liefert die Standard-Einstellungen des Plugins.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'show_in_emails' => true,
			'show_in_admin'  => true,
		);
	}

	/**
	 * Liest eine einzelne Einstellung (mit Fallback auf den Standardwert).
	 *
	 * @param string $key Einstellungsschlüssel.
	 * @return mixed
	 */
	public static function get( $key ) {
		$settings = (array) get_option( self::OPT_SETTINGS, array() );
		$defaults = self::defaults();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : null );
	}

	/**
	 * Sanitisiert die allgemeinen Einstellungsfelder streng pro Feld.
	 *
	 * @param array<string,mixed> $raw Rohe $_POST-Daten.
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( array $raw ) {
		return array(
			'show_in_emails' => ! empty( $raw['show_in_emails'] ),
			'show_in_admin'  => ! empty( $raw['show_in_admin'] ),
		);
	}

	/**
	 * Prüft einen Admin-POST-Request: Berechtigung + Nonce. Bricht bei Fehler ab.
	 *
	 * @param string $action Nonce-Aktion.
	 * @param string $field  Nonce-Feldname.
	 */
	public static function guard_post( $action, $field = '_wpnonce' ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'checkout-felder' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action, $field );
	}
}
