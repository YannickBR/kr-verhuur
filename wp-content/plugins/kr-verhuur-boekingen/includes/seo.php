<?php
/**
 * Zoekmachine-optimalisatie (SEO).
 *
 * - Titels en meta-omschrijvingen per paginatype (automatisch, per artikel/huurgroep aan te passen).
 * - Open Graph / social previews (Facebook, WhatsApp, LinkedIn, X).
 * - Structured data (JSON-LD): LocalBusiness met werkgebied, Product + prijs, BreadcrumbList,
 *   ItemList op huurgroepen, FAQPage op werkgebiedpagina's.
 * - Oude/overbodige pagina's niet indexeren en doorsturen (301).
 * - Werkgebiedpagina's in de sitemap.
 *
 * Werkt samen met Yoast SEO, Rank Math, All in One SEO, SEOPress en The SEO Framework:
 * is zo'n plugin actief, dan geven we hun onze standaardteksten door in plaats van dubbele
 * tags te printen, en corrigeren we canonical/title voor de pagina's die zij niet kennen
 * (werkgebied, winkelwagen).
 */

defined( 'ABSPATH' ) || exit;

/* ===========================================================================
 * Hulpfuncties
 * ========================================================================= */

/** Is er een SEO-plugin actief die zelf title/meta/OG print? */
function krv_seo_plugin() {
	if ( defined( 'WPSEO_VERSION' ) ) {
		return 'yoast';
	}
	if ( class_exists( 'RankMath' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return 'rankmath';
	}
	if ( defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' ) ) {
		return 'aioseo';
	}
	if ( defined( 'SEOPRESS_VERSION' ) ) {
		return 'seopress';
	}
	if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
		return 'tsf';
	}
	return '';
}

/** "Springkussen" → "Springkussen huren"; "Camperbus huren" blijft zo. */
function krv_with_huren( $name ) {
	$name = trim( (string) $name );
	return preg_match( '/\bhuren\b/i', $name ) ? $name : $name . ' huren';
}

/** Naam zonder "huren" erachter (voor zinnen als "Wij bezorgen de springkussen ..."). */
function krv_without_huren( $name ) {
	return trim( preg_replace( '/\s+huren$/i', '', (string) $name ) );
}

function krv_company() {
	return krv_setting( 'company_name' ) ?: 'KRverhuur';
}

/** Lijst als "A, B en C". */
function krv_list_text( $items ) {
	$items = array_values( array_filter( $items ) );
	if ( count( $items ) < 2 ) {
		return implode( '', $items );
	}
	$last = array_pop( $items );
	return implode( ', ', $items ) . ' en ' . $last;
}

/** Belangrijkste plaatsen voor in titels/teksten. */
function krv_seo_top_places( $n = 3 ) {
	$names = array();
	foreach ( krv_locations() as $l ) {
		$names[] = $l['name'];
	}
	// Grootste plaatsen eerst noemen (Emmeloord, Urk), daarna op afstand.
	$priority = array( 'Emmeloord', 'Urk', 'Marknesse' );
	$names    = array_values( array_unique( array_merge( array_intersect( $priority, $names ), $names ) ) );
	return array_slice( $names, 0, $n );
}

/** Ingekorte tekst voor meta-omschrijvingen (max. ~155 tekens, op woordgrens). */
function krv_seo_trim( $text, $max = 155 ) {
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text ) ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max - 1 );
	$cut = preg_replace( '/\s+\S*$/u', '', $cut );
	return rtrim( $cut, ' ,.;:–-' ) . '…';
}

/* ===========================================================================
 * Context: wat voor pagina is dit?
 * ========================================================================= */

function krv_seo_context() {
	static $ctx = null;
	if ( null !== $ctx ) {
		return $ctx;
	}
	$ctx = array( 'type' => '' );
	if ( is_admin() ) {
		return $ctx;
	}
	$loc = function_exists( 'krv_current_location_slug' ) ? krv_current_location_slug() : '';
	if ( 'overzicht' === $loc ) {
		$ctx = array( 'type' => 'area', 'url' => krv_service_area_url() );
	} elseif ( $loc ) {
		$ctx = array( 'type' => 'location', 'loc' => krv_location( $loc ), 'url' => krv_location_url( $loc ) );
	} elseif ( function_exists( 'krv_is_cart' ) && krv_is_cart() ) {
		$ctx = array( 'type' => 'cart', 'url' => krv_cart_url() );
	} elseif ( is_singular( 'kr_product' ) ) {
		$ctx = array( 'type' => 'product', 'id' => get_queried_object_id(), 'url' => get_permalink( get_queried_object_id() ) );
	} elseif ( is_tax( 'kr_group' ) ) {
		$term = get_queried_object();
		$ctx  = array( 'type' => 'group', 'term' => $term, 'url' => get_term_link( $term ) );
	} elseif ( is_post_type_archive( 'kr_product' ) ) {
		$ctx = array( 'type' => 'catalog', 'url' => get_post_type_archive_link( 'kr_product' ) );
	} elseif ( is_front_page() ) {
		$ctx = array( 'type' => 'home', 'url' => home_url( '/' ) );
	}
	return $ctx;
}

