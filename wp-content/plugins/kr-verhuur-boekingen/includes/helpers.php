<?php
/**
 * Algemene hulpfuncties.
 */

defined( 'ABSPATH' ) || exit;

/** Bedrag als "€ 12,50". */
function krv_euro( $amount ) {
	return '€ ' . number_format_i18n( (float) $amount, 2 );
}

/** Controleer een datum in formaat YYYY-MM-DD. */
function krv_valid_date( $date ) {
	if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return false;
	}
	list( $y, $m, $d ) = array_map( 'intval', explode( '-', $date ) );
	return checkdate( $m, $d, $y );
}

/** Aantal dagen tussen twee datums, inclusief begin en eind. */
function krv_days_between( $start, $end ) {
	$a = new DateTimeImmutable( $start );
	$b = new DateTimeImmutable( $end );
	return (int) $a->diff( $b )->format( '%r%a' ) + 1;
}

/** Alle datums (YYYY-MM-DD) van begin t/m eind. */
function krv_date_range( $start, $end ) {
	$out = array();
	$d   = new DateTimeImmutable( $start );
	$e   = new DateTimeImmutable( $end );
	while ( $d <= $e ) {
		$out[] = $d->format( 'Y-m-d' );
		$d     = $d->modify( '+1 day' );
	}
	return $out;
}

/** Vandaag in de tijdzone van de site. */
function krv_today() {
	return wp_date( 'Y-m-d' );
}

/** Datum leesbaar in het Nederlands, bijv. "za 26 september 2026". */
function krv_pretty_date( $date ) {
	if ( ! krv_valid_date( $date ) ) {
		return '';
	}
	$ts = ( new DateTimeImmutable( $date, wp_timezone() ) )->getTimestamp();
	return wp_date( 'D j F Y', $ts );
}

/** Periode leesbaar, bijv. "26 sep t/m 29 sep 2026 (4 dagen)". */
function krv_pretty_period( $start, $end ) {
	$days = krv_days_between( $start, $end );
	$txt  = krv_pretty_date( $start );
	if ( $days > 1 ) {
		$txt .= ' t/m ' . krv_pretty_date( $end );
	}
	return $txt . ' (' . $days . ' ' . ( 1 === $days ? 'dag' : 'dagen' ) . ')';
}
