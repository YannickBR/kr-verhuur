<?php
/**
 * Beschikbaarheid: hoeveel stuks van een artikel zijn per dag nog vrij?
 */

defined( 'ABSPATH' ) || exit;

/**
 * Boekingen van een artikel die (deels) binnen een periode vallen en de agenda blokkeren.
 *
 * @return int[] booking IDs
 */
function krv_overlapping_bookings( $product_id, $from, $to, $exclude_id = 0 ) {
	return get_posts(
		array(
			'post_type'    => 'kr_booking',
			'post_status'  => 'publish',
			'numberposts'  => -1,
			'fields'       => 'ids',
			'post__not_in' => $exclude_id ? array( (int) $exclude_id ) : array(),
			'meta_query'   => array(
				'relation' => 'AND',
				array( 'key' => '_krv_product_id', 'value' => (int) $product_id ),
				array( 'key' => '_krv_status', 'value' => krv_blocking_statuses(), 'compare' => 'IN' ),
				array( 'key' => '_krv_start', 'value' => $to, 'compare' => '<=' ),
				array( 'key' => '_krv_end', 'value' => $from, 'compare' => '>=' ),
			),
		)
	);
}

/**
 * Gebruik per dag: [ 'YYYY-MM-DD' => aantal verhuurd ].
 */
function krv_usage( $product_id, $from, $to, $exclude_id = 0 ) {
	$usage = array();
	foreach ( krv_overlapping_bookings( $product_id, $from, $to, $exclude_id ) as $bid ) {
		$qty   = max( 1, (int) get_post_meta( $bid, '_krv_quantity', true ) );
		$start = max( $from, get_post_meta( $bid, '_krv_start', true ) );
		$end   = min( $to, get_post_meta( $bid, '_krv_end', true ) );
		if ( ! krv_valid_date( $start ) || ! krv_valid_date( $end ) || $start > $end ) {
			continue;
		}
		foreach ( krv_date_range( $start, $end ) as $d ) {
			$usage[ $d ] = ( isset( $usage[ $d ] ) ? $usage[ $d ] : 0 ) + $qty;
		}
	}
	return $usage;
}

/**
 * Resterende voorraad per dag, alleen voor dagen waarop niet alles vrij is.
 * [ 'YYYY-MM-DD' => resterend aantal (0 = volgeboekt) ]
 */
function krv_availability( $product_id, $from = null, $to = null ) {
	$product = krv_get_product( $product_id );
	if ( ! $product ) {
		return array();
	}
	$from = $from ? $from : krv_today();
	$to   = $to ? $to : wp_date( 'Y-m-d', strtotime( '+' . (int) krv_setting( 'max_days_ahead' ) . ' days' ) );
	$out  = array();
	foreach ( krv_usage( $product_id, $from, $to ) as $d => $used ) {
		$out[ $d ] = max( 0, $product['stock'] - $used );
	}
	ksort( $out );
	return $out;
}

/**
 * Controleer of een periode vrij is.
 *
 * @return true|WP_Error
 */
function krv_check_available( $product_id, $start, $end, $quantity, $exclude_id = 0 ) {
	$product = krv_get_product( $product_id );
	if ( ! $product ) {
		return new WP_Error( 'krv_product', 'Onbekend artikel.' );
	}
	if ( $quantity > $product['stock'] ) {
		return new WP_Error( 'krv_stock', sprintf( 'Er zijn maximaal %d stuks beschikbaar.', $product['stock'] ) );
	}
	foreach ( krv_usage( $product_id, $start, $end, $exclude_id ) as $d => $used ) {
		$left = $product['stock'] - $used;
		if ( $left < $quantity ) {
			return new WP_Error(
				'krv_unavailable',
				$left > 0
					? sprintf( 'Op %s zijn nog maar %d stuks beschikbaar.', krv_pretty_date( $d ), $left )
					: sprintf( '%s is op %s al verhuurd.', $product['name'], krv_pretty_date( $d ) )
			);
		}
	}
	return true;
}
