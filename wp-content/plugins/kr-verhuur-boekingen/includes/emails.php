<?php
/**
 * E-mails naar klant en beheerder (HTML in de huisstijl, met tekstversie).
 *
 * Teksten zijn aan te passen via Boekingen → Instellingen → E-mails.
 * Placeholders: {voornaam} {naam} {bestelnummer} {bedrijf} {telefoon} {email} {totaal}
 */

defined( 'ABSPATH' ) || exit;

/* ===========================================================================
 * Teksten
 * ========================================================================= */

function krv_email_text_defaults() {
	return array(
		'received_subject'  => 'We hebben je bestelling ontvangen – {bestelnummer}',
		'received_heading'  => 'Bedankt voor je bestelling, {voornaam}!',
		'received_intro'    => "Wat leuk dat je bij ons huurt! We hebben je bestelling goed ontvangen en kijken nu of alles op de gekozen dagen voor je klaarstaat.\n\nJe hoort zo snel mogelijk van ons, meestal binnen één werkdag. Hieronder vind je alles nog even op een rij.",
		'confirmed_subject' => 'Je reservering is bevestigd – {bestelnummer}',
		'confirmed_heading' => 'Alles staat voor je klaar, {voornaam}!',
		'confirmed_intro'   => "Goed nieuws: je reservering is bevestigd. We hebben er zin in!\n\nHieronder vind je alle details. Klopt er iets niet of wil je nog iets wijzigen? Laat het ons gerust weten.",
		'cancelled_subject' => 'Je reservering is geannuleerd – {bestelnummer}',
		'cancelled_heading' => 'Je reservering is geannuleerd',
		'cancelled_intro'   => "Beste {voornaam},\n\nJammer dat het deze keer niet doorgaat: je reservering is geannuleerd. Wil je een andere datum proberen of heb je vragen? Neem gerust contact met ons op, we denken graag met je mee.",
		'signature'         => "Hartelijke groet,\n{bedrijf}",
		'footer'            => 'Vragen? Beantwoord deze e-mail of bel ons op {telefoon}.',
	);
}

function krv_email_texts() {
	$saved = get_option( 'krv_email_texts', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), krv_email_text_defaults() );
}

/** Beschikbare placeholders met uitleg (voor het instellingenscherm). */
function krv_email_placeholder_help() {
	return array(
		'{voornaam}'     => 'Voornaam van de klant',
		'{naam}'         => 'Volledige naam',
		'{bestelnummer}' => 'Bestelnummer, bijv. KR-00042',
		'{bedrijf}'      => 'Bedrijfsnaam',
		'{telefoon}'     => 'Jullie telefoonnummer',
		'{email}'        => 'Jullie e-mailadres',
		'{totaal}'       => 'Totaalbedrag van de bestelling',
	);
}

/** Waarden voor de placeholders. */
function krv_email_vars( $bookings, $request_id ) {
	$first = $bookings ? $bookings[0] : array( 'name' => '' );
	$name  = trim( (string) $first['name'] );
	$parts = preg_split( '/\s+/', $name );
	return array(
		'{voornaam}'     => $parts && '' !== $parts[0] ? $parts[0] : $name,
		'{naam}'         => $name,
		'{bestelnummer}' => $request_id,
		'{bedrijf}'      => krv_setting( 'company_name' ),
		'{telefoon}'     => krv_setting( 'phone' ),
		'{email}'        => krv_setting( 'email' ),
		'{totaal}'       => krv_euro( array_sum( wp_list_pluck( $bookings, 'total' ) ) ),
	);
}

function krv_fill( $text, $vars ) {
	return strtr( (string) $text, $vars );
}

/* ===========================================================================
 * Onderdelen
 * ========================================================================= */

/** Logo voor e-mails: eigen logo uit de Customizer, anders het meegeleverde PNG-logo. */
function krv_email_logo_url() {
	$id = get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$src = wp_get_attachment_image_url( $id, 'medium' );
		if ( $src ) {
			return $src;
		}
	}
	return KRV_URL . 'assets/email-logo-dark.png';
}

/** Bedrag volgens de btw-weergave (keuze van de klant bij het bestellen). */
function krv_mail_amount( $incl ) {
	return krv_euro( krv_display_amount( $incl ) );
}

/** Tekst met regeleinden → veilige HTML-alinea's. */
function krv_email_paragraphs( $text, $style ) {
	$out = '';
	foreach ( preg_split( "/\n\s*\n/", trim( (string) $text ) ) as $p ) {
		if ( '' !== trim( $p ) ) {
			$out .= '<p style="' . $style . '">' . nl2br( esc_html( trim( $p ) ) ) . '</p>';
		}
	}
	return $out;
}