/** Eigen SEO-velden (artikel of huurgroep). */
function krv_seo_custom( $ctx, $field ) {
	if ( 'product' === $ctx['type'] ) {
		return trim( (string) get_post_meta( $ctx['id'], '_krv_seo_' . $field, true ) );
	}
	if ( 'group' === $ctx['type'] ) {
		return trim( (string) get_term_meta( $ctx['term']->term_id, '_krv_seo_' . $field, true ) );
	}
	return '';
}

/* ===========================================================================
 * Titels en omschrijvingen
 * ========================================================================= */

function krv_seo_title( $ctx = null ) {
	$ctx     = $ctx ?: krv_seo_context();
	$company = krv_company();
	$custom  = $ctx['type'] ? krv_seo_custom( $ctx, 'title' ) : '';
	if ( $custom ) {
		return $custom;
	}
	$places = implode( ', ', krv_seo_top_places( 2 ) );
	switch ( $ctx['type'] ) {
		case 'home':
			return $company . ' – Verhuur in Marknesse, Emmeloord & de Noordoostpolder';
		case 'product':
			$p = krv_get_product( $ctx['id'] );
			if ( $p['is_sale'] ) {
				return $p['name'] . ' kopen in de Noordoostpolder | ' . $company;
			}
			return krv_with_huren( $p['name'] ) . ' in ' . $places . ' & omgeving | ' . $company;
		case 'group':
			return krv_with_huren( $ctx['term']->name ) . ' in ' . $places . ' & Noordoostpolder | ' . $company;
		case 'catalog':
			return 'Huren voor feest, evenement & klus – Noordoostpolder | ' . $company;
		case 'location':
			return 'Verhuur ' . $ctx['loc']['name'] . ' – photobooth, springkussen & toiletwagen huren | ' . $company;
		case 'area':
			return 'Werkgebied – verhuur Noordoostpolder, Flevoland & Overijssel | ' . $company;
		case 'cart':
			return 'Winkelwagen | ' . $company;
	}
	return '';
}

function krv_seo_description( $ctx = null ) {
	$ctx    = $ctx ?: krv_seo_context();
	$custom = $ctx['type'] ? krv_seo_custom( $ctx, 'desc' ) : '';
	if ( $custom ) {
		return krv_seo_trim( $custom, 160 );
	}
	$company = krv_company();
	$places  = krv_list_text( krv_seo_top_places( 3 ) );
	switch ( $ctx['type'] ) {
		case 'home':
			return krv_seo_trim( $company . ' in Marknesse: huur een photobooth, springkussen, bumperbaan, toiletwagen, meubilair, verwarming of gereedschap. Online reserveren, bezorging in ' . $places . ' en de hele Noordoostpolder.' );
		case 'product':
			$p     = krv_get_product( $ctx['id'] );
			$post  = get_post( $ctx['id'] );
			$intro = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 18, '' );
			if ( $p['external_url'] ) {
				return krv_seo_trim( krv_with_huren( $p['name'] ) . ' bij ' . $company . ' in Marknesse. ' . $intro );
			}
			$price = krv_euro( krv_entered_to_incl( $p['price_day'] ) ) . ( $p['is_sale'] ? ' per ' . $p['unit'] : ' per dag' );
			$verb  = $p['is_sale'] ? 'Bestel' : 'Huur';
			return krv_seo_trim( $verb . ' de ' . mb_strtolower( krv_without_huren( $p['name'] ) ) . ' vanaf ' . $price . '. ' . $intro . ' Bezorging in ' . $places . ' en omgeving.' );
		case 'group':
			$meta = krv_group_meta( $ctx['term'] );
			$from = krv_group_from_price( $ctx['term'] );
			return krv_seo_trim( krv_with_huren( $ctx['term']->name ) . ( $from ? ' vanaf ' . krv_euro( krv_entered_to_incl( $from ) ) . ' per dag' : '' ) . ' bij ' . $company . '. ' . $meta['tagline'] . ' Online reserveren, bezorging in ' . $places . ' en de Noordoostpolder.' );
		case 'catalog':
			return krv_seo_trim( 'Bekijk het complete huuraanbod van ' . $company . ': photobooth, springkussen, bumperbaan, toiletwagen, meubilair, verwarming, koeling en gereedschap. Online reserveren in de Noordoostpolder.' );
		case 'location':
			$l = $ctx['loc'];
			return krv_seo_trim( 'Verhuur in ' . $l['name'] . ': photobooth, springkussen, toiletwagen, statafels, verwarming en meer huren bij ' . $company . '. Wij bezorgen in ' . $l['name'] . ( $l['gemeente'] !== $l['name'] ? ' (gemeente ' . $l['gemeente'] . ')' : '' ) . '. Direct online reserveren.' );
		case 'area':
			return krv_seo_trim( $company . ' uit Marknesse bezorgt in ' . implode( ', ', krv_seo_top_places( 8 ) ) . ' en de rest van de Noordoostpolder, Flevoland en Overijssel.' );
	}
	return '';
}

