<?php
/**
 * Post types: huurartikelen (kr_product), huurgroepen (kr_group) en boekingen (kr_booking).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'krv_register_types' );

function krv_register_types() {
	register_post_type(
		'kr_product',
		array(
			'labels'        => array(
				'name'          => 'Huurartikelen',
				'singular_name' => 'Huurartikel',
				'add_new'       => 'Nieuw artikel',
				'add_new_item'  => 'Nieuw huurartikel',
				'edit_item'     => 'Huurartikel bewerken',
				'all_items'     => 'Alle huurartikelen',
				'search_items'  => 'Huurartikelen zoeken',
				'menu_name'     => 'Huurartikelen',
			),
			'public'        => true,
			'has_archive'   => 'huren',
			'rewrite'       => array( 'slug' => 'huren', 'with_front' => false ),
			'menu_icon'     => 'dashicons-products',
			'menu_position' => 25,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);

	register_taxonomy(
		'kr_group',
		'kr_product',
		array(
			'labels'            => array(
				'name'          => 'Huurgroepen',
				'singular_name' => 'Huurgroep',
				'add_new_item'  => 'Nieuwe huurgroep',
				'edit_item'     => 'Huurgroep bewerken',
				'menu_name'     => 'Huurgroepen',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'huurgroep', 'with_front' => false ),
		)
	);

	register_post_type(
		'kr_booking',
		array(
			'labels'          => array(
				'name'          => 'Boekingen',
				'singular_name' => 'Boeking',
				'add_new'       => 'Nieuwe boeking',
				'add_new_item'  => 'Nieuwe boeking',
				'edit_item'     => 'Boeking bewerken',
				'all_items'     => 'Alle boekingen',
				'search_items'  => 'Boekingen zoeken',
				'not_found'     => 'Geen boekingen gevonden',
				'menu_name'     => 'Boekingen',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-calendar-alt',
			'menu_position'   => 24,
			'supports'        => array( '' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}

/* ---------------------------------------------------------------------------
 * Productgegevens
 * ------------------------------------------------------------------------- */

/** Standaardwaarden voor productvelden (meta keys zonder prefix "_krv_"). */
function krv_product_fields() {
	return array(
		'price_day'       => 0,
		'price_extra_day' => '',
		'deposit'         => 0,
		'max_days'        => 14,
		'stock'           => 1,
		'allow_quantity'  => 0,
		'features'        => '',
		'extras'          => array(),
	);
}

/** Alle gegevens van een huurartikel als array. */
function krv_get_product( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'kr_product' !== $post->post_type ) {
		return null;
	}
	$data = array();
	foreach ( krv_product_fields() as $key => $default ) {
		$val          = get_post_meta( $post->ID, '_krv_' . $key, true );
		$data[ $key ] = ( '' === $val || null === $val ) ? $default : $val;
	}
	$data['id']              = $post->ID;
	$data['name']            = get_the_title( $post );
	$data['price_day']       = (float) $data['price_day'];
	$data['price_extra_day'] = '' === $data['price_extra_day'] ? $data['price_day'] : (float) $data['price_extra_day'];
	$data['deposit']         = (float) $data['deposit'];
	$data['max_days']        = max( 1, (int) $data['max_days'] );
	$data['stock']           = max( 1, (int) $data['stock'] );
	$data['allow_quantity']  = (bool) $data['allow_quantity'];
	$data['extras']          = array_values( array_intersect( (array) $data['extras'], array_keys( krv_extras() ) ) );
	$data['features']        = array_values( array_filter( array_map( 'trim', explode( "\n", (string) $data['features'] ) ) ) );
	$terms                   = get_the_terms( $post, 'kr_group' );
	$data['group']           = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	return $data;
}

/* ---------------------------------------------------------------------------
 * Huurgroep-gegevens (term meta)
 * ------------------------------------------------------------------------- */

function krv_group_meta( $term ) {
	$term = get_term( $term, 'kr_group' );
	if ( ! $term || is_wp_error( $term ) ) {
		return null;
	}
	return array(
		'icon'         => get_term_meta( $term->term_id, '_krv_icon', true ) ?: 'box',
		'tagline'      => get_term_meta( $term->term_id, '_krv_tagline', true ) ?: $term->description,
		'external_url' => get_term_meta( $term->term_id, '_krv_external_url', true ),
		'order'        => (int) get_term_meta( $term->term_id, '_krv_order', true ),
	);
}

/** Huurgroepen, gesorteerd op volgorde. */
function krv_get_groups() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'kr_group',
			'hide_empty' => false,
			'parent'     => 0,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort(
		$terms,
		function ( $a, $b ) {
			return krv_group_meta( $a )['order'] <=> krv_group_meta( $b )['order'];
		}
	);
	return $terms;
}

/** URL van een huurgroep (externe link zoals Goboony gaat voor). */
function krv_group_url( $term ) {
	$meta = krv_group_meta( $term );
	if ( $meta && $meta['external_url'] ) {
		return $meta['external_url'];
	}
	return get_term_link( $term );
}

/** Laagste dagprijs binnen een huurgroep. */
function krv_group_from_price( $term ) {
	$ids = get_posts(
		array(
			'post_type'   => 'kr_product',
			'numberposts' => -1,
			'fields'      => 'ids',
			'tax_query'   => array(
				array(
					'taxonomy' => 'kr_group',
					'terms'    => $term->term_id,
				),
			),
		)
	);
	$prices = array();
	foreach ( $ids as $id ) {
		$p = (float) get_post_meta( $id, '_krv_price_day', true );
		if ( $p > 0 ) {
			$prices[] = $p;
		}
	}
	return $prices ? min( $prices ) : null;
}
