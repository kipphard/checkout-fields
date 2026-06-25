<?php
/**
 * Feldkonfiguration: laden, speichern, WC-Defaults ableiten.
 *
 * @package Kipphard\CheckoutFelder
 */

namespace Kipphard\CheckoutFelder;

defined( 'ABSPATH' ) || exit;

/**
 * Verwaltung der gespeicherten Feldkonfiguration.
 */
class Fields {

	/** Abschnittsnamen die unterstützt werden. */
	const SECTIONS = array( 'billing', 'shipping', 'order' );

	/**
	 * Gibt die vollständige Feldkonfiguration zurück.
	 * Gespeicherte Felder werden über die WC-Defaults gelegt; innerhalb
	 * jedes Abschnitts nach position aufsteigend sortiert.
	 *
	 * @return array<string,array>
	 */
	public static function get() {
		$stored   = (array) get_option( Helpers::OPT_FIELDS, array() );
		$defaults = self::defaults_from_wc();

		// Wenn noch nichts gespeichert: WC-Defaults verwenden und einmalig speichern.
		if ( empty( $stored ) ) {
			update_option( Helpers::OPT_FIELDS, $defaults );
			return $defaults;
		}

		// Fehlende Abschnitte mit Defaults auffüllen.
		$result = array();
		foreach ( self::SECTIONS as $section ) {
			$section_fields = isset( $stored[ $section ] ) ? $stored[ $section ] : ( isset( $defaults[ $section ] ) ? $defaults[ $section ] : array() );
			// Nach position sortieren.
			usort(
				$section_fields,
				static function ( $a, $b ) {
					return absint( $a['position'] ) - absint( $b['position'] );
				}
			);
			$result[ $section ] = $section_fields;
		}

		return $result;
	}

	/**
	 * Speichert eine sanitisierte Feldkonfiguration.
	 *
	 * @param array<string,array> $fields Rohe Feldkonfiguration aus POST.
	 */
	public static function save( array $fields ) {
		$allowed_types = Helpers::allowed_types();
		$clean         = array();

		foreach ( self::SECTIONS as $section ) {
			$clean[ $section ] = array();
			if ( empty( $fields[ $section ] ) || ! is_array( $fields[ $section ] ) ) {
				continue;
			}
			foreach ( $fields[ $section ] as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				// Feldschlüssel: nur gültige Zeichen (Buchstaben, Zahlen, Unterstriche).
				$key = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';
				if ( '' === $key ) {
					continue;
				}
				// Typ: nur aus der Allowliste.
				$type = isset( $field['type'] ) ? $field['type'] : 'text';
				if ( ! in_array( $type, $allowed_types, true ) ) {
					$type = 'text';
				}
				// Optionen für select/radio (Pro): Array von Strings.
				$options = array();
				if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
					foreach ( $field['options'] as $opt ) {
						$opt_clean = sanitize_text_field( wp_unslash( (string) $opt ) );
						if ( '' !== $opt_clean ) {
							$options[] = $opt_clean;
						}
					}
				}

				$clean[ $section ][] = array(
					'key'         => $key,
					'type'        => $type,
					'label'       => isset( $field['label'] ) ? sanitize_text_field( wp_unslash( $field['label'] ) ) : '',
					'placeholder' => isset( $field['placeholder'] ) ? sanitize_text_field( wp_unslash( $field['placeholder'] ) ) : '',
					'required'    => ! empty( $field['required'] ),
					'enabled'     => ! empty( $field['enabled'] ),
					'position'    => isset( $field['position'] ) ? absint( $field['position'] ) : 10,
					'custom'      => ! empty( $field['custom'] ),
					'options'     => $options,
				);
			}
		}

		update_option( Helpers::OPT_FIELDS, $clean );
	}

	/**
	 * Leitet die Standardfelder aus den aktuellen WooCommerce-Checkout-Feldern ab.
	 * Gibt ein leeres Array zurück wenn WC nicht verfügbar ist.
	 *
	 * @return array<string,array>
	 */
	public static function defaults_from_wc() {
		if ( ! function_exists( 'WC' ) || ! WC()->checkout() ) {
			return array(
				'billing'  => array(),
				'shipping' => array(),
				'order'    => array(),
			);
		}

		$wc_fields = WC()->checkout()->get_checkout_fields();
		$result    = array();
		$position  = 10;

		foreach ( self::SECTIONS as $section ) {
			$result[ $section ] = array();
			if ( empty( $wc_fields[ $section ] ) || ! is_array( $wc_fields[ $section ] ) ) {
				continue;
			}
			$position = 10;
			foreach ( $wc_fields[ $section ] as $key => $cfg ) {
				$result[ $section ][] = array(
					'key'         => sanitize_key( $key ),
					'type'        => 'text',
					'label'       => isset( $cfg['label'] ) ? sanitize_text_field( $cfg['label'] ) : '',
					'placeholder' => isset( $cfg['placeholder'] ) ? sanitize_text_field( $cfg['placeholder'] ) : '',
					'required'    => ! empty( $cfg['required'] ),
					'enabled'     => true,
					'position'    => $position,
					'custom'      => false,
					'options'     => array(),
				);
				$position += 10;
			}
		}

		return $result;
	}
}
