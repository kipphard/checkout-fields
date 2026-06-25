<?php
/**
 * Plugin-Deinstallation: Optionen aus der Datenbank entfernen.
 *
 * @package Kipphard\CheckoutFelder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ckf_fields' );
delete_option( 'ckf_settings' );
