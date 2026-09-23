<?php
/**
 * E-mails naar klant en beheerder.
 */

defined( 'ABSPATH' ) || exit;

/** Tekstuele samenvatting van een boeking. */
function krv_booking_summary_text( $b ) {
	$extras = krv_extras();
	$out    = array(
		'Referentie: ' . krv_booking_ref( $b['id'] ),
		'Artikel: ' . $b['product_name'] . ( $b['quantity'] > 1 ? ' (' . $b['quantity'] . '×)' : '' ),
		'Periode: ' . krv_pretty_period( $b['start'], $b['end'] ),
		'',
	);
	foreach ( (array) $b['lines'] as $l ) {
		$out[] = '  ' . $l['label'] . ': ' . krv_euro( $l['amount'] );
	}
	$out[] = 'Totaal: ' . krv_euro( $b['total'] ) . ' (incl. btw)';
	if ( $b['deposit'] > 0 ) {
		$out[] = 'Borg: ' . krv_euro( $b['deposit'] );
	}
	$out[] = '';
	$out[] = 'Naam: ' . $b['name'];
	$out[] = 'E-mail: ' . $b['email'];
	$out[] = 'Telefoon: ' . $b['phone'];
	if ( $b['address'] ) {
		$out[] = 'Adres: ' . $b['address'];
	}
	if ( $b['notes'] ) {
		$out[] = 'Opmerkingen: ' . $b['notes'];
	}
	return implode( "\n", $out );
}

function krv_mail_headers() {
	$from = krv_setting( 'email' );
	return array( 'Reply-To: ' . krv_setting( 'company_name' ) . ' <' . $from . '>' );
}

/* Nieuwe aanvraag vanaf de website → beheerder + klant. */
add_action(
	'krv_booking_created',
	function ( $id ) {
		$b = krv_get_booking( $id );
		if ( ! $b || 'website' !== $b['source'] ) {
			return;
		}
		$company = krv_setting( 'company_name' );
		$summary = krv_booking_summary_text( $b );

		wp_mail(
			krv_setting( 'notify_email' ),
			'Nieuwe reserveringsaanvraag ' . krv_booking_ref( $id ) . ' – ' . $b['product_name'],
			"Er is een nieuwe reserveringsaanvraag binnengekomen.\n\n" . $summary .
			"\n\nBekijk en bevestig de boeking:\n" . admin_url( 'post.php?action=edit&post=' . $id ),
			array( 'Reply-To: ' . $b['name'] . ' <' . $b['email'] . '>' )
		);

		if ( is_email( $b['email'] ) ) {
			wp_mail(
				$b['email'],
				'We hebben je aanvraag ontvangen – ' . $company,
				'Beste ' . $b['name'] . ",\n\nBedankt voor je reserveringsaanvraag! We controleren de beschikbaarheid en sturen je zo snel mogelijk een bevestiging.\n\n" .
				$summary . "\n\nMet vriendelijke groet,\n" . $company . "\n" . krv_setting( 'phone' ),
				krv_mail_headers()
			);
		}
	}
);

/** Mail aan klant bij bevestigen of annuleren (alleen als de beheerder dat aanvinkt). */
function krv_send_status_mail( $id, $status ) {
	$b = krv_get_booking( $id );
	if ( ! $b || ! is_email( $b['email'] ) ) {
		return false;
	}
	$company = krv_setting( 'company_name' );
	if ( 'confirmed' === $status ) {
		$subject = 'Je reservering is bevestigd – ' . $company;
		$intro   = 'Goed nieuws: je reservering is bevestigd! Hieronder vind je de gegevens.';
	} elseif ( 'cancelled' === $status ) {
		$subject = 'Je reservering is geannuleerd – ' . $company;
		$intro   = 'Je reservering is geannuleerd. Heb je vragen? Neem gerust contact met ons op.';
	} else {
		return false;
	}
	$sent = wp_mail(
		$b['email'],
		$subject,
		'Beste ' . $b['name'] . ",\n\n" . $intro . "\n\n" . krv_booking_summary_text( $b ) .
		"\n\nMet vriendelijke groet,\n" . $company . "\n" . krv_setting( 'phone' ),
		krv_mail_headers()
	);
	if ( $sent ) {
		krv_log( $id, 'E-mail "' . krv_statuses()[ $status ] . '" verstuurd naar klant.' );
	}
	return $sent;
}
