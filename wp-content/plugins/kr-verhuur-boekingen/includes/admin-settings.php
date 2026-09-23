<?php
/**
 * Beheer: instellingen en extra opties.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=kr_booking', 'Instellingen', 'Instellingen', 'manage_options', 'krv-settings', 'krv_settings_page' );
	}
);

add_action(
	'admin_post_krv_save_settings',
	function () {
		check_admin_referer( 'krv_settings' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		$in = isset( $_POST['krv'] ) ? wp_unslash( (array) $_POST['krv'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$settings = array(
			'company_name'    => sanitize_text_field( $in['company_name'] ?? '' ),
			'email'           => sanitize_email( $in['email'] ?? '' ),
			'notify_email'    => sanitize_email( $in['notify_email'] ?? '' ),
			'phone'           => sanitize_text_field( $in['phone'] ?? '' ),
			'region'          => sanitize_text_field( $in['region'] ?? '' ),
			'goboony_url'     => esc_url_raw( $in['goboony_url'] ?? '' ),
			'min_lead_days'   => max( 0, (int) ( $in['min_lead_days'] ?? 1 ) ),
			'max_days_ahead'  => max( 7, (int) ( $in['max_days_ahead'] ?? 365 ) ),
			'blocking_status' => array_values( array_intersect( (array) ( $in['blocking_status'] ?? array() ), array( 'pending', 'confirmed' ) ) ),
		);
		if ( ! $settings['blocking_status'] ) {
			$settings['blocking_status'] = array( 'confirmed' );
		}
		update_option( 'krv_settings', $settings );

		$extras = array();
		foreach ( (array) ( $_POST['krv_extras'] ?? array() ) as $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$row   = wp_unslash( (array) $row );
			$label = sanitize_text_field( $row['label'] ?? '' );
			if ( '' === $label || ! empty( $row['delete'] ) ) {
				continue;
			}
			$key = sanitize_key( $row['key'] ?? '' );
			if ( '' === $key ) {
				$key = sanitize_key( sanitize_title( $label ) );
			}
			while ( isset( $extras[ $key ] ) ) {
				$key .= '2';
			}
			$extras[ $key ] = array(
				'label'         => $label,
				'description'   => sanitize_text_field( $row['description'] ?? '' ),
				'price'         => round( (float) str_replace( ',', '.', $row['price'] ?? 0 ), 2 ),
				'type'          => 'perDay' === ( $row['type'] ?? '' ) ? 'perDay' : 'fixed',
				'needs_address' => empty( $row['needs_address'] ) ? 0 : 1,
			);
		}
		update_option( 'krv_extras', $extras );

		// Goboony-link ook bij de huurgroep "camper" bijwerken als die nog naar Goboony wees.
		$camper = get_term_by( 'slug', 'camper', 'kr_group' );
		if ( $camper && false !== strpos( (string) get_term_meta( $camper->term_id, '_krv_external_url', true ), 'goboony' ) ) {
			update_term_meta( $camper->term_id, '_krv_external_url', $settings['goboony_url'] );
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=kr_booking&page=krv-settings&saved=1' ) );
		exit;
	}
);

function krv_settings_page() {
	$s      = krv_settings();
	$extras = krv_extras();
	?>
	<div class="wrap">
		<h1>KR Verhuur – instellingen</h1>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Instellingen opgeslagen.</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="krv_save_settings">
			<?php wp_nonce_field( 'krv_settings' ); ?>

			<h2>Bedrijf & contact</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="company_name">Bedrijfsnaam</label></th><td><input class="regular-text" id="company_name" name="krv[company_name]" value="<?php echo esc_attr( $s['company_name'] ); ?>"></td></tr>
				<tr><th><label for="email">E-mailadres (website)</label></th><td><input class="regular-text" type="email" id="email" name="krv[email]" value="<?php echo esc_attr( $s['email'] ); ?>"></td></tr>
				<tr><th><label for="notify_email">Meldingen nieuwe boekingen naar</label></th><td><input class="regular-text" type="email" id="notify_email" name="krv[notify_email]" value="<?php echo esc_attr( $s['notify_email'] ); ?>"></td></tr>
				<tr><th><label for="phone">Telefoonnummer</label></th><td><input class="regular-text" id="phone" name="krv[phone]" value="<?php echo esc_attr( $s['phone'] ); ?>"></td></tr>
				<tr><th><label for="region">Plaats / werkgebied</label></th><td><input class="regular-text" id="region" name="krv[region]" value="<?php echo esc_attr( $s['region'] ); ?>"></td></tr>
				<tr><th><label for="goboony_url">Goboony-link camper</label></th><td><input class="regular-text" type="url" id="goboony_url" name="krv[goboony_url]" value="<?php echo esc_attr( $s['goboony_url'] ); ?>"></td></tr>
			</table>

			<h2>Reserveren</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="min_lead_days">Minimaal dagen vooruit</label></th><td><input type="number" min="0" id="min_lead_days" name="krv[min_lead_days]" value="<?php echo esc_attr( $s['min_lead_days'] ); ?>" style="width:80px"> <span class="description">0 = vandaag nog boeken</span></td></tr>
				<tr><th><label for="max_days_ahead">Maximaal dagen vooruit</label></th><td><input type="number" min="7" id="max_days_ahead" name="krv[max_days_ahead]" value="<?php echo esc_attr( $s['max_days_ahead'] ); ?>" style="width:80px"></td></tr>
				<tr><th>Agenda blokkeren bij</th><td>
					<label><input type="checkbox" name="krv[blocking_status][]" value="pending" <?php checked( in_array( 'pending', $s['blocking_status'], true ) ); ?>> Aanvraag</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="krv[blocking_status][]" value="confirmed" <?php checked( in_array( 'confirmed', $s['blocking_status'], true ) ); ?>> Bevestigd</label>
					<p class="description">Standaard blokkeren ook nog niet bevestigde aanvragen de agenda, zodat er niet dubbel geboekt wordt.</p></td></tr>
			</table>

			<h2>Extra opties</h2>
			<p class="description">Bijvoorbeeld halen en brengen of schoonmaakkosten. Per huurartikel kies je welke opties beschikbaar zijn.</p>
			<table class="widefat striped" id="krv-extras" style="max-width:1100px">
				<thead><tr><th>Naam</th><th>Omschrijving</th><th style="width:100px">Prijs (€)</th><th style="width:130px">Berekening</th><th style="width:90px">Vraagt adres</th><th style="width:70px">Verwijder</th></tr></thead>
				<tbody>
				<?php
				$i = 0;
				foreach ( $extras as $key => $x ) :
					?>
					<tr>
						<td><input type="hidden" name="krv_extras[<?php echo (int) $i; ?>][key]" value="<?php echo esc_attr( $key ); ?>"><input style="width:100%" name="krv_extras[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $x['label'] ); ?>"></td>
						<td><input style="width:100%" name="krv_extras[<?php echo (int) $i; ?>][description]" value="<?php echo esc_attr( $x['description'] ); ?>"></td>
						<td><input style="width:100%" type="number" step="0.01" min="0" name="krv_extras[<?php echo (int) $i; ?>][price]" value="<?php echo esc_attr( $x['price'] ); ?>"></td>
						<td><select name="krv_extras[<?php echo (int) $i; ?>][type]"><option value="fixed" <?php selected( $x['type'], 'fixed' ); ?>>Per boeking</option><option value="perDay" <?php selected( $x['type'], 'perDay' ); ?>>Per dag</option></select></td>
						<td style="text-align:center"><input type="checkbox" name="krv_extras[<?php echo (int) $i; ?>][needs_address]" value="1" <?php checked( ! empty( $x['needs_address'] ) ); ?>></td>
						<td style="text-align:center"><input type="checkbox" name="krv_extras[<?php echo (int) $i; ?>][delete]" value="1"></td>
					</tr>
					<?php
					$i++;
				endforeach;
				?>
				<tr>
					<td><input style="width:100%" name="krv_extras[<?php echo (int) $i; ?>][label]" placeholder="Nieuwe optie…"></td>
					<td><input style="width:100%" name="krv_extras[<?php echo (int) $i; ?>][description]"></td>
					<td><input style="width:100%" type="number" step="0.01" min="0" name="krv_extras[<?php echo (int) $i; ?>][price]"></td>
					<td><select name="krv_extras[<?php echo (int) $i; ?>][type]"><option value="fixed">Per boeking</option><option value="perDay">Per dag</option></select></td>
					<td style="text-align:center"><input type="checkbox" name="krv_extras[<?php echo (int) $i; ?>][needs_address]" value="1"></td>
					<td></td>
				</tr>
				</tbody>
			</table>
			<?php submit_button( 'Instellingen opslaan' ); ?>
		</form>
	</div>
	<?php
}
