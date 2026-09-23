<?php
/**
 * REST API voor de website.
 *
 *   GET  /wp-json/kr/v1/availability/<product_id>  → { "2026-10-03": 0, ... } (resterende voorraad)
 *   POST /wp-json/kr/v1/cart/validate              → controleer winkelwagenregels (prijs + beschikbaarheid)
 *   POST /wp-json/kr/v1/bookings                   → bestelling plaatsen: alle regels uit de winkelwagen in één keer
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'krv_register_rest' );

function krv_register_rest() {
	register_rest_route(
		'kr/v1',
		'/availability/(?P<id>\d+)',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => function ( WP_REST_Request $r ) {
				$id = (int) $r['id'];
				if ( ! krv_get_product( $id ) || 'publish' !== get_post_status( $id ) ) {
					return new WP_Error( 'krv_product', 'Onbekend artikel.', array( 'status' => 404 ) );
				}
				$res = rest_ensure_response( (object) krv_availability( $id ) );
				$res->header( 'Cache-Control', 'no-store' );
				return $res;
			},
		)
	);

	register_rest_route(
		'kr/v1',
		'/cart/validate',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'krv_rest_validate_cart',
		)
	);

	register_rest_route(
		'kr/v1',
		'/bookings',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'krv_rest_create_booking',
		)
	);
}

function krv_rest_create_booking( WP_REST_Request $r ) {
	$body = $r->get_json_params();
	if ( ! is_array( $body ) ) {
		return new WP_Error( 'krv_invalid', 'Ongeldige aanvraag.', array( 'status' => 400 ) );
	}

	// Honeypot tegen spam-bots.
	if ( ! empty( $body['website'] ) ) {
		return new WP_Error( 'krv_invalid', 'Ongeldige aanvraag.', array( 'status' => 400 ) );
	}

	// Eenvoudige rate limit: max 10 aanvragen per uur per IP.
	$ip_key = 'krv_rl_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 10 ) {
		return new WP_Error( 'krv_rate', 'Te veel aanvragen. Probeer het later opnieuw of bel ons.', array( 'status' => 429 ) );
	}

	$customer = isset( $body['customer'] ) && is_array( $body['customer'] ) ? $body['customer'] : array();
	$items    = isset( $body['items'] ) && is_array( $body['items'] ) ? array_slice( $body['items'], 0, 20 ) : array();
	if ( ! $items ) {
		return new WP_Error( 'krv_invalid', 'Geen artikelen gekozen.', array( 'status' => 400 ) );
	}

	// Eerst alles valideren, dan pas opslaan (alles of niets).
	$valid = array();
	foreach ( $items as $i => $item ) {
		$v = krv_validate_booking( array_merge( (array) $item, $customer ), array( 'pending' => $valid ) );
		if ( is_wp_error( $v ) ) {
			$name   = krv_item_name( $item );
			$prefix = ( 'krv_customer' === $v->get_error_code() || ! $name || false !== strpos( $v->get_error_message(), $name ) ) ? '' : $name . ': ';
			return new WP_Error(
				$v->get_error_code(),
				$prefix . $v->get_error_message(),
				array( 'status' => 422, 'item' => $i )
			);
		}
		$valid[] = $v;
	}

	$ids        = array();
	$request_id = '';
	foreach ( $valid as $v ) {
		$id = krv_create_booking( $v, 'pending', 'website', $request_id );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		if ( ! $request_id ) {
			$request_id = krv_booking_ref( $id );
		}
		$ids[] = $id;
	}

	do_action( 'krv_request_created', $request_id, $ids );
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	return array(
		'ok'        => true,
		'reference' => $request_id,
		'total'     => round( array_sum( wp_list_pluck( $valid, 'total' ) ), 2 ),
		'bookings'  => array_map( 'krv_booking_ref', $ids ),
	);
}

function krv_item_name( $item ) {
	$id = is_array( $item ) && isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
	return $id && 'kr_product' === get_post_type( $id ) ? get_the_title( $id ) : '';
}

/**
 * Winkelwagen controleren zonder op te slaan: actuele prijs en beschikbaarheid per regel.
 * Regels worden in volgorde gecontroleerd, zodat ook onderlinge overlap in de winkelwagen telt.
 */
function krv_rest_validate_cart( WP_REST_Request $r ) {
	$body  = $r->get_json_params();
	$items = isset( $body['items'] ) && is_array( $body['items'] ) ? array_slice( $body['items'], 0, 20 ) : array();
	$out   = array();
	$valid = array();
	foreach ( $items as $item ) {
		$v = krv_validate_booking( (array) $item, array( 'pending' => $valid, 'skip_customer' => true ) );
		if ( is_wp_error( $v ) ) {
			$out[] = array( 'ok' => false, 'error' => $v->get_error_message() );
			continue;
		}
		$valid[] = $v;
		$out[]   = array(
			'ok'       => true,
			'days'     => $v['days'],
			'quantity' => $v['quantity'],
			'lines'    => $v['lines'],
			'total'    => $v['total'],
			'deposit'  => $v['deposit'],
		);
	}
	$res = rest_ensure_response( array( 'items' => $out ) );
	$res->header( 'Cache-Control', 'no-store' );
	return $res;
}