/** Afbeelding voor social previews. */
function krv_seo_image( $ctx = null ) {
	$ctx = $ctx ?: krv_seo_context();
	if ( 'product' === $ctx['type'] ) {
		$imgs = krv_product_images( $ctx['id'] );
		if ( $imgs ) {
			$src = wp_get_attachment_image_src( $imgs[0], 'large' );
			if ( $src ) {
				return array( 'url' => $src[0], 'width' => $src[1], 'height' => $src[2] );
			}
		}
	}
	if ( 'group' === $ctx['type'] ) {
		$ids = get_posts( array( 'post_type' => 'kr_product', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_thumbnail_id', 'tax_query' => array( array( 'taxonomy' => 'kr_group', 'terms' => $ctx['term']->term_id ) ) ) );
		if ( $ids ) {
			$src = wp_get_attachment_image_src( get_post_thumbnail_id( $ids[0] ), 'large' );
			if ( $src ) {
				return array( 'url' => $src[0], 'width' => $src[1], 'height' => $src[2] );
			}
		}
	}
	return array( 'url' => KRV_URL . 'assets/email-logo-dark.png', 'width' => 900, 'height' => 200 );
}

/* ===========================================================================
 * Uitvoer zonder SEO-plugin
 * ========================================================================= */

add_filter(
	'pre_get_document_title',
	function ( $title ) {
		if ( krv_seo_plugin() ) {
			return $title;
		}
		$t = krv_seo_title();
		return $t ? $t : $title;
	},
	20
);

add_action(
	'wp_head',
	function () {
		$ctx = krv_seo_context();
		if ( krv_seo_plugin() || ! $ctx['type'] ) {
			return;
		}
		$title = krv_seo_title( $ctx );
		$desc  = krv_seo_description( $ctx );
		$img   = krv_seo_image( $ctx );
		$url   = $ctx['url'];
		$tags  = array();
		if ( $desc ) {
			$tags[] = '<meta name="description" content="' . esc_attr( $desc ) . '">';
		}
		// WordPress print zelf een canonical bij losse berichten/pagina's; voor de rest doen wij het.
		if ( ! is_singular() && $url && ! is_wp_error( $url ) && 'cart' !== $ctx['type'] ) {
			$tags[] = '<link rel="canonical" href="' . esc_url( $url ) . '">';
		}
		$tags[] = '<meta property="og:locale" content="nl_NL">';
		$tags[] = '<meta property="og:type" content="' . ( 'product' === $ctx['type'] ? 'product' : 'website' ) . '">';
		$tags[] = '<meta property="og:site_name" content="' . esc_attr( krv_company() ) . '">';
		$tags[] = '<meta property="og:title" content="' . esc_attr( $title ) . '">';
		if ( $desc ) {
			$tags[] = '<meta property="og:description" content="' . esc_attr( $desc ) . '">';
		}
		if ( $url && ! is_wp_error( $url ) ) {
			$tags[] = '<meta property="og:url" content="' . esc_url( $url ) . '">';
		}
		$tags[] = '<meta property="og:image" content="' . esc_url( $img['url'] ) . '">';
		$tags[] = '<meta property="og:image:width" content="' . (int) $img['width'] . '">';
		$tags[] = '<meta property="og:image:height" content="' . (int) $img['height'] . '">';
		$tags[] = '<meta name="twitter:card" content="summary_large_image">';
		$tags[] = '<meta name="twitter:title" content="' . esc_attr( $title ) . '">';
		if ( $desc ) {
			$tags[] = '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">';
		}
		$tags[] = '<meta name="twitter:image" content="' . esc_url( $img['url'] ) . '">';
		echo "\n<!-- KRverhuur SEO -->\n" . implode( "\n", $tags ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	},
	2
);

/* ===========================================================================
 * Samenwerken met SEO-plugins
 * ========================================================================= */

/** Heeft de SEO-plugin voor dit artikel/deze groep een eigen waarde ingevuld? */
function krv_seo_plugin_has_own( $ctx, $field ) {
	$keys = array(
		'yoast'    => array( 'title' => '_yoast_wpseo_title', 'desc' => '_yoast_wpseo_metadesc' ),
		'rankmath' => array( 'title' => 'rank_math_title', 'desc' => 'rank_math_description' ),
		'aioseo'   => array(),
		'seopress' => array( 'title' => '_seopress_titles_title', 'desc' => '_seopress_titles_desc' ),
	);
	$plugin = krv_seo_plugin();
	if ( 'product' === $ctx['type'] && ! empty( $keys[ $plugin ][ $field ] ) ) {
		return '' !== trim( (string) get_post_meta( $ctx['id'], $keys[ $plugin ][ $field ], true ) );
	}
	return false;
}

/** Waarde die we de SEO-plugin geven, of null om die van de plugin te houden. */
function krv_seo_for_plugin( $field, $current ) {
	$ctx = krv_seo_context();
	if ( ! $ctx['type'] ) {
		return null;
	}
	// Pagina's die de SEO-plugin niet kent (werkgebied, winkelwagen) altijd van ons.
	$virtual = in_array( $ctx['type'], array( 'location', 'area', 'cart' ), true );
	if ( ! $virtual ) {
		if ( krv_seo_custom( $ctx, $field ) ) {
			// Eigen veld in onze plugin gaat voor.
		} elseif ( krv_seo_plugin_has_own( $ctx, $field ) || 'home' === $ctx['type'] ) {
			return null;
		}
	}
	$value = 'title' === $field ? krv_seo_title( $ctx ) : krv_seo_description( $ctx );
	return $value ? $value : null;
}

/** Canonical voor pagina's die de SEO-plugin niet kent (anders wijzen ze naar de homepage). */
function krv_seo_virtual_canonical() {
	$ctx = krv_seo_context();
	return in_array( $ctx['type'], array( 'location', 'area', 'cart' ), true ) ? $ctx['url'] : null;
}

// Yoast SEO.
foreach ( array( 'wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title' ) as $hook ) {
	add_filter( $hook, function ( $v ) { $n = krv_seo_for_plugin( 'title', $v ); return null === $n ? $v : $n; }, 20 );
}
foreach ( array( 'wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description' ) as $hook ) {
	add_filter( $hook, function ( $v ) { $n = krv_seo_for_plugin( 'desc', $v ); return null === $n ? $v : $n; }, 20 );
}
foreach ( array( 'wpseo_canonical', 'wpseo_opengraph_url' ) as $hook ) {
	add_filter( $hook, function ( $v ) { $c = krv_seo_virtual_canonical(); return $c ? $c : $v; }, 20 );
}
// Rank Math.
add_filter( 'rank_math/frontend/title', function ( $v ) { $n = krv_seo_for_plugin( 'title', $v ); return null === $n ? $v : $n; }, 20 );
add_filter( 'rank_math/frontend/description', function ( $v ) { $n = krv_seo_for_plugin( 'desc', $v ); return null === $n ? $v : $n; }, 20 );
add_filter( 'rank_math/frontend/canonical', function ( $v ) { $c = krv_seo_virtual_canonical(); return $c ? $c : $v; }, 20 );
add_filter( 'rank_math/opengraph/url', function ( $v ) { $c = krv_seo_virtual_canonical(); return $c ? $c : $v; }, 20 );
// All in One SEO.
add_filter( 'aioseo_title', function ( $v ) { $n = krv_seo_for_plugin( 'title', $v ); return null === $n ? $v : $n; }, 20 );
add_filter( 'aioseo_description', function ( $v ) { $n = krv_seo_for_plugin( 'desc', $v ); return null === $n ? $v : $n; }, 20 );
add_filter( 'aioseo_canonical_url', function ( $v ) { $c = krv_seo_virtual_canonical(); return $c ? $c : $v; }, 20 );
// SEOPress.
add_filter( 'seopress_titles_title', function ( $v ) { $n = krv_seo_for_plugin( 'title', $v ); return null === $n ? $v : $n; }, 20 );
add_filter( 'seopress_titles_desc', function ( $v ) { $n = krv_seo_for_plugin( 'desc', $v ); return null === $n ? $v : $n; }, 20 );
add_filter( 'seopress_titles_canonical', function ( $v ) { $c = krv_seo_virtual_canonical(); return $c ? '<link rel="canonical" href="' . esc_url( $c ) . '" />' : $v; }, 20 );
// The SEO Framework.
add_filter( 'the_seo_framework_title_from_generation', function ( $v ) { $n = krv_seo_for_plugin( 'title', $v ); return null === $n ? $v : $n; }, 20 );
add_filter( 'the_seo_framework_generated_description', function ( $v ) { $n = krv_seo_for_plugin( 'desc', $v ); return null === $n ? $v : $n; }, 20 );
// Thema-/WordPress-titel bij een SEO-plugin die de document title niet zelf vervangt.
add_filter(
	'pre_get_document_title',
	function ( $title ) {
		if ( ! krv_seo_plugin() ) {
			return $title;
		}
		$n = krv_seo_for_plugin( 'title', $title );
		return null === $n ? $title : $n;
	},
	5
);

/* ===========================================================================
 * Structured data (JSON-LD)
 * ========================================================================= */

/** Bedrijfsgegevens als LocalBusiness. */
function krv_schema_business() {
	$city_line = trim( (string) krv_setting( 'address_city' ) );
	$postcode  = '';
	$locality  = $city_line;
	if ( preg_match( '/^(\d{4}\s?[A-Za-z]{2})\s+(.+)$/', $city_line, $m ) ) {
		$postcode = strtoupper( preg_replace( '/\s+/', ' ', $m[1] ) );
		$locality = trim( $m[2] );
	}
	$base      = krv_base_location();
	$area      = array();
	foreach ( krv_locations() as $l ) {
		$area[] = array( '@type' => 'City', 'name' => $l['name'] );
	}
	foreach ( array( 'Noordoostpolder', 'Flevoland', 'Overijssel' ) as $r ) {
		$area[] = array( '@type' => 'AdministrativeArea', 'name' => $r );
	}
	$logo     = function_exists( 'krv_email_logo_url' ) ? krv_email_logo_url() : '';
	$business = array(
		'@type'      => 'LocalBusiness',
		'@id'        => home_url( '/#localbusiness' ),
		'name'       => krv_company(),
		'description' => 'Verhuur van feest-, evenementen- en klusmaterialen in de Noordoostpolder: photobooth, springkussen, bumperbaan, toiletwagen, meubilair, verwarming, koeling en gereedschap.',
		'url'        => home_url( '/' ),
		'logo'       => $logo,
		'image'      => $logo,
		'telephone'  => krv_setting( 'phone' ),
		'email'      => krv_setting( 'email' ),
		'priceRange' => '€€',
		'address'    => array_filter(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => krv_setting( 'address_street' ),
				'postalCode'      => $postcode,
				'addressLocality' => $locality,
				'addressRegion'   => 'Flevoland',
				'addressCountry'  => 'NL',
			)
		),
		'geo'        => array( '@type' => 'GeoCoordinates', 'latitude' => $base['lat'], 'longitude' => $base['lng'] ),
		'areaServed' => $area,
	);
	$same = array_filter( array_map( 'trim', explode( "\n", (string) krv_setting( 'seo_same_as' ) ) ) );
	if ( $same ) {
		$business['sameAs'] = array_values( $same );
	}
	return $business;
}

function krv_schema_breadcrumbs( $items ) {
	$list = array();
	foreach ( array_values( $items ) as $i => $it ) {
		$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $it[0], 'item' => $it[1] );
	}
	return array( '@type' => 'BreadcrumbList', 'itemListElement' => $list );
}

function krv_schema_graph() {
	$ctx   = krv_seo_context();
	$graph = array();
	$home  = array( 'Home', home_url( '/' ) );
	$cat   = array( 'Assortiment', get_post_type_archive_link( 'kr_product' ) );

	switch ( $ctx['type'] ) {
		case 'home':
			$graph[] = krv_schema_business();
			$graph[] = array(
				'@type'     => 'WebSite',
				'@id'       => home_url( '/#website' ),
				'url'       => home_url( '/' ),
				'name'      => krv_company(),
				'publisher' => array( '@id' => home_url( '/#localbusiness' ) ),
				'inLanguage' => 'nl-NL',
			);
			break;

		case 'product':
			$p      = krv_get_product( $ctx['id'] );
			$post   = get_post( $ctx['id'] );
			$images = array();
			foreach ( krv_product_images( $ctx['id'] ) as $img ) {
				$u = wp_get_attachment_image_url( $img, 'large' );
				if ( $u ) {
					$images[] = $u;
				}
			}
			$product = array(
				'@type'       => 'Product',
				'@id'         => $ctx['url'] . '#product',
				'name'        => $p['is_sale'] ? $p['name'] : krv_with_huren( $p['name'] ),
				'description' => krv_seo_trim( has_excerpt( $post ) ? $post->post_excerpt : $post->post_content, 300 ),
				'url'         => $ctx['url'],
				'brand'       => array( '@type' => 'Brand', 'name' => krv_company() ),
			);
			if ( $images ) {
				$product['image'] = $images;
			}
			if ( $p['group'] ) {
				$product['category'] = $p['group']->name;
			}
			if ( ! $p['external_url'] && $p['price_day'] > 0 ) {
				$price            = round( krv_entered_to_incl( $p['price_day'] ), 2 );
				$product['offers'] = array(
					'@type'              => 'Offer',
					'url'                => $ctx['url'],
					'price'              => number_format( $price, 2, '.', '' ),
					'priceCurrency'      => 'EUR',
					'availability'       => 'https://schema.org/InStock',
					'itemCondition'      => 'https://schema.org/UsedCondition',
					'businessFunction'   => $p['is_sale'] ? 'http://purl.org/goodrelations/v1#Sell' : 'http://purl.org/goodrelations/v1#LeaseOut',
					'priceSpecification' => array(
						'@type'                 => 'UnitPriceSpecification',
						'price'                 => number_format( $price, 2, '.', '' ),
						'priceCurrency'         => 'EUR',
						'valueAddedTaxIncluded' => true,
						'unitText'              => $p['is_sale'] ? $p['unit'] : 'dag',
					),
					'areaServed'         => wp_list_pluck( array_values( krv_locations() ), 'name' ),
					'seller'             => array( '@id' => home_url( '/#localbusiness' ) ),
				);
			}
			$graph[] = $product;
			$crumbs  = array( $home, $cat );
			if ( $p['group'] ) {
				$crumbs[] = array( $p['group']->name, get_term_link( $p['group'] ) );
			}
			$crumbs[] = array( $p['name'], $ctx['url'] );
			$graph[]  = krv_schema_breadcrumbs( $crumbs );
			$graph[]  = krv_schema_business();
			break;

		case 'group':
		case 'catalog':
			$args = array( 'post_type' => 'kr_product', 'numberposts' => 50, 'orderby' => 'menu_order title', 'order' => 'ASC' );
			if ( 'group' === $ctx['type'] ) {
				$args['tax_query'] = array( array( 'taxonomy' => 'kr_group', 'terms' => $ctx['term']->term_id ) );
			}
			$items = array();
			foreach ( get_posts( $args ) as $i => $prod ) {
				$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink( $prod ), 'name' => get_the_title( $prod ) );
			}
			$graph[] = array(
				'@type'    => 'CollectionPage',
				'@id'      => $ctx['url'] . '#webpage',
				'url'      => $ctx['url'],
				'name'     => krv_seo_title( $ctx ),
				'about'    => array( '@id' => home_url( '/#localbusiness' ) ),
				'mainEntity' => array( '@type' => 'ItemList', 'itemListElement' => $items ),
			);
			$crumbs = array( $home, $cat );
			if ( 'group' === $ctx['type'] ) {
				$crumbs[] = array( $ctx['term']->name, $ctx['url'] );
			}
			$graph[] = krv_schema_breadcrumbs( $crumbs );
			$graph[] = krv_schema_business();
			break;

		case 'location':
			$l        = $ctx['loc'];
			$business = krv_schema_business();
			$graph[]  = $business;
			$graph[]  = array(
				'@type'      => 'Service',
				'name'       => 'Verhuur in ' . $l['name'],
				'serviceType' => 'Verhuur van feest-, evenementen- en klusmaterialen',
				'provider'   => array( '@id' => home_url( '/#localbusiness' ) ),
				'areaServed' => array( '@type' => 'City', 'name' => $l['name'], 'containedInPlace' => array( '@type' => 'AdministrativeArea', 'name' => $l['gemeente'] . ', ' . $l['provincie'] ) ),
				'url'        => $ctx['url'],
			);
			$faq = array();
			foreach ( krv_location_faq( $l ) as $f ) {
				$faq[] = array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ) );
			}
			$graph[] = array( '@type' => 'FAQPage', 'mainEntity' => $faq );
			$graph[] = krv_schema_breadcrumbs( array( $home, array( 'Werkgebied', krv_service_area_url() ), array( 'Verhuur ' . $l['name'], $ctx['url'] ) ) );
			break;

		case 'area':
			$graph[] = krv_schema_business();
			$graph[] = krv_schema_breadcrumbs( array( $home, array( 'Werkgebied', $ctx['url'] ) ) );
			break;
	}
	return $graph;
}

