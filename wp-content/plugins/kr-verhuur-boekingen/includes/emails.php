<?php
/**
 * E-mails naar klant en beheerder.
 */

defined( 'ABSPATH' ) || exit;

/** Bedrag voor e-mails, volgens de btw-weergave van de website. */
function krv_mail_amount( $incl ) {
	return krv_euro( krv_display_amount( $incl ) );
}

/** Tekstuele samenvatting van één boekingsregel (zonder klantgegevens). */
function krv_booking_item_text( $b ) {
	$out = array(
		$b['product_name'] . ( $b['quantity'] > 1 ? ' (' . $b['quantity'] . '×)' : '' ) . ' – ' . krv_booking_ref( $b['id'] ),
		'Periode: ' . krv_pretty_period( $b['start'], $b['end'] ),
	);
	foreach ( (array) $b['lines'] as $l ) {
		$out[] = '  ' . $l['label'] . ': ' . krv_mail_amount( $l['amount'] );
	}
	$out[] = 'Subtotaal: ' . krv_mail_amount( $b['total'] ) . ' ' . krv_vat_label() . ( $b['deposit'] > 0 ? ' (borg ' . krv_euro( $b['deposit'] ) . ')' : '' );
	return implode( "\n", $out );
}

function krv_customer_text( $b ) {
	$out = array( 'Naam: ' . $b['name'], 'E-mail: ' . $b['email'], 'Telefoon: ' . $b['phone'] );
	if ( $b['address'] ) {
		$out[] = 'Adres: ' . $b['address'];
	}
	if ( $b['notes'] ) {
		$out[] = 'Opmerkingen: ' . $b['notes'];
	}
	return implode( "\n", $out );
}

/** Samenvatting van meerdere boekingen (één bestelling). */
function krv_order_summary_text( $bookings ) {
	$parts   = array();
	$total   = 0;
	$deposit = 0;
	foreach ( $bookings as $b ) {
		$parts[]  = krv_booking_item_text( $b );
		$total   += (float) $b['total'];
		$deposit += (float) $b['deposit'];
	}
	$vat = krv_vat_breakdown( $total );
	$txt = implode( "\n\n", $parts ) . "\n\n" .
		'Totaal excl. btw: ' . krv_euro( $vat['excl'] ) . "\n" .
		'Btw ' . $vat['rate'] . '%: ' . krv_euro( $vat['vat'] ) . "\n" .
		'Totaal incl. btw: ' . krv_euro( $vat['incl'] );
	if ( $deposit > 0 ) {
		$txt .= "\nBorg totaal: " . krv_euro( $deposit );
	}
	return $txt . "\n\n" . krv_customer_text( $bookings[0] );
}

/** Tekstuele samenvatting van één boeking (voor statusmails). */
function krv_booking_summary_text( $b ) {
	return krv_order_summary_text( array( $b ) );
}

function krv_mail_headers() {
	$from = krv_setting( 'email' );
	return array( 'Reply-To: ' . krv_setting( 'company_name' ) . ' <' . $from . '>' );
}

/* Nieuwe bestelling vanaf de website → één mail naar de beheerder en één naar de klant. */
add_action(
	'krv_request_created',
	function ( $request_id, $ids ) {
		$bookings = array_values( array_filter( array_map( 'krv_get_booking', (array) $ids ) ) );
		if ( ! $bookings ) {
			return;
		}
		$first   = $bookings[0];
		$company = krv_setting( 'company_name' );
		$summary = 'Bestelnummer: ' . $request_id . "\n\n" . krv_order_summary_text( $bookings );
		$names   = implode( ', ', wp_list_pluck( $bookings, 'product_name' ) );

		wp_mail(
			krv_setting( 'notify_email' ),
			'Nieuwe bestelling ' . $request_id . ' – ' . $names,
			'Er is een nieuwe bestelling binnengekomen (' . count( $bookings ) . ( 1 === count( $bookings ) ? ' artikel' : ' artikelen' ) . ").\n\n" . $summary .
			"\n\nBekijk en bevestig de boekingen:\n" . admin_url( 'edit.php?post_type=kr_booking&s=' . rawurlencode( $request_id ) ),
			array( 'Reply-To: ' . $first['name'] . ' <' . $first['email'] . '>' )
		);

		if ( is_email( $first['email'] ) ) {
			wp_mail(
				$first['email'],
				'We hebben je bestelling ontvangen – ' . $company,
				'Beste ' . $first['name'] . ",\n\nBedankt voor je bestelling! We controleren de beschikbaarheid en sturen je zo snel mogelijk een bevestiging.\n\n" .
				$summary . "\n\nMet vriendelijke groet,\n" . $company . "\n" . krv_setting( 'phone' ),
				krv_mail_headers()
			);
		}
	},
	10,
	2
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
