<?php
/**
 * Boekingen: validatie, aanmaken, uitlezen en statuswijzigingen.
 *
 * Een boeking = één artikel voor één periode. Meerdere artikelen in één aanvraag
 * (straks: winkelwagen) krijgen hetzelfde aanvraagnummer (_krv_request_id).
 */

defined( 'ABSPATH' ) || exit;

/** Meta-velden van een boeking (zonder prefix "_krv_"). */
function krv_booking_fields() {
	return array(
		'status'      => 'pending',
		'product_id'  => 0,
		'start'       => '',
		'end'         => '',
		'days'        => 1,
		'quantity'    => 1,
		'extras'      => array(),
		'lines'       => array(),
		'total'       => 0,
		'deposit'     => 0,
		'name'        => '',
		'email'       => '',
		'phone'       => '',
		'address'     => '',
		'notes'       => '',
		'admin_notes' => '',
		'request_id'  => '',
		'source'      => 'website',
		'paid'        => 0,
	);
}

function krv_get_booking( $id ) {
	$post = get_post( $id );
	if ( ! $post || 'kr_booking' !== $post->post_type ) {
		return null;
	}
	$b = array( 'id' => $post->ID, 'created' => $post->post_date );
	foreach ( krv_booking_fields() as $k => $default ) {
		$v       = get_post_meta( $post->ID, '_krv_' . $k, true );
		$b[ $k ] = ( '' === $v || null === $v ) ? $default : $v;
	}
	$b['product_name'] = $b['product_id'] ? get_the_title( $b['product_id'] ) : '';
	return $b;
}

/** Referentienummer voor klant en beheer, bijv. "KR-00042". */
function krv_booking_ref( $id ) {
	return 'KR-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
}

/**
 * Normaliseer en valideer boekingsgegevens.
 *
 * @param array $in      Ruwe invoer.
 * @param array $opts    context: 'public'|'admin', exclude_id, force (dubbel boeken toestaan, alleen admin).
 * @return array|WP_Error Genormaliseerde gegevens (incl. prijs).
 */
function krv_validate_booking( $in, $opts = array() ) {
	$opts    = wp_parse_args( $opts, array( 'context' => 'public', 'exclude_id' => 0, 'force' => false, 'pending' => array(), 'skip_customer' => false ) );
	$public  = 'public' === $opts['context'];
	$product = krv_get_product( isset( $in['product_id'] ) ? (int) $in['product_id'] : 0 );
	if ( ! $product || ( $public && 'publish' !== get_post_status( $product['id'] ) ) ) {
		return new WP_Error( 'krv_product', 'Kies een geldig huurartikel.' );
	}

	$start = isset( $in['start'] ) ? sanitize_text_field( $in['start'] ) : '';
	$end   = ! empty( $in['end'] ) ? sanitize_text_field( $in['end'] ) : $start;
	if ( ! krv_valid_date( $start ) || ! krv_valid_date( $end ) || $end < $start ) {
		return new WP_Error( 'krv_dates', 'Kies een geldige huurperiode.' );
	}
	$days = krv_days_between( $start, $end );

	if ( $public ) {
		$min = wp_date( 'Y-m-d', strtotime( '+' . (int) krv_setting( 'min_lead_days' ) . ' days' ) );
		$max = wp_date( 'Y-m-d', strtotime( '+' . (int) krv_setting( 'max_days_ahead' ) . ' days' ) );
		if ( $start < $min || $end > $max ) {
			return new WP_Error( 'krv_dates', 'Deze datum kan niet online geboekt worden. Neem contact met ons op.' );
		}
		if ( $days > $product['max_days'] ) {
			return new WP_Error( 'krv_dates', sprintf( 'Maximaal %d dagen per reservering.', $product['max_days'] ) );
		}
	}

	$quantity = isset( $in['quantity'] ) ? max( 1, (int) $in['quantity'] ) : 1;
	if ( $public && ! $product['allow_quantity'] ) {
		$quantity = 1;
	}

	$extras = array_values( array_unique( array_map( 'sanitize_key', (array) ( isset( $in['extras'] ) ? $in['extras'] : array() ) ) ) );
	$extras = array_values( array_intersect( $extras, $public ? $product['extras'] : array_keys( krv_extras() ) ) );

	$customer = array(
		'name'    => sanitize_text_field( isset( $in['name'] ) ? $in['name'] : '' ),
		'email'   => sanitize_email( isset( $in['email'] ) ? $in['email'] : '' ),
		'phone'   => sanitize_text_field( isset( $in['phone'] ) ? $in['phone'] : '' ),
		'address' => sanitize_textarea_field( isset( $in['address'] ) ? $in['address'] : '' ),
		'notes'   => sanitize_textarea_field( isset( $in['notes'] ) ? $in['notes'] : '' ),
	);
	if ( '' === $customer['name'] && ! $opts['skip_customer'] ) {
		return new WP_Error( 'krv_customer', 'Vul je naam in.' );
	}
	if ( $public && ! $opts['skip_customer'] ) {
		if ( ! is_email( $customer['email'] ) ) {
			return new WP_Error( 'krv_customer', 'Vul een geldig e-mailadres in.' );
		}
		if ( '' === $customer['phone'] ) {
			return new WP_Error( 'krv_customer', 'Vul je telefoonnummer in.' );
		}
		$all = krv_extras();
		foreach ( $extras as $x ) {
			if ( ! empty( $all[ $x ]['needs_address'] ) && '' === $customer['address'] ) {
				return new WP_Error( 'krv_customer', 'Vul het afleveradres in.' );
			}
		}
	}

	if ( ! $opts['force'] ) {
		$ok = krv_check_available( $product['id'], $start, $end, $quantity, $opts['exclude_id'], $opts['pending'] );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
	}

	$price = krv_calculate_price( $product, $days, $quantity, $extras );

	return array_merge(
		$customer,
		array(
			'product_id' => $product['id'],
			'start'      => $start,
			'end'        => $end,
			'days'       => $days,
			'quantity'   => $quantity,
			'extras'     => $extras,
			'lines'      => $price['lines'],
			'total'      => $price['total'],
			'deposit'    => $price['deposit'],
		)
	);
}