add_action(
	'wp_head',
	function () {
		$graph = krv_schema_graph();
		if ( ! $graph ) {
			return;
		}
		$json = wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		echo "\n<script type=\"application/ld+json\" class=\"krv-schema\">" . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	},
	30
);

/* ===========================================================================
 * Afbeeldingen: alt-tekst als die ontbreekt
 * ========================================================================= */

add_filter(
	'wp_get_attachment_image_attributes',
	function ( $attr, $attachment ) {
		if ( ! empty( $attr['alt'] ) ) {
			return $attr;
		}
		$parent = $attachment->post_parent ? get_post( $attachment->post_parent ) : null;
		$post   = ( $parent && 'kr_product' === $parent->post_type ) ? $parent : ( is_singular( 'kr_product' ) ? get_queried_object() : null );
		if ( $post && 'kr_product' === $post->post_type ) {
			$p           = krv_get_product( $post );
			$attr['alt'] = ( $p && $p['is_sale'] ? $p['name'] : krv_with_huren( get_the_title( $post ) ) ) . ' – ' . krv_company();
		}
		return $attr;
	},
	10,
	2
);

/* ===========================================================================
 * Oude pagina's: niet indexeren en doorsturen
 * ========================================================================= */

/** Standaardlijst: overblijfselen van de vorige verhuurplugin (RentMy e.d.). */
function krv_seo_default_redirects() {
	return "/rentmy-products-list/ => /huren/\n/all-items/ => /huren/\n/rentmy-checkout/ => /winkelwagen/\n/rentmy-customer-login/ => /\n/rentmy-user-registration/ => /\n/rentmy-customer-profile/ => /\n/tf-wishlist/ => /huren/\n/cancel-appointment/ => /\n/add_services/ => /huren/";
}

