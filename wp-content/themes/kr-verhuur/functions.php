<?php
/**
 * KR Verhuur theme.
 *
 * De functionaliteit (huurartikelen, boekingen, beheer) zit in de plugin
 * "KR Verhuur – Boekingen". Dit theme zorgt voor de weergave.
 */

defined( 'ABSPATH' ) || exit;

define( 'KRT_VERSION', '1.2.0' );

/**
 * Versie van een themabestand op basis van de wijzigingsdatum. Na elke update krijgt
 * het bestand zo een nieuwe URL en laden browsers en caches nooit een oude versie.
 */
function krt_asset_ver( $rel ) {
	$file = get_template_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $file ) ? KRT_VERSION . '.' . filemtime( $file ) : KRT_VERSION;
}

require_once get_template_directory() . '/inc/template-tags.php';

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 540, 'flex-height' => true, 'flex-width' => true ) );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		register_nav_menus(
			array(
				'primary' => 'Hoofdmenu',
				'footer'  => 'Footermenu',
			)
		);
	}
);

/** Is de boekingen-plugin actief? */
function krt_has_plugin() {
	return function_exists( 'krv_get_product' );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		$uri = get_template_directory_uri();
		wp_enqueue_style( 'krt-fonts', 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap', array(), null );
		wp_enqueue_style( 'krt-main', $uri . '/assets/css/main.css', array(), krt_asset_ver( '/assets/css/main.css' ) );
		wp_enqueue_script( 'krt-nav', $uri . '/assets/js/nav.js', array(), krt_asset_ver( '/assets/js/nav.js' ), true );

		if ( ! krt_has_plugin() ) {
			return;
		}
		wp_enqueue_script( 'krt-cart', $uri . '/assets/js/cart.js', array(), krt_asset_ver( '/assets/js/cart.js' ), true );
		wp_localize_script(
			'krt-cart',
			'KRV_VAT',
			array(
				'rate'    => krv_vat_rate(),
				'mode'    => krv_vat_mode(),
				'default' => krv_vat_default_mode(),
				'toggle'  => krv_vat_toggle_enabled() ? 1 : 0,
				'cookie'  => KRV_VAT_COOKIE,
			)
		);

		if ( krv_is_cart() ) {
			wp_enqueue_script( 'krt-calendar', $uri . '/assets/js/calendar.js', array(), krt_asset_ver( '/assets/js/calendar.js' ), true );
			wp_enqueue_script( 'krt-cart-page', $uri . '/assets/js/cart-page.js', array( 'krt-cart', 'krt-calendar' ), krt_asset_ver( '/assets/js/cart-page.js' ), true );
			wp_localize_script(
				'krt-cart-page',
				'KRV_CART',
				array(
					'restUrl'    => esc_url_raw( rest_url( 'kr/v1/' ) ),
					'nonce'      => wp_create_nonce( 'wp_rest' ),
					'catalogUrl' => krt_catalog_url(),
					'homeUrl'    => home_url( '/' ),
					'vatToggle'  => krt_vat_toggle( 'vat-toggle-cart' ),
					'today'      => krv_today(),
					'minLead'    => (int) krv_setting( 'min_lead_days' ),
					'maxAhead'   => (int) krv_setting( 'max_days_ahead' ),
					'icons'      => krt_icons_for_js(),
				)
			);
		}

		if ( is_singular( 'kr_product' ) ) {
			$p = krv_get_product( get_queried_object_id() );
			wp_enqueue_script( 'krt-calendar', $uri . '/assets/js/calendar.js', array(), krt_asset_ver( '/assets/js/calendar.js' ), true );
			wp_enqueue_script( 'krt-booking', $uri . '/assets/js/booking.js', array( 'krt-calendar', 'krt-cart' ), krt_asset_ver( '/assets/js/booking.js' ), true );

			// Prijzen gaan altijd incl. btw naar de JS; die rekent zo nodig om voor weergave.
			$all_extras = krv_extras_incl();
			$extras     = array();
			foreach ( $p['extras'] as $key ) {
				$extras[ $key ] = $all_extras[ $key ];
			}
			wp_localize_script(
				'krt-booking',
				'KRV_BOOKING',
				array(
					'restUrl'   => esc_url_raw( rest_url( 'kr/v1/' ) ),
					'nonce'     => wp_create_nonce( 'wp_rest' ),
					'minLead'   => (int) krv_setting( 'min_lead_days' ),
					'maxAhead'  => (int) krv_setting( 'max_days_ahead' ),
					'today'     => krv_today(),
					'icons'     => krt_icons_for_js(),
					'cartUrl'    => krv_cart_url(),
					'catalogUrl' => krt_catalog_url(),
					'productUrl' => get_permalink( $p['id'] ),
					'product'   => array(
						'id'             => $p['id'],
						'name'           => $p['name'],
						'priceDay'       => krv_entered_to_incl( $p['price_day'] ),
						'priceExtraDay'  => krv_entered_to_incl( $p['price_extra_day'] ),
						'deposit'        => $p['deposit'],
						'maxDays'        => $p['max_days'],
						'stock'          => $p['stock'],
						'allowQuantity'  => $p['allow_quantity'],
						'extras'         => $extras,
					),
				)
			);
		}
	}
);