/** Sla (gevalideerde) gegevens op als meta en werk titel/zoektekst bij. */
function krv_save_booking_data( $id, $data ) {
	$fields = krv_booking_fields();
	foreach ( $data as $k => $v ) {
		if ( array_key_exists( $k, $fields ) ) {
			update_post_meta( $id, '_krv_' . $k, $v );
		}
	}
	$b = krv_get_booking( $id );
	// Titel en inhoud worden gebruikt voor het zoeken in de beheerlijst.
	remove_action( 'save_post_kr_booking', 'krv_admin_save_booking', 10 );
	wp_update_post(
		array(
			'ID'           => $id,
			'post_title'   => krv_booking_ref( $id ) . ' – ' . $b['name'] . ' – ' . $b['product_name'],
			'post_content' => implode( "\n", array( $b['request_id'], $b['email'], $b['phone'], $b['address'], $b['notes'] ) ),
			'post_status'  => 'publish',
		)
	);
	if ( function_exists( 'krv_admin_save_booking' ) ) {
		add_action( 'save_post_kr_booking', 'krv_admin_save_booking', 10, 2 );
	}
}

/**
 * Nieuwe boeking aanmaken.
 *
 * @return int|WP_Error Booking ID.
 */
function krv_create_booking( $data, $status = 'pending', $source = 'website', $request_id = '' ) {
	$id = wp_insert_post(
		array(
			'post_type'   => 'kr_booking',
			'post_status' => 'publish',
			'post_title'  => 'Boeking',
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	$data['status']     = $status;
	$data['source']     = $source;
	$data['request_id'] = $request_id ? $request_id : krv_booking_ref( $id );
	krv_save_booking_data( $id, $data );
	krv_log( $id, 'Boeking aangemaakt via ' . ( 'admin' === $source ? 'beheer' : 'website' ) . ' (status: ' . krv_statuses()[ $status ] . ').' );
	do_action( 'krv_booking_created', $id );
	return $id;
}

/** Alle boekingen (regels) van één bestelling. */
function krv_request_bookings( $request_id ) {
	return get_posts(
		array(
			'post_type'   => 'kr_booking',
			'numberposts' => -1,
			'fields'      => 'ids',
			'orderby'     => 'ID',
			'order'       => 'ASC',
			'meta_key'    => '_krv_request_id',
			'meta_value'  => $request_id,
		)
	);
}

/** Status wijzigen (met logregel en hook voor e-mails). */
function krv_set_status( $id, $status ) {
	if ( ! array_key_exists( $status, krv_statuses() ) ) {
		return;
	}
	$old = get_post_meta( $id, '_krv_status', true );
	if ( $old === $status ) {
		return;
	}
	update_post_meta( $id, '_krv_status', $status );
	krv_log( $id, 'Status gewijzigd: ' . ( isset( krv_statuses()[ $old ] ) ? krv_statuses()[ $old ] : '-' ) . ' → ' . krv_statuses()[ $status ] . '.' );
	do_action( 'krv_booking_status_changed', $id, $status, $old );
}

/** Historie per boeking. */
function krv_log( $id, $message ) {
	$log = get_post_meta( $id, '_krv_log', true );
	$log = is_array( $log ) ? $log : array();
	$u   = wp_get_current_user();
	$log[] = array(
		'time' => current_time( 'mysql' ),
		'user' => $u && $u->exists() ? $u->display_name : 'Website',
		'msg'  => $message,
	);
	update_post_meta( $id, '_krv_log', $log );
}
