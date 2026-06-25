<?php
/**
 * WooCommerce-Integration: Felder filtern, validieren, speichern und anzeigen.
 *
 * @package Kipphard\CheckoutFelder
 */

namespace Kipphard\CheckoutFelder;

defined( 'ABSPATH' ) || exit;

/**
 * Alle Checkout-seitigen Hooks.
 */
class Checkout {

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		// Felder auf dem Checkout anpassen.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_checkout_fields' ) );

		// Pflichtfelder-Validierung für benutzerdefinierte Felder.
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_custom_fields' ) );

		// Feldwerte in der Bestellung speichern.
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_custom_fields' ) );

		// Im Admin-Bestellbereich anzeigen.
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_in_admin_order' ) );

		// In WooCommerce-E-Mails anzeigen.
		add_filter( 'woocommerce_email_order_meta_fields', array( $this, 'add_email_meta_fields' ), 10, 3 );
	}

	/**
	 * Wendet die gespeicherte Konfiguration auf die WooCommerce-Felder an.
	 * Deaktivierte Felder werden entfernt; Labels, Platzhalter, Pflichtfeld-Status
	 * und Priorität (aus position) werden überschrieben. Benutzerdefinierte Felder
	 * werden dem jeweiligen Abschnitt hinzugefügt.
	 *
	 * @param array<string,array> $wc_fields Felder von WooCommerce.
	 * @return array<string,array>
	 */
	public function filter_checkout_fields( $wc_fields ) {
		$config = Fields::get();

		foreach ( Fields::SECTIONS as $section ) {
			if ( empty( $config[ $section ] ) ) {
				continue;
			}
			foreach ( $config[ $section ] as $field ) {
				$key = $field['key'];

				if ( empty( $field['enabled'] ) ) {
					// Deaktiviert: aus dem WC-Array entfernen.
					if ( isset( $wc_fields[ $section ][ $key ] ) ) {
						unset( $wc_fields[ $section ][ $key ] );
					}
					continue;
				}

				if ( ! empty( $field['custom'] ) ) {
					// Benutzerdefiniertes Feld: neu hinzufügen.
					$wc_fields[ $section ][ $key ] = array(
						'type'        => $field['type'],
						'label'       => $field['label'],
						'placeholder' => $field['placeholder'],
						'required'    => (bool) $field['required'],
						'priority'    => absint( $field['position'] ),
						'class'       => array( 'form-row-wide' ),
					);
				} else {
					// Bestehendes WC-Feld anpassen.
					if ( isset( $wc_fields[ $section ][ $key ] ) ) {
						if ( '' !== $field['label'] ) {
							$wc_fields[ $section ][ $key ]['label'] = $field['label'];
						}
						if ( '' !== $field['placeholder'] ) {
							$wc_fields[ $section ][ $key ]['placeholder'] = $field['placeholder'];
						}
						$wc_fields[ $section ][ $key ]['required'] = (bool) $field['required'];
						$wc_fields[ $section ][ $key ]['priority'] = absint( $field['position'] );
					}
				}
			}
		}

		return $wc_fields;
	}

	/**
	 * Validiert Pflichtfelder für benutzerdefinierte Felder.
	 * WooCommerce validiert nur seine eigenen Pflichtfelder; wir müssen
	 * unsere selbst prüfen.
	 */
	public function validate_custom_fields() {
		$config = Fields::get();

		foreach ( Fields::SECTIONS as $section ) {
			if ( empty( $config[ $section ] ) ) {
				continue;
			}
			foreach ( $config[ $section ] as $field ) {
				if ( empty( $field['custom'] ) || empty( $field['enabled'] ) || empty( $field['required'] ) ) {
					continue;
				}
				$key   = $field['key'];
				$value = isset( $_POST[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : '';
				if ( '' === $value ) {
					wc_add_notice(
						/* translators: %s: Feldbezeichnung */
						sprintf( __( '%s ist ein Pflichtfeld.', 'checkout-felder' ), '<strong>' . esc_html( $field['label'] ) . '</strong>' ),
						'error'
					);
				}
			}
		}
	}

	/**
	 * Speichert die Werte benutzerdefinierter Felder als Order-Meta.
	 * Schlüssel werden mit dem Präfix _ckf_ gespeichert.
	 *
	 * @param int $order_id Bestellungs-ID.
	 */
	public function save_custom_fields( $order_id ) {
		$config = Fields::get();

		foreach ( Fields::SECTIONS as $section ) {
			if ( empty( $config[ $section ] ) ) {
				continue;
			}
			foreach ( $config[ $section ] as $field ) {
				if ( empty( $field['custom'] ) || empty( $field['enabled'] ) ) {
					continue;
				}
				$key = $field['key'];
				if ( ! isset( $_POST[ $key ] ) ) {
					continue;
				}
				$type  = isset( $field['type'] ) ? $field['type'] : 'text';
				$value = $this->sanitize_value( wp_unslash( $_POST[ $key ] ), $type );
				update_post_meta( $order_id, '_ckf_' . $key, $value );
			}
		}
	}

	/**
	 * Zeigt benutzerdefinierte Feldwerte im Admin-Bestellbereich an.
	 *
	 * @param \WC_Order $order WooCommerce-Bestellung.
	 */
	public function display_in_admin_order( $order ) {
		if ( ! Helpers::get( 'show_in_admin' ) ) {
			return;
		}
		$fields = $this->get_custom_field_values( $order );
		if ( empty( $fields ) ) {
			return;
		}
		echo '<div class="ckf-admin-fields">';
		echo '<h3>' . esc_html__( 'Zusätzliche Felder', 'checkout-felder' ) . '</h3>';
		echo '<table class="wc-order-totals">';
		foreach ( $fields as $label => $value ) {
			echo '<tr>';
			echo '<td class="label">' . esc_html( $label ) . ':</td>';
			echo '<td class="total">' . esc_html( $value ) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo '</div>';
	}

	/**
	 * Fügt benutzerdefinierte Feldwerte zu WooCommerce-E-Mails hinzu.
	 *
	 * @param array<string,array> $fields   Bereits vorhandene E-Mail-Meta-Felder.
	 * @param bool                $sent_to_admin Ob E-Mail an Admin geht.
	 * @param \WC_Order           $order    WooCommerce-Bestellung.
	 * @return array<string,array>
	 */
	public function add_email_meta_fields( $fields, $sent_to_admin, $order ) {
		if ( ! Helpers::get( 'show_in_emails' ) ) {
			return $fields;
		}
		$custom_values = $this->get_custom_field_values( $order );
		foreach ( $custom_values as $label => $value ) {
			$fields[] = array(
				'label' => $label,
				'value' => $value,
			);
		}
		return $fields;
	}

	// -------------------------------------------------------------------------
	// Interne Hilfsmethoden
	// -------------------------------------------------------------------------

	/**
	 * Sanitisiert einen Feldwert abhängig vom Feldtyp.
	 *
	 * @param mixed  $value Rohwert aus $_POST.
	 * @param string $type  Feldtyp.
	 * @return string
	 */
	private function sanitize_value( $value, $type ) {
		if ( 'textarea' === $type ) {
			return sanitize_textarea_field( (string) $value );
		}
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Liest alle benutzerdefinierten Feldwerte einer Bestellung aus.
	 * Gibt ein assoziatives Array label => value zurück.
	 *
	 * @param \WC_Order $order WooCommerce-Bestellung.
	 * @return array<string,string>
	 */
	private function get_custom_field_values( $order ) {
		$config = Fields::get();
		$result = array();
		$id     = $order->get_id();

		foreach ( Fields::SECTIONS as $section ) {
			if ( empty( $config[ $section ] ) ) {
				continue;
			}
			foreach ( $config[ $section ] as $field ) {
				if ( empty( $field['custom'] ) || empty( $field['enabled'] ) ) {
					continue;
				}
				$meta = get_post_meta( $id, '_ckf_' . $field['key'], true );
				if ( '' !== $meta && false !== $meta ) {
					$result[ $field['label'] ] = (string) $meta;
				}
			}
		}

		return $result;
	}
}