add_action(
	'wp_head',
	function () {
		if ( ! has_site_icon() ) {
			echo '<link rel="icon" href="' . esc_url( get_template_directory_uri() . '/assets/img/favicon.svg' ) . '" type="image/svg+xml">' . "\n";
		}
		echo '<meta name="theme-color" content="#002533">' . "\n";
	}
);

/* Huurartikelen sorteren op volgorde en alles op één pagina tonen. */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( $q->is_post_type_archive( 'kr_product' ) || $q->is_tax( 'kr_group' ) ) {
			$q->set( 'posts_per_page', 60 );
			$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		}
	}
);

/* Melding als de plugin niet actief is. */
add_action(
	'admin_notices',
	function () {
		if ( ! krt_has_plugin() && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-warning"><p><strong>KR Verhuur theme:</strong> activeer de plugin <em>KR Verhuur – Boekingen</em> voor huurartikelen en de reserveringskalender.</p></div>';
		}
	}
);

/** Standaard menu als er (nog) geen menu is ingesteld. */
function krt_default_menu() {
	$items = array(
		home_url( '/' )                => 'Home',
		krt_catalog_url()             => 'Assortiment',
		home_url( '/#hoe-werkt-het' )  => 'Hoe werkt het',
		home_url( '/#contact' )        => 'Contact',
	);
	echo '<ul class="menu">';
	foreach ( $items as $url => $label ) {
		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

/** Link naar het assortiment. */
function krt_catalog_url() {
	$url = krt_has_plugin() ? get_post_type_archive_link( 'kr_product' ) : '';
	return $url ? $url : home_url( '/huren/' );
}

/* Winkelwagenpagina (/winkelwagen/, geregistreerd door de plugin). */
add_filter(
	'template_include',
	function ( $template ) {
		if ( krt_has_plugin() && krv_is_cart() ) {
			status_header( 200 );
			return get_template_directory() . '/cart.php';
		}
		return $template;
	}
);

add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( krt_has_plugin() && krv_is_cart() ) {
			$parts['title'] = 'Winkelwagen';
		}
		return $parts;
	}
);

add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( krt_has_plugin() && krv_is_cart() ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}
);

/** Link naar de winkelwagen. */
function krt_cart_url() {
	return krt_has_plugin() ? krv_cart_url() : home_url( '/winkelwagen/' );
}

/* Body-class met de huidige btw-weergave (voor "alleen bij excl. btw"-teksten). */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( krt_has_plugin() ) {
			$classes[] = krv_show_excl_vat() ? 'vat-excl' : 'vat-incl';
		}
		return $classes;
	}
);
