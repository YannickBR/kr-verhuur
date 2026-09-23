<?php
/**
 * Prijsberekening. Dezelfde regels staan in de theme-JS (alleen voor weergave);
 * de server rekent altijd opnieuw bij het opslaan van een boeking.
 *
 * Alle bedragen die hier uitkomen zijn INCLUSIEF btw (zie krv_entered_to_incl()).
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param array $product  Uitvoer van krv_get_product().
 * @param int   $days     Aantal huurdagen.
 * @param int   $quantity Aantal stuks.
 * @param array $extras   Gekozen extra-keys.
 * @return array { lines: [ {key,label,amount} ], total, deposit }
 */
function krv_calculate_price( $product, $days, $quantity = 1, $extras = array() ) {
	$days     = max( 1, (int) $days );
	$quantity = max( 1, (int) $quantity );
	$all      = krv_extras_incl();
	$lines    = array();

	$rent    = krv_entered_to_incl( ( $product['price_day'] + ( $days - 1 ) * $product['price_extra_day'] ) * $quantity );
	$lines[] = array(
		'key'    => 'rent',
		'label'  => ( $quantity > 1 ? $quantity . '× ' : '' ) . 'Huur ' . $days . ( 1 === $days ? ' dag' : ' dagen' ),
		'amount' => round( $rent, 2 ),
	);

	foreach ( (array) $extras as $key ) {
		if ( ! isset( $all[ $key ] ) ) {
			continue;
		}
		$x       = $all[ $key ];
		$per_day = 'perDay' === $x['type'];
		$lines[] = array(
			'key'    => $key,
			'label'  => $x['label'] . ( $per_day && $days > 1 ? ' (' . $days . ' dagen)' : '' ),
			'amount' => round( (float) $x['price'] * ( $per_day ? $days : 1 ), 2 ),
		);
	}

	return array(
		'lines'   => $lines,
		'total'   => round( array_sum( wp_list_pluck( $lines, 'amount' ) ), 2 ),
		'deposit' => (float) $product['deposit'],
	);
}