/** Doorverwijzingen als [ '/oud/' => '/nieuw/' ]. */
function krv_seo_redirects() {
	$raw = krv_setting( 'seo_redirects' );
	$raw = null === $raw ? krv_seo_default_redirects() : (string) $raw;
	$out = array();
	foreach ( preg_split( '/\r?\n/', $raw ) as $line ) {
		if ( ! preg_match( '/^\s*(\S+)\s*(?:=>|->|\s)\s*(\S+)\s*$/', $line, $m ) ) {
			continue;
		}
		$from = '/' . trim( wp_parse_url( $m[1], PHP_URL_PATH ) ?: $m[1], '/' ) . '/';
		$out[ strtolower( $from ) ] = $m[2];
	}
	return $out;
}

add_action(
	'template_redirect',
	function () {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
			$path = '/' . substr( $path, strlen( $home ) );
		}
		$key = strtolower( '/' . trim( $path, '/' ) . '/' );
		$map = krv_seo_redirects();
		if ( '//' !== $key && isset( $map[ $key ] ) ) {
			$to = $map[ $key ];
			$to = preg_match( '#^https?://#', $to ) ? $to : home_url( $to );
			wp_safe_redirect( $to, 301, 'KRverhuur' );
			exit;
		}
	},
	0
);

/** Pagina's die (nog) bestaan maar niet in Google horen. */
function krv_seo_is_excluded_page() {
	if ( ! is_singular( 'page' ) ) {
		return false;
	}
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	if ( preg_match( '/^(rentmy-|tf-wishlist|cancel-appointment|add_services|add-services|all-items)/', (string) $slug ) ) {
		return true;
	}
	$paths = array_keys( krv_seo_redirects() );
	return in_array( '/' . $slug . '/', $paths, true );
}

