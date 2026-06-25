<?php
/**
 * WordPress-Admin-UI: Feldeditor und Einstellungsseite.
 *
 * @package Kipphard\CheckoutFelder
 */

namespace Kipphard\CheckoutFelder;

defined( 'ABSPATH' ) || exit;

/**
 * Registriert Admin-Menüs und verarbeitet Formularabsendungen.
 */
class Admin {

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_ckf_save_fields', array( $this, 'handle_save_fields' ) );
		add_action( 'admin_post_ckf_save_settings', array( $this, 'handle_save_settings' ) );
	}

	/**
	 * Untermenü unter WooCommerce registrieren.
	 */
	public function register_menus() {
		add_submenu_page(
			'woocommerce',
			__( 'Checkout-Felder', 'checkout-felder' ),
			__( 'Checkout-Felder', 'checkout-felder' ),
			Helpers::CAP,
			CKF_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Assets nur auf der Plugin-Seite einbinden.
	 *
	 * @param string $hook Aktueller Admin-Seiten-Hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_' . CKF_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'ckf-admin',
			CKF_URL . 'assets/admin.css',
			array(),
			CKF_VERSION
		);
		wp_enqueue_script(
			'ckf-admin',
			CKF_URL . 'assets/admin.js',
			array(),
			CKF_VERSION,
			true
		);
	}

	// -------------------------------------------------------------------------
	// POST-Handler
	// -------------------------------------------------------------------------

	/**
	 * Feldkonfiguration speichern.
	 */
	public function handle_save_fields() {
		Helpers::guard_post( 'ckf_save_fields' );

		$raw = isset( $_POST['ckf_fields'] ) && is_array( $_POST['ckf_fields'] )
			? wp_unslash( $_POST['ckf_fields'] )
			: array();

		Fields::save( $raw );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => CKF_SLUG,
					'notice' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Allgemeine Einstellungen speichern.
	 */
	public function handle_save_settings() {
		Helpers::guard_post( 'ckf_save_settings' );

		$raw   = isset( $_POST ) ? $_POST : array();
		$clean = Helpers::sanitize_settings( $raw );
		update_option( Helpers::OPT_SETTINGS, $clean );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => CKF_SLUG,
					'tab'    => 'settings',
					'notice' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// Seiten-Renderer
	// -------------------------------------------------------------------------

	/**
	 * Hauptseite rendern – Tab-basiert (Felder | Einstellungen).
	 */
	public function render_page() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}

		$notice  = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$tab     = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'fields';
		$is_pro  = Helpers::is_pro();

		$tabs = array(
			'fields'   => __( 'Checkout-Felder', 'checkout-felder' ),
			'settings' => __( 'Einstellungen', 'checkout-felder' ),
		);
		?>
		<div class="wrap ckf-wrap">
			<h1><?php esc_html_e( 'Checkout-Felder', 'checkout-felder' ); ?></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Einstellungen gespeichert.', 'checkout-felder' ); ?></p>
				</div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => CKF_SLUG, 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>"
					   class="nav-tab<?php echo ( $tab === $tab_key ) ? ' nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'fields' === $tab ) : ?>
				<?php $this->render_fields_tab( $is_pro ); ?>
			<?php elseif ( 'settings' === $tab ) : ?>
				<?php $this->render_settings_tab( $is_pro ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Tab: Feldeditor.
	 *
	 * @param bool $is_pro Ob Pro aktiv ist.
	 */
	private function render_fields_tab( $is_pro ) {
		$config        = Fields::get();
		$allowed_types = Helpers::allowed_types();

		$section_labels = array(
			'billing'  => __( 'Rechnung', 'checkout-felder' ),
			'shipping' => __( 'Versand', 'checkout-felder' ),
			'order'    => __( 'Zusätzlich', 'checkout-felder' ),
		);
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ckf-fields-form">
			<input type="hidden" name="action" value="ckf_save_fields">
			<?php wp_nonce_field( 'ckf_save_fields' ); ?>

			<?php foreach ( Fields::SECTIONS as $section ) : ?>
				<h2><?php echo esc_html( $section_labels[ $section ] ); ?></h2>

				<table class="wp-list-table widefat fixed ckf-fields-table" data-section="<?php echo esc_attr( $section ); ?>">
					<thead>
						<tr>
							<th class="ckf-col-enabled"><?php esc_html_e( 'Aktiv', 'checkout-felder' ); ?></th>
							<th class="ckf-col-key"><?php esc_html_e( 'Schlüssel', 'checkout-felder' ); ?></th>
							<th class="ckf-col-label"><?php esc_html_e( 'Bezeichnung', 'checkout-felder' ); ?></th>
							<th class="ckf-col-placeholder"><?php esc_html_e( 'Platzhalter', 'checkout-felder' ); ?></th>
							<th class="ckf-col-type"><?php esc_html_e( 'Typ', 'checkout-felder' ); ?></th>
							<th class="ckf-col-required"><?php esc_html_e( 'Pflicht', 'checkout-felder' ); ?></th>
							<th class="ckf-col-position"><?php esc_html_e( 'Position', 'checkout-felder' ); ?></th>
							<th class="ckf-col-actions"><?php esc_html_e( 'Aktionen', 'checkout-felder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$fields = isset( $config[ $section ] ) ? $config[ $section ] : array();
						foreach ( $fields as $i => $field ) :
							$base = 'ckf_fields[' . esc_attr( $section ) . '][' . $i . ']';
							?>
							<tr class="ckf-field-row" data-custom="<?php echo ! empty( $field['custom'] ) ? '1' : '0'; ?>">
								<td class="ckf-col-enabled">
									<input type="checkbox"
										   name="<?php echo esc_attr( $base ); ?>[enabled]"
										   value="1"
										   <?php checked( ! empty( $field['enabled'] ) ); ?>>
								</td>
								<td class="ckf-col-key">
									<input type="hidden" name="<?php echo esc_attr( $base ); ?>[key]" value="<?php echo esc_attr( $field['key'] ); ?>">
									<input type="hidden" name="<?php echo esc_attr( $base ); ?>[custom]" value="<?php echo ! empty( $field['custom'] ) ? '1' : '0'; ?>">
									<code><?php echo esc_html( $field['key'] ); ?></code>
								</td>
								<td class="ckf-col-label">
									<input type="text"
										   name="<?php echo esc_attr( $base ); ?>[label]"
										   value="<?php echo esc_attr( $field['label'] ); ?>"
										   class="regular-text">
								</td>
								<td class="ckf-col-placeholder">
									<input type="text"
										   name="<?php echo esc_attr( $base ); ?>[placeholder]"
										   value="<?php echo esc_attr( $field['placeholder'] ); ?>"
										   class="regular-text">
								</td>
								<td class="ckf-col-type">
									<?php if ( ! empty( $field['custom'] ) ) : ?>
										<select name="<?php echo esc_attr( $base ); ?>[type]">
											<?php foreach ( $allowed_types as $type_val ) : ?>
												<option value="<?php echo esc_attr( $type_val ); ?>" <?php selected( $field['type'], $type_val ); ?>>
													<?php echo esc_html( $type_val ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									<?php else : ?>
										<input type="hidden" name="<?php echo esc_attr( $base ); ?>[type]" value="<?php echo esc_attr( isset( $field['type'] ) ? $field['type'] : 'text' ); ?>">
										<span><?php echo esc_html( isset( $field['type'] ) ? $field['type'] : 'text' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="ckf-col-required">
									<input type="checkbox"
										   name="<?php echo esc_attr( $base ); ?>[required]"
										   value="1"
										   <?php checked( ! empty( $field['required'] ) ); ?>>
								</td>
								<td class="ckf-col-position">
									<input type="number"
										   name="<?php echo esc_attr( $base ); ?>[position]"
										   value="<?php echo esc_attr( absint( $field['position'] ) ); ?>"
										   class="small-text"
										   min="1">
								</td>
								<td class="ckf-col-actions">
									<?php if ( ! empty( $field['custom'] ) ) : ?>
										<button type="button" class="button button-link-delete ckf-remove-field">
											<?php esc_html_e( 'Entfernen', 'checkout-felder' ); ?>
										</button>
									<?php else : ?>
										<span class="ckf-wc-field-badge"><?php esc_html_e( 'WooCommerce', 'checkout-felder' ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr class="ckf-add-field-row">
							<td colspan="8">
								<strong><?php esc_html_e( 'Neues Feld hinzufügen:', 'checkout-felder' ); ?></strong>
								<label class="screen-reader-text" for="ckf-new-label-<?php echo esc_attr( $section ); ?>"><?php esc_html_e( 'Bezeichnung', 'checkout-felder' ); ?></label>
								<input type="text" id="ckf-new-label-<?php echo esc_attr( $section ); ?>"
									   class="ckf-new-label regular-text"
									   placeholder="<?php esc_attr_e( 'Bezeichnung', 'checkout-felder' ); ?>">
								<label class="screen-reader-text" for="ckf-new-type-<?php echo esc_attr( $section ); ?>"><?php esc_html_e( 'Typ', 'checkout-felder' ); ?></label>
								<select id="ckf-new-type-<?php echo esc_attr( $section ); ?>" class="ckf-new-type">
									<?php foreach ( $allowed_types as $type_val ) : ?>
										<option value="<?php echo esc_attr( $type_val ); ?>"><?php echo esc_html( $type_val ); ?></option>
									<?php endforeach; ?>
								</select>
								<button type="button" class="button ckf-add-field"
										data-section="<?php echo esc_attr( $section ); ?>">
									<?php esc_html_e( '+ Feld hinzufügen', 'checkout-felder' ); ?>
								</button>
							</td>
						</tr>
					</tfoot>
				</table>
			<?php endforeach; ?>

			<?php submit_button( __( 'Felder speichern', 'checkout-felder' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Tab: Einstellungen + Pro-Teaser.
	 *
	 * @param bool $is_pro Ob Pro aktiv ist.
	 */
	private function render_settings_tab( $is_pro ) {
		$settings        = (array) get_option( Helpers::OPT_SETTINGS, array() );
		$defaults        = Helpers::defaults();
		$show_in_emails  = isset( $settings['show_in_emails'] ) ? (bool) $settings['show_in_emails'] : $defaults['show_in_emails'];
		$show_in_admin   = isset( $settings['show_in_admin'] ) ? (bool) $settings['show_in_admin'] : $defaults['show_in_admin'];
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ckf_save_settings">
			<?php wp_nonce_field( 'ckf_save_settings' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Felder anzeigen', 'checkout-felder' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="checkbox" name="show_in_emails" value="1" <?php checked( $show_in_emails ); ?>>
								<?php esc_html_e( 'In Bestell-E-Mails anzeigen', 'checkout-felder' ); ?>
							</label>
							<br>
							<label>
								<input type="checkbox" name="show_in_admin" value="1" <?php checked( $show_in_admin ); ?>>
								<?php esc_html_e( 'In Admin-Bestellansicht anzeigen', 'checkout-felder' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Einstellungen speichern', 'checkout-felder' ) ); ?>
		</form>

		<?php if ( $is_pro ) : ?>

			<hr>
			<div class="card ckf-pro-settings" style="max-width:680px;padding:20px 24px;margin-top:20px;">
				<h2><?php esc_html_e( 'Pro-Funktionen', 'checkout-felder' ); ?></h2>
				<p><?php esc_html_e( 'Checkout-Felder Pro ist aktiv.', 'checkout-felder' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Erweiterte Feldtypen: Select, Checkbox, Radio, Datum, Zahl', 'checkout-felder' ); ?></li>
					<li><?php esc_html_e( 'Bedingte Logik: Felder ein-/ausblenden basierend auf anderen Feldwerten', 'checkout-felder' ); ?></li>
				</ul>
			</div>

		<?php else : ?>

			<hr>
			<div class="card ckf-pro-teaser" style="max-width:680px;padding:20px 24px;margin-top:20px;background:#f6f7f7;border:1px dashed #a7aaad;">
				<h2><?php esc_html_e( 'Checkout-Felder Pro', 'checkout-felder' ); ?></h2>
				<p><?php esc_html_e( 'Erweitere den Checkout-Editor mit professionellen Funktionen:', 'checkout-felder' ); ?></p>
				<ul class="ckf-pro-features">
					<li>
						<span class="dashicons dashicons-editor-ul"></span>
						<?php esc_html_e( 'Erweiterte Feldtypen: Select, Checkbox, Radio, Datum, Zahl', 'checkout-felder' ); ?>
					</li>
					<li>
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Bedingte Logik: Felder dynamisch ein-/ausblenden', 'checkout-felder' ); ?>
					</li>
				</ul>
				<p>
					<a href="https://products.kipphard.com/checkout-felder" target="_blank" rel="noopener noreferrer" class="button button-secondary">
						<?php esc_html_e( 'Jetzt upgraden', 'checkout-felder' ); ?>
					</a>
				</p>
			</div>

		<?php endif; ?>
		<?php
	}
}