/** Tabel met de gehuurde artikelen. */
function krv_email_items_html( $bookings ) {
	$html = '';
	foreach ( $bookings as $b ) {
		$html .= '<tr><td style="padding:18px 0;border-top:1px solid #e2e9ed;">' .
			'<div style="font-size:16px;font-weight:800;color:#002533;">' . esc_html( ( $b['quantity'] > 1 ? $b['quantity'] . '× ' : '' ) . $b['product_name'] ) . '</div>' .
			'<div style="font-size:14px;color:#3f8469;font-weight:700;margin:4px 0 8px;">&#128197; ' . esc_html( krv_pretty_period( $b['start'], $b['end'] ) ) . '</div>' .
			'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#5d7280;">';
		foreach ( (array) $b['lines'] as $l ) {
			$html .= '<tr><td style="padding:2px 0;">' . esc_html( $l['label'] ) . '</td><td align="right" style="padding:2px 0;white-space:nowrap;">' . esc_html( krv_mail_amount( $l['amount'] ) ) . '</td></tr>';
		}
		$html .= '</table></td>' .
			'<td valign="top" align="right" style="padding:18px 0 18px 16px;border-top:1px solid #e2e9ed;font-size:16px;font-weight:800;color:#002533;white-space:nowrap;">' . esc_html( krv_mail_amount( $b['total'] ) ) . '</td></tr>';
	}
	return $html;
}

/** Totalen met btw-opbouw. */
function krv_email_totals_html( $bookings ) {
	$total   = array_sum( wp_list_pluck( $bookings, 'total' ) );
	$deposit = array_sum( wp_list_pluck( $bookings, 'deposit' ) );
	$vat     = krv_vat_breakdown( $total );
	$row     = function ( $label, $value, $strong = false ) {
		$st = $strong ? 'font-size:18px;font-weight:900;color:#002533;padding-top:10px;' : 'font-size:14px;color:#5d7280;';
		return '<tr><td style="padding:3px 0;' . $st . '">' . esc_html( $label ) . '</td><td align="right" style="padding:3px 0;white-space:nowrap;' . $st . '">' . esc_html( $value ) . '</td></tr>';
	};
	$html  = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f9fa;border-radius:12px;padding:16px 20px;">';
	$html .= $row( 'Subtotaal excl. btw', krv_euro( $vat['excl'] ) );
	$html .= $row( 'Btw ' . $vat['rate'] . '%', krv_euro( $vat['vat'] ) );
	$html .= $row( 'Totaal incl. btw', krv_euro( $vat['incl'] ), true );
	if ( $deposit > 0 ) {
		$html .= $row( 'Borg (apart, geen btw)', krv_euro( $deposit ) );
	}
	return $html . '</table>';
}

/** Blok met klantgegevens. */
function krv_email_customer_html( $b ) {
	$rows = array(
		'Naam'     => $b['name'],
		'E-mail'   => $b['email'],
		'Telefoon' => $b['phone'],
		'Adres'    => $b['address'],
		'Opmerkingen' => $b['notes'],
	);
	$html = '';
	foreach ( $rows as $label => $value ) {
		if ( '' !== trim( (string) $value ) ) {
			$html .= '<tr><td style="padding:3px 16px 3px 0;color:#5d7280;font-size:14px;vertical-align:top;white-space:nowrap;">' . esc_html( $label ) . '</td><td style="padding:3px 0;font-size:14px;color:#0f2530;">' . nl2br( esc_html( $value ) ) . '</td></tr>';
		}
	}
	return '<table role="presentation" cellpadding="0" cellspacing="0">' . $html . '</table>';
}

/* ===========================================================================
 * Template
 * ========================================================================= */

/**
 * Volledige HTML-mail.
 *
 * @param array $a heading, intro, personal, bookings, request_id, show_customer, button_url, button_label, preheader, signature, footer
 */
