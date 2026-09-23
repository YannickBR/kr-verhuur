<?php
/**
 * Template-hulpjes: iconen, logo en kaarten.
 */

defined( 'ABSPATH' ) || exit;

/** Lijn-icoon (24×24, stroke = currentColor). */
function krt_icon( $name, $class = '' ) {
	static $paths = array(
		'box'          => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
		'camper'       => '<path d="M2 17V7a2 2 0 0 1 2-2h11l5 5v7"/><path d="M2 17h20"/><circle cx="7" cy="17.5" r="2"/><circle cx="17" cy="17.5" r="2"/><path d="M5 9h4v3H5zM12 9h3"/>',
		'camera'       => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7l1.5-3h5L16 7"/><circle cx="12" cy="13.5" r="3.5"/>',
		'toilet'       => '<path d="M6 3h7v7H6z"/><path d="M4 10h14a7 7 0 0 1-7 7H10a6 6 0 0 1-6-6z"/><path d="M9 17l-1 4h7l-1-4"/>',
		'castle'       => '<path d="M3 21V9l3 2V6l3 2 3-4 3 4 3-2v5l3-2v12z"/><path d="M10 21v-5a2 2 0 0 1 4 0v5"/>',
		'bumper'       => '<path d="M4 15l1.5-5A2 2 0 0 1 7.4 8.5h9.2a2 2 0 0 1 1.9 1.5L20 15"/><rect x="2" y="15" width="20" height="4" rx="2"/><path d="M12 8.5V4M10 4h4"/>',
		'chair'        => '<path d="M7 3h10v8H7z"/><path d="M5 11h14v3H5z"/><path d="M7 14v7M17 14v7"/>',
		'flame'        => '<path d="M12 22a7 7 0 0 0 7-7c0-4-3-6-4-10-2 2-3 4-3 6-1-1-2-2-2-4-3 3-5 5-5 8a7 7 0 0 0 7 7z"/>',
		'tool'         => '<path d="M14.7 6.3a4 4 0 0 0 5 5L21 13l-8 8-3-3 8-8-1.3-1.3a4 4 0 0 1-5-5L13 3z"/><path d="M3 21l6-6"/>',
		'tent'         => '<path d="M12 3L2 21h20z"/><path d="M12 3v18M9 21l3-6 3 6"/>',
		'music'        => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
		'cart'         => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.1a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.5L21 8H6"/>',
		'calendar'     => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'check'        => '<path d="M4 12l5 5L20 6"/>',
		'phone'        => '<path d="M5 3h4l2 5-3 2a11 11 0 0 0 6 6l2-3 5 2v4a2 2 0 0 1-2 2A18 18 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
		'mail'         => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'pin'          => '<path d="M12 22s7-7 7-12a7 7 0 0 0-14 0c0 5 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
		'arrow'        => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'external'     => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
		'chevronLeft'  => '<path d="M15 5l-7 7 7 7"/>',
		'chevronRight' => '<path d="M9 5l7 7-7 7"/>',
		'menu'         => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'        => '<path d="M6 6l12 12M18 6L6 18"/>',
	);
	$d = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['box'];
	return '<svg class="icon ' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/** Alle iconen als JSON voor de reserverings-JS. */
function krt_icons_for_js() {
	$out = array();
	foreach ( array( 'chevronLeft', 'chevronRight', 'calendar', 'cart', 'check' ) as $n ) {
		$out[ $n ] = krt_icon( $n );
	}
	return $out;
}

/** Het K-beeldmerk. */
function krt_mark_paths() {
	return '<g fill="#519f81"><path d="M18 0h46v172a24 24 0 0 1-24 24H18A18 18 0 0 1 0 178V18A18 18 0 0 1 18 0z"/><circle cx="118" cy="62" r="35"/><path d="M82 108l68 25a20 20 0 0 1 13 19v44l-68-27a20 20 0 0 1-13-19z"/></g>';
}

/**
 * Logo. Als er via Weergave → Customizer → Site-identiteit een eigen logo is
 * ingesteld, wordt dat gebruikt; anders het ingebouwde SVG-logo.
 */
function krt_logo( $variant = 'light' ) {
	if ( has_custom_logo() ) {
		$id = get_theme_mod( 'custom_logo' );
		return wp_get_attachment_image( $id, 'full', false, array( 'class' => 'custom-logo', 'alt' => get_bloginfo( 'name' ) ) );
	}
	$c = 'dark' === $variant ? '#002533' : '#dff1fb';
	return '<svg viewBox="0 0 900 200" role="img" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . krt_mark_paths() .
		'<text x="222" y="118" font-family="Nunito, Arial, sans-serif" font-size="118" font-weight="900" fill="' . $c . '" letter-spacing="-2">KRverhuur</text>' .
		'<text x="226" y="178" font-family="Nunito, Arial, sans-serif" font-size="44" font-weight="600" fill="' . $c . '">Voor als je het zelf niet hebt!</text></svg>';
}

function krt_mark() {
	return '<svg viewBox="0 0 170 200" aria-hidden="true">' . krt_mark_paths() . '</svg>';
}

/** Instelling uit de plugin, met terugval als de plugin (nog) niet actief is. */
function krt_setting( $key, $default = '' ) {
	if ( function_exists( 'krv_setting' ) ) {
		$v = krv_setting( $key );
		return ( null === $v || '' === $v ) ? $default : $v;
	}
	return $default;
}

function krt_euro( $n ) {
	return function_exists( 'krv_euro' ) ? krv_euro( $n ) : '€ ' . number_format_i18n( (float) $n, 2 );
}

/**
 * Ingevoerde prijs (artikel of extra optie) als HTML, zoals de website hem toont (incl. of excl. btw).
 * Het bedrag incl. btw staat in data-price-incl, zodat de wisselknop het zonder herladen kan omrekenen.
 */
function krt_price( $entered ) {
	if ( ! function_exists( 'krv_display_amount' ) ) {
		return esc_html( krt_euro( $entered ) );
	}
	$incl = round( krv_entered_to_incl( $entered ), 2 );
	return '<span class="js-price" data-price-incl="' . esc_attr( $incl ) . '">' . esc_html( krt_euro( krv_display_amount( $incl ) ) ) . '</span>';
}

/** "incl. btw" of "excl. btw" als HTML (wordt bijgewerkt door de wisselknop). */
function krt_vat_label() {
	$label = function_exists( 'krv_vat_label' ) ? krv_vat_label() : 'incl. btw';
	return '<span class="js-vat-label">' . esc_html( $label ) . '</span>';
}

/** Wisselknop incl./excl. btw (alleen als die in de instellingen aan staat). */
function krt_vat_toggle( $class = '' ) {
	if ( ! function_exists( 'krv_vat_toggle_enabled' ) || ! krv_vat_toggle_enabled() ) {
		return '';
	}
	$excl = krv_show_excl_vat();
	return '<div class="vat-toggle ' . esc_attr( $class ) . '" role="group" aria-label="Prijzen tonen">' .
		'<button type="button" data-vat-set="incl" aria-pressed="' . ( $excl ? 'false' : 'true' ) . '">Incl. btw</button>' .
		'<button type="button" data-vat-set="excl" aria-pressed="' . ( $excl ? 'true' : 'false' ) . '">Excl. btw</button>' .
		'</div>';
}

function krt_is_external( $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	return $host && wp_parse_url( home_url(), PHP_URL_HOST ) !== $host;
}

/** Kaart voor een huurgroep. */
function krt_group_card( $term ) {
	$meta = krv_group_meta( $term );
	$url  = krv_group_url( $term );
	$ext  = ! empty( $meta['external_url'] );
	$from = $ext ? null : krv_group_from_price( $term );
	$host = $ext ? preg_replace( '/^www\./', '', (string) wp_parse_url( $meta['external_url'], PHP_URL_HOST ) ) : '';
	?>
	<a class="group-card<?php echo $ext ? ' is-external' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
		<?php if ( $ext ) : ?><span class="badge"><?php echo krt_icon( 'external' ); // phpcs:ignore ?> <?php echo esc_html( ucfirst( strtok( $host, '.' ) ) ); ?></span><?php endif; ?>
		<div class="group-icon"><?php echo krt_icon( $meta['icon'] ); // phpcs:ignore ?></div>
		<h3><?php echo esc_html( $term->name ); ?></h3>
		<p><?php echo esc_html( $meta['tagline'] ); ?></p>
		<span class="more">
			<?php if ( $ext ) : ?>
				Bekijk op <?php echo esc_html( ucfirst( strtok( $host, '.' ) ) ); ?> <?php echo krt_icon( 'external' ); // phpcs:ignore ?>
			<?php else : ?>
				<?php echo $from ? 'Vanaf ' . krt_price( $from ) . ' p/d' : 'Bekijken'; ?> <?php echo krt_icon( 'arrow' ); // phpcs:ignore ?>
			<?php endif; ?>
		</span>
	</a>
	<?php
}

/** Afbeelding of icoon van een huurartikel. */
function krt_product_media( $post_id, $size = 'large' ) {
	$p    = krv_get_product( $post_id );
	$icon = $p['group'] ? krv_group_meta( $p['group'] )['icon'] : 'box';
	echo '<div class="product-media">';
	if ( has_post_thumbnail( $post_id ) ) {
		echo get_the_post_thumbnail( $post_id, $size, array( 'loading' => 'lazy' ) );
	} else {
		echo krt_icon( $icon ); // phpcs:ignore
	}
	if ( $p['group'] ) {
		echo '<span class="badge">' . esc_html( $p['group']->name ) . '</span>';
	}
	echo '</div>';
}

/** Kaart voor een huurartikel. */
function krt_product_card( $post_id ) {
	$p = krv_get_product( $post_id );
	?>
	<a class="product-card" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
		<?php krt_product_media( $post_id, 'medium_large' ); ?>
		<div class="product-body">
			<h3><?php echo esc_html( $p['name'] ); ?></h3>
			<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 24 ) ); ?></p>
			<div class="product-foot">
				<span class="price"><?php echo krt_price( $p['price_day'] ); // phpcs:ignore ?> <small>/ dag <span class="only-excl">excl. btw</span></small></span>
				<span class="btn btn-outline">Bekijken</span>
			</div>
		</div>
	</a>
	<?php
}