add_filter(
	'wp_robots',
	function ( $robots ) {
		$ctx = krv_seo_context();
		if ( 'cart' === $ctx['type'] || krv_seo_is_excluded_page() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['max-image-preview'] );
		} elseif ( $ctx['type'] ) {
			$robots['max-image-preview'] = 'large';
		}
		return $robots;
	}
);
add_filter( 'wpseo_robots', function ( $v ) { return krv_seo_is_excluded_page() || 'cart' === krv_seo_context()['type'] ? 'noindex, follow' : $v; } );
add_filter(
	'rank_math/frontend/robots',
	function ( $r ) {
		if ( krv_seo_is_excluded_page() || 'cart' === krv_seo_context()['type'] ) {
			$r['index'] = 'noindex';
		}
		return $r;
	}
);

/* ===========================================================================
 * Sitemaps
 * ========================================================================= */

/** Alle werkgebied-URL's. */
function krv_seo_location_urls() {
	$urls = array( krv_service_area_url() );
	foreach ( krv_locations() as $slug => $l ) {
		$urls[] = krv_location_url( $slug );
	}
	return $urls;
}

// WordPress-sitemap (wp-sitemap.xml): werkgebied toevoegen, oude pagina's weglaten.
add_action(
	'init',
	function () {
		if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) {
			return;
		}
		if ( ! class_exists( 'KRV_Sitemap_Locations' ) ) {
			// phpcs:ignore Generic.Files.OneObjectStructurePerFile
			class KRV_Sitemap_Locations extends WP_Sitemaps_Provider {
				public function __construct() {
					$this->name        = 'werkgebied';
					$this->object_type = 'werkgebied';
				}
				public function get_url_list( $page_num, $object_subtype = '' ) {
					return array_map(
						function ( $u ) {
							return array( 'loc' => $u );
						},
						krv_seo_location_urls()
					);
				}
				public function get_max_num_pages( $object_subtype = '' ) {
					return 1;
				}
			}
		}
		wp_register_sitemap_provider( 'werkgebied', new KRV_Sitemap_Locations() );
	}
);