function krv_render_email( $a ) {
	$a = wp_parse_args(
		$a,
		array(
			'heading'       => '',
			'intro'         => '',
			'personal'      => '',
			'bookings'      => array(),
			'request_id'    => '',
			'show_customer' => true,
			'button_url'    => '',
			'button_label'  => '',
			'preheader'     => '',
			'signature'     => '',
			'footer'        => '',
			'accent'        => '#519f81',
			'for_admin'     => false,
		)
	);
	$company = krv_setting( 'company_name' );
	$phone   = krv_setting( 'phone' );
	$email   = krv_setting( 'email' );
	$p_style = 'margin:0 0 14px;font-size:16px;line-height:1.6;color:#0f2530;';

	ob_start();
	?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?php echo esc_html( $company ); ?></title>
</head>
<body style="margin:0;padding:0;background:#eef3f5;font-family:'Nunito','Segoe UI',Helvetica,Arial,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;"><?php echo esc_html( $a['preheader'] ); ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f5;">
<tr><td align="center" style="padding:28px 12px;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;">
		<tr><td style="height:6px;background:<?php echo esc_attr( $a['accent'] ); ?>;font-size:0;line-height:0;">&nbsp;</td></tr>
		<tr><td style="padding:28px 32px 8px;">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( krv_email_logo_url() ); ?>" width="200" alt="<?php echo esc_attr( $company ); ?>" style="display:block;border:0;width:200px;max-width:60%;height:auto;"></a>
		</td></tr>
		<tr><td style="padding:20px 32px 4px;">
			<h1 style="margin:0 0 16px;font-size:26px;line-height:1.25;font-weight:900;color:#002533;"><?php echo esc_html( $a['heading'] ); ?></h1>
			<?php echo krv_email_paragraphs( $a['intro'], $p_style ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</td></tr>
		<?php if ( '' !== trim( (string) $a['personal'] ) ) : ?>
		<tr><td style="padding:4px 32px 12px;">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e6f3ed;border-left:4px solid #519f81;border-radius:10px;">
				<tr><td style="padding:16px 20px;">
					<div style="font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#3f8469;margin-bottom:6px;">Persoonlijk bericht</div>
					<?php echo krv_email_paragraphs( $a['personal'], 'margin:0 0 8px;font-size:15px;line-height:1.6;color:#0f2530;' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</td></tr>
			</table>
		</td></tr>
		<?php endif; ?>
		<?php if ( $a['bookings'] ) : ?>
		<tr><td style="padding:16px 32px 0;">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
				<tr><td style="font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#5d7280;padding-bottom:4px;"><?php echo $a['for_admin'] ? 'Bestelling' : 'Je bestelling'; ?></td>
					<td align="right" style="font-size:13px;font-weight:800;color:#002533;padding-bottom:4px;"><?php echo esc_html( $a['request_id'] ); ?></td></tr>
				<?php echo krv_email_items_html( $a['bookings'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</table>
		</td></tr>
		<tr><td style="padding:8px 32px 8px;"><?php echo krv_email_totals_html( $a['bookings'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
		<?php endif; ?>
		<?php if ( $a['show_customer'] && $a['bookings'] ) : ?>
		<tr><td style="padding:18px 32px 0;">
			<div style="font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#5d7280;margin-bottom:8px;"><?php echo $a['for_admin'] ? 'Klantgegevens' : 'Je gegevens'; ?></div>
			<?php echo krv_email_customer_html( $a['bookings'][0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</td></tr>
		<?php endif; ?>
		<?php if ( $a['button_url'] ) : ?>
		<tr><td style="padding:24px 32px 28px;">
			<a href="<?php echo esc_url( $a['button_url'] ); ?>" style="display:inline-block;background:#519f81;color:#ffffff;text-decoration:none;font-weight:800;font-size:15px;padding:13px 26px;border-radius:999px;"><?php echo esc_html( $a['button_label'] ); ?></a>
		</td></tr>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $a['signature'] ) ) : ?>
		<tr><td style="padding:26px 32px 4px;"><?php echo krv_email_paragraphs( $a['signature'], $p_style ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
		<?php endif; ?>
		<tr><td style="padding:22px 32px;background:#002533;color:#dff1fb;font-size:13px;line-height:1.6;">
			<?php if ( '' !== trim( (string) $a['footer'] ) ) : ?>
				<div style="margin-bottom:8px;"><?php echo esc_html( $a['footer'] ); ?></div>
			<?php endif; ?>
			<strong style="color:#ffffff;"><?php echo esc_html( $company ); ?></strong>
			<?php if ( $phone ) : ?> · <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>" style="color:#dff1fb;"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
			<?php if ( $email ) : ?> · <a href="mailto:<?php echo esc_attr( $email ); ?>" style="color:#dff1fb;"><?php echo esc_html( $email ); ?></a><?php endif; ?>
			<br><a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:#519f81;font-weight:700;"><?php echo esc_html( preg_replace( '#^https?://#', '', untrailingslashit( home_url() ) ) ); ?></a>
		</td></tr>
	</table>
</td></tr>
</table>
</body>
</html>
	<?php
	return ob_get_clean();
}

/* ===========================================================================
 * Tekstversie (voor e-mailprogramma's zonder HTML)
 * ========================================================================= */

function krv_booking_item_text( $b ) {
	$out = array(
		$b['product_name'] . ( $b['quantity'] > 1 ? ' (' . $b['quantity'] . '×)' : '' ),
		'Periode: ' . krv_pretty_period( $b['start'], $b['end'] ),
	);
	foreach ( (array) $b['lines'] as $l ) {
		$out[] = '  ' . $l['label'] . ': ' . krv_mail_amount( $l['amount'] );
	}
	$out[] = 'Subtotaal: ' . krv_mail_amount( $b['total'] ) . ' ' . krv_vat_label();
	return implode( "\n", $out );
}

function krv_order_summary_text( $bookings ) {
	$parts = array_map( 'krv_booking_item_text', $bookings );
	$vat   = krv_vat_breakdown( array_sum( wp_list_pluck( $bookings, 'total' ) ) );
	$txt   = implode( "\n\n", $parts ) . "\n\n" .
		'Totaal excl. btw: ' . krv_euro( $vat['excl'] ) . "\n" .
		'Btw ' . $vat['rate'] . '%: ' . krv_euro( $vat['vat'] ) . "\n" .
		'Totaal incl. btw: ' . krv_euro( $vat['incl'] );
	$deposit = array_sum( wp_list_pluck( $bookings, 'deposit' ) );
	if ( $deposit > 0 ) {
		$txt .= "\nBorg: " . krv_euro( $deposit );
	}
	$b    = $bookings[0];
	$txt .= "\n\nNaam: " . $b['name'] . "\nE-mail: " . $b['email'] . "\nTelefoon: " . $b['phone'];
	if ( $b['address'] ) {
		$txt .= "\nAdres: " . $b['address'];
	}
	if ( $b['notes'] ) {
		$txt .= "\nOpmerkingen: " . $b['notes'];
	}
	return $txt;
}

function krv_email_text_version( $a ) {
	$txt = $a['heading'] . "\n\n" . trim( $a['intro'] );
	if ( ! empty( $a['personal'] ) ) {
		$txt .= "\n\nPersoonlijk bericht:\n" . trim( $a['personal'] );
	}
	if ( ! empty( $a['bookings'] ) ) {
		$txt .= "\n\nBestelnummer: " . $a['request_id'] . "\n\n" . krv_order_summary_text( $a['bookings'] );
	}
	if ( ! empty( $a['button_url'] ) ) {
		$txt .= "\n\n" . $a['button_label'] . ': ' . $a['button_url'];
	}
	if ( ! empty( $a['signature'] ) ) {
		$txt .= "\n\n" . trim( $a['signature'] );
	}
	return $txt . "\n\n" . krv_setting( 'company_name' ) . ' · ' . krv_setting( 'phone' ) . ' · ' . krv_setting( 'email' );
}

/* ===========================================================================
 * Versturen
 * ========================================================================= */

/**
 * HTML-mail versturen met tekstversie als alternatief.
 *
 * @param array $a Zie krv_render_email().
 */
function krv_send_email( $to, $subject, $a, $reply_to = '' ) {
	$html = krv_render_email( $a );
	$text = krv_email_text_version( wp_parse_args( $a, array( 'personal' => '', 'bookings' => array(), 'button_url' => '', 'signature' => '' ) ) );

	$set_alt = function ( $phpmailer ) use ( $text ) {
		$phpmailer->AltBody = $text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	};
	add_action( 'phpmailer_init', $set_alt );

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$from    = krv_setting( 'email' );
	if ( is_email( $from ) ) {
		$headers[] = 'From: ' . wp_specialchars_decode( krv_setting( 'company_name' ), ENT_QUOTES ) . ' <' . $from . '>';
	}
	if ( $reply_to ) {
		$headers[] = 'Reply-To: ' . $reply_to;
	}
	$sent = wp_mail( $to, $subject, $html, $headers );
	remove_action( 'phpmailer_init', $set_alt );
	return $sent;
}

/** Klantmail voor een bestelling (ontvangen / bevestigd / geannuleerd). */
function krv_customer_email_args( $type, $bookings, $request_id, $personal = '' ) {
	$t    = krv_email_texts();
	$vars = krv_email_vars( $bookings, $request_id );
	return array(
		'subject'   => krv_fill( $t[ $type . '_subject' ], $vars ),
		'heading'   => krv_fill( $t[ $type . '_heading' ], $vars ),
		'intro'     => krv_fill( $t[ $type . '_intro' ], $vars ),
		'personal'  => $personal,
		'bookings'  => $bookings,
		'request_id'=> $request_id,
		'preheader' => wp_strip_all_tags( krv_fill( $t[ $type . '_heading' ], $vars ) ) . ' – ' . $request_id,
		'signature' => krv_fill( $t['signature'], $vars ),
		'footer'    => krv_fill( $t['footer'], $vars ),
		'accent'    => 'cancelled' === $type ? '#c2413b' : '#519f81',
	);
}

/* Nieuwe bestelling vanaf de website → mail naar de beheerder en naar de klant. */
add_action(
	'krv_request_created',
	function ( $request_id, $ids ) {
		$bookings = array_values( array_filter( array_map( 'krv_get_booking', (array) $ids ) ) );
		if ( ! $bookings ) {
			return;
		}
		$first = $bookings[0];
		$names = implode( ', ', wp_list_pluck( $bookings, 'product_name' ) );

		// Beheerder.
		krv_send_email(
			krv_setting( 'notify_email' ),
			'Nieuwe bestelling ' . $request_id . ' – ' . $first['name'],
			array(
				'heading'      => 'Nieuwe bestelling van ' . $first['name'],
				'intro'        => 'Er is een nieuwe bestelling binnengekomen met ' . count( $bookings ) . ( 1 === count( $bookings ) ? ' artikel' : ' artikelen' ) . ': ' . $names . ".\n\nBekijk de beschikbaarheid en bevestig de bestelling in het beheer.",
				'bookings'     => $bookings,
				'request_id'   => $request_id,
				'button_url'   => admin_url( 'post.php?action=edit&post=' . (int) $first['id'] ),
				'button_label' => 'Bestelling bekijken en bevestigen',
				'for_admin'    => true,
				'preheader'    => $first['name'] . ' – ' . $names,
			),
			$first['name'] . ' <' . $first['email'] . '>'
		);

		// Klant.
		if ( is_email( $first['email'] ) ) {
			$a = krv_customer_email_args( 'received', $bookings, $request_id );
			krv_send_email( $first['email'], $a['subject'], $a );
		}
	},
	10,
	2
);

/**
 * Statusmail aan de klant (bevestigd of geannuleerd) voor één of meer boekingen
 * van dezelfde bestelling, met optioneel persoonlijk bericht.
 *
 * @param int|int[] $ids
 */
function krv_send_status_mail( $ids, $status, $personal = '' ) {
	if ( ! in_array( $status, array( 'confirmed', 'cancelled' ), true ) ) {
		return false;
	}
	$bookings = array_values( array_filter( array_map( 'krv_get_booking', (array) $ids ) ) );
	if ( ! $bookings || ! is_email( $bookings[0]['email'] ) ) {
		return false;
	}
	$request_id = $bookings[0]['request_id'] ? $bookings[0]['request_id'] : krv_booking_ref( $bookings[0]['id'] );
	$a          = krv_customer_email_args( $status, $bookings, $request_id, $personal );
	$sent       = krv_send_email( $bookings[0]['email'], $a['subject'], $a );
	if ( $sent ) {
		foreach ( $bookings as $b ) {
			krv_log( $b['id'], 'E-mail "' . krv_statuses()[ $status ] . '" verstuurd naar klant' . ( '' !== trim( $personal ) ? ' (met persoonlijk bericht)' : '' ) . '.' );
		}
	}
	return $sent;
}

/** Voorbeeldgegevens voor de preview in het beheer. */
function krv_email_sample_bookings() {
	$latest = get_posts( array( 'post_type' => 'kr_booking', 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $latest ) {
		$b   = krv_get_booking( $latest[0] );
		$ids = $b['request_id'] ? krv_request_bookings( $b['request_id'] ) : array( $b['id'] );
		return array( array_values( array_filter( array_map( 'krv_get_booking', $ids ) ) ), $b['request_id'] ? $b['request_id'] : krv_booking_ref( $b['id'] ) );
	}
	$start = wp_date( 'Y-m-d', strtotime( '+14 days' ) );
	$end   = wp_date( 'Y-m-d', strtotime( '+15 days' ) );
	return array(
		array(
			array(
				'id' => 0, 'product_name' => 'Photobooth Classic', 'quantity' => 1, 'start' => $start, 'end' => $end, 'total' => 389, 'deposit' => 150,
				'lines' => array( array( 'label' => 'Huur 2 dagen', 'amount' => 374 ), array( 'label' => 'Props & accessoires', 'amount' => 15 ) ),
				'name' => 'Sanne de Vries', 'email' => 'sanne@example.com', 'phone' => '06 12 34 56 78', 'address' => 'Dorpsstraat 1, Ergens', 'notes' => 'Graag voor 10:00 bezorgen.',
			),
		),
		'KR-00042',
	);
}
