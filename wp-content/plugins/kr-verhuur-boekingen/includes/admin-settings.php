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
		$settings['vat_rate']        = max( 0, min( 100, (float) str_replace( ',', '.', $in['vat_rate'] ?? 21 ) ) );
		$settings['prices_incl_vat'] = ( $in['prices_incl_vat'] ?? '1' ) === '1' ? 1 : 0;
		$settings['display_vat']     = 'excl' === ( $in['display_vat'] ?? 'incl' ) ? 'excl' : 'incl';
		$settings['vat_toggle']      = empty( $in['vat_toggle'] ) ? 0 : 1;
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

		// E-mailteksten.
		$texts = array();
		$in_mail = isset( $_POST['krv_email'] ) ? wp_unslash( (array) $_POST['krv_email'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( krv_email_text_defaults() as $key => $default ) {
			$val           = isset( $in_mail[ $key ] ) ? sanitize_textarea_field( $in_mail[ $key ] ) : $default;
			$texts[ $key ] = '' === trim( $val ) ? $default : $val;
		}
		update_option( 'krv_email_texts', $texts );

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
		<?php if ( ! empty( $_GET['testmail'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<?php if ( 'ok' === $_GET['testmail'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>Testmail verstuurd naar <?php echo esc_html( wp_get_current_user()->user_email ); ?>.</p></div>
			<?php else : ?>
				<div class="notice notice-error is-dismissible"><p>De testmail kon niet worden verstuurd. Controleer de e-mailinstellingen van je server (tip: installeer een SMTP-plugin zoals WP Mail SMTP).</p></div>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Instellingen opgeslagen.</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="krv_save_settings">
			<?php wp_nonce_field( 'krv_settings' ); ?>

			<h2>Bedrijf & contact</h2>
			<p class="description">Deze gegevens staan op de website (header, footer en contactblok op de homepage) en in alle e-mails. E-mails worden verstuurd vanaf het e-mailadres hieronder.</p>
			<table class="form-table" role="presentation">
				<tr><th><label for="company_name">Bedrijfsnaam</label></th><td><input class="regular-text" id="company_name" name="krv[company_name]" value="<?php echo esc_attr( $s['company_name'] ); ?>"></td></tr>
				<tr><th><label for="email">E-mailadres</label></th><td><input class="regular-text" type="email" id="email" name="krv[email]" value="<?php echo esc_attr( $s['email'] ); ?>"></td></tr>
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

			<h2>Btw</h2>
			<table class="form-table" role="presentation">
				<tr><th>Prijzen standaard tonen</th><td>
					<label><input type="radio" name="krv[display_vat]" value="incl" <?php checked( 'incl', $s['display_vat'] ); ?>> Inclusief btw</label><br>
					<label><input type="radio" name="krv[display_vat]" value="excl" <?php checked( 'excl', $s['display_vat'] ); ?>> Exclusief btw</label>
					<p class="description">Hoe nieuwe bezoekers de prijzen zien: artikelen, extra opties, winkelwagen en e-mails. Bij exclusief toont de winkelwagen ook de btw en het totaal inclusief btw.</p></td></tr>
				<tr><th>Wisselknop voor bezoekers</th><td>
					<label><input type="checkbox" name="krv[vat_toggle]" value="1" <?php checked( ! empty( $s['vat_toggle'] ) ); ?>> Bezoekers kunnen zelf wisselen tussen incl. en excl. btw</label>
					<p class="description">Toont een schakelaar "Incl. btw / Excl. btw" in de header en de winkelwagen. De keuze wordt onthouden.</p></td></tr>
				<tr><th>Ingevoerde prijzen zijn</th><td>
					<label><input type="radio" name="krv[prices_incl_vat]" value="1" <?php checked( 1, (int) $s['prices_incl_vat'] ); ?>> Inclusief btw</label><br>
					<label><input type="radio" name="krv[prices_incl_vat]" value="0" <?php checked( 0, (int) $s['prices_incl_vat'] ); ?>> Exclusief btw</label>
					<p class="description">Hoe je de prijzen bij huurartikelen en extra opties invult. Borg valt buiten de btw.</p></td></tr>
				<tr><th><label for="vat_rate">Btw-percentage</label></th><td><input type="number" step="0.1" min="0" max="100" id="vat_rate" name="krv[vat_rate]" value="<?php echo esc_attr( $s['vat_rate'] ); ?>" style="width:80px"> %</td></tr>
			</table>

			<h2>Extra opties</h2>
			<p class="description">Bijvoorbeeld halen en brengen of schoonmaakkosten. Per huurartikel kies je welke opties beschikbaar zijn.</p>
			<table class="widefat striped" id="krv-extras" style="max-width:1100px">
				<thead><tr><th>Naam</th><th>Omschrijving</th><th style="width:100px">Prijs (€ <?php echo $s['prices_incl_vat'] ? 'incl.' : 'excl.'; ?> btw)</th><th style="width:130px">Berekening</th><th style="width:90px">Vraagt adres</th><th style="width:70px">Verwijder</th></tr></thead>
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
			<h2 id="emails">E-mails aan klanten</h2>
			<p class="description">Pas de teksten aan naar je eigen toon. Je kunt deze codes gebruiken; ze worden automatisch ingevuld:
				<?php foreach ( krv_email_placeholder_help() as $code => $help ) : ?>
					<code><?php echo esc_html( $code ); ?></code> <?php echo esc_html( lcfirst( $help ) ); ?>;
				<?php endforeach; ?>
				Een lege regel maakt een nieuwe alinea.</p>
			<?php
			$texts = krv_email_texts();
			$types = array(
				'received'  => array( 'Bestelling ontvangen', 'Direct na het plaatsen van een bestelling.' ),
				'confirmed' => array( 'Reservering bevestigd', 'Als je een boeking op "Bevestigd" zet en "E-mail klant" aanvinkt.' ),
				'cancelled' => array( 'Reservering geannuleerd', 'Als je een boeking op "Geannuleerd" zet en "E-mail klant" aanvinkt.' ),
			);
			$preview = function ( $type ) {
				return wp_nonce_url( admin_url( 'admin-post.php?action=krv_email_preview&type=' . $type ), 'krv_email_preview' );
			};
			?>
			<?php foreach ( $types as $type => $info ) : ?>
				<h3 style="margin-top:28px"><?php echo esc_html( $info[0] ); ?> <a class="button button-small" target="_blank" href="<?php echo esc_url( $preview( $type ) ); ?>">Voorbeeld bekijken</a></h3>
				<p class="description"><?php echo esc_html( $info[1] ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th><label for="m-<?php echo esc_attr( $type ); ?>-s">Onderwerp</label></th><td><input class="large-text" id="m-<?php echo esc_attr( $type ); ?>-s" name="krv_email[<?php echo esc_attr( $type ); ?>_subject]" value="<?php echo esc_attr( $texts[ $type . '_subject' ] ); ?>"></td></tr>
					<tr><th><label for="m-<?php echo esc_attr( $type ); ?>-h">Kop</label></th><td><input class="large-text" id="m-<?php echo esc_attr( $type ); ?>-h" name="krv_email[<?php echo esc_attr( $type ); ?>_heading]" value="<?php echo esc_attr( $texts[ $type . '_heading' ] ); ?>"></td></tr>
					<tr><th><label for="m-<?php echo esc_attr( $type ); ?>-i">Bericht</label></th><td><textarea class="large-text" rows="5" id="m-<?php echo esc_attr( $type ); ?>-i" name="krv_email[<?php echo esc_attr( $type ); ?>_intro]"><?php echo esc_textarea( $texts[ $type . '_intro' ] ); ?></textarea></td></tr>
				</table>
			<?php endforeach; ?>
			<h3 style="margin-top:28px">Afsluiting (alle e-mails)</h3>
			<table class="form-table" role="presentation">
				<tr><th><label for="m-sig">Groet / ondertekening</label></th><td><textarea class="large-text" rows="3" id="m-sig" name="krv_email[signature]"><?php echo esc_textarea( $texts['signature'] ); ?></textarea></td></tr>
				<tr><th><label for="m-foot">Voettekst</label></th><td><input class="large-text" id="m-foot" name="krv_email[footer]" value="<?php echo esc_attr( $texts['footer'] ); ?>"></td></tr>
			</table>
			<p class="description">Bij het bevestigen of annuleren van een boeking kun je daarnaast een <strong>persoonlijk bericht</strong> voor die ene klant meesturen.</p>

			<?php submit_button( 'Instellingen opslaan' ); ?>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=krv_email_test' ), 'krv_email_test' ) ); ?>">Stuur testmail naar mij</a>
				<span class="description">Verstuurt de e-mail "Reservering bevestigd" (met de laatste bestelling als voorbeeld) naar <?php echo esc_html( wp_get_current_user()->user_email ); ?>. Sla eerst je wijzigingen op.</span></p>
		</form>
	</div>
	<?php
}

/* Voorbeeld van een e-mail in de browser. */
add_action(
	'admin_post_krv_email_preview',
	function () {
		check_admin_referer( 'krv_email_preview' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		$type = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : 'received';
		$type = in_array( $type, array( 'received', 'confirmed', 'cancelled' ), true ) ? $type : 'received';
		list( $bookings, $request_id ) = krv_email_sample_bookings();
		$a = krv_customer_email_args( $type, $bookings, $request_id, 'confirmed' === $type ? 'Voorbeeld van een persoonlijk bericht: we bezorgen zaterdag rond 9:00 uur. Veel plezier!' : '' );
		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<div style="font:14px sans-serif;background:#002533;color:#fff;padding:10px 16px">Voorbeeld · Onderwerp: <strong>' . esc_html( $a['subject'] ) . '</strong></div>';
		echo krv_render_email( $a ); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
);

/* Testmail naar de ingelogde beheerder. */
add_action(
	'admin_post_krv_email_test',
	function () {
		check_admin_referer( 'krv_email_test' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		list( $bookings, $request_id ) = krv_email_sample_bookings();
		$a    = krv_customer_email_args( 'confirmed', $bookings, $request_id, 'Dit is een testmail vanuit KR Verhuur.' );
		$sent = krv_send_email( wp_get_current_user()->user_email, '[Test] ' . $a['subject'], $a );
		wp_safe_redirect( admin_url( 'edit.php?post_type=kr_booking&page=krv-settings&testmail=' . ( $sent ? 'ok' : 'fail' ) . '#emails' ) );
		exit;
	}
);