add_filter(
	'wp_sitemaps_posts_query_args',
	function ( $args, $post_type ) {
		if ( 'page' !== $post_type ) {
			return $args;
		}
		$exclude = array();
		foreach ( get_posts( array( 'post_type' => 'page', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
			$slug = (string) get_post_field( 'post_name', $id );
			if ( preg_match( '/^(rentmy-|tf-wishlist|cancel-appointment|add_services|add-services|all-items)/', $slug ) || isset( krv_seo_redirects()[ '/' . $slug . '/' ] ) ) {
				$exclude[] = $id;
			}
		}
		if ( $exclude ) {
			$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $exclude );
		}
		return $args;
	},
	10,
	2
);

// Yoast: werkgebied in de pagina-sitemap.
add_filter(
	'wpseo_sitemap_page_content',
	function ( $content ) {
		foreach ( krv_seo_location_urls() as $u ) {
			$content .= '<url><loc>' . esc_url( $u ) . '</loc></url>' . "\n";
		}
		return $content;
	}
);
// All in One SEO: extra pagina's.
add_filter(
	'aioseo_sitemap_additional_pages',
	function ( $pages ) {
		foreach ( krv_seo_location_urls() as $u ) {
			$pages[] = array( 'loc' => $u, 'lastmod' => gmdate( 'c' ), 'changefreq' => 'monthly', 'priority' => 0.7 );
		}
		return $pages;
	}
);
// Rank Math: extra URL's in de pagina-sitemap.
add_filter(
	'rank_math/sitemap/page/content',
	function ( $content ) {
		foreach ( krv_seo_location_urls() as $u ) {
			$content .= '<url><loc>' . esc_url( $u ) . '</loc></url>' . "\n";
		}
		return $content;
	}
);

/* Geen sitemap met gebruikers (die toont gebruikersnamen en heeft geen waarde voor Google). */
add_filter(
	'wp_sitemaps_add_provider',
	function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);
