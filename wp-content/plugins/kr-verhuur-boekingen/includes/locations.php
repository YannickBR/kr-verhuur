<?php
/**
 * Werkgebied: plaatsen waar KRverhuur levert, met een eigen pagina per plaats
 * (/verhuur/<plaats>/) voor lokale vindbaarheid in Google.
 *
 * Per plaats is een eigen tekst toe te voegen via Huurartikelen → Werkgebied (SEO).
 * Afstanden zijn hemelsbreed vanaf Marknesse en worden "ca." genoemd.
 */

defined( 'ABSPATH' ) || exit;

/** Vestiging (Waterpas, Marknesse) voor afstandsberekening en LocalBusiness-schema. */
function krv_base_location() {
	return array(
		'lat' => (float) ( krv_setting( 'geo_lat' ) ?: 52.7046 ),
		'lng' => (float) ( krv_setting( 'geo_lng' ) ?: 5.8752 ),
	);
}

/**
 * Standaard werkgebied. Coördinaten zijn benaderingen van het dorps-/stadscentrum.
 * gemeente/provincie worden in de teksten gebruikt.
 */
function krv_default_locations() {
	return array(
		'marknesse'     => array( 'Marknesse', 52.7090, 5.8660, 'Noordoostpolder', 'Flevoland' ),
		'emmeloord'     => array( 'Emmeloord', 52.7108, 5.7486, 'Noordoostpolder', 'Flevoland' ),
		'ens'           => array( 'Ens', 52.6378, 5.8290, 'Noordoostpolder', 'Flevoland' ),
		'urk'           => array( 'Urk', 52.6618, 5.6012, 'Urk', 'Flevoland' ),
		'espel'         => array( 'Espel', 52.7240, 5.6990, 'Noordoostpolder', 'Flevoland' ),
		'nagele'        => array( 'Nagele', 52.6454, 5.7216, 'Noordoostpolder', 'Flevoland' ),
		'rutten'        => array( 'Rutten', 52.8086, 5.7372, 'Noordoostpolder', 'Flevoland' ),
		'kraggenburg'   => array( 'Kraggenburg', 52.6620, 5.8990, 'Noordoostpolder', 'Flevoland' ),
		'creil'         => array( 'Creil', 52.7620, 5.6600, 'Noordoostpolder', 'Flevoland' ),
		'bant'          => array( 'Bant', 52.7700, 5.7520, 'Noordoostpolder', 'Flevoland' ),
		'tollebeek'     => array( 'Tollebeek', 52.6850, 5.6810, 'Noordoostpolder', 'Flevoland' ),
		'swifterbant'   => array( 'Swifterbant', 52.5670, 5.6370, 'Dronten', 'Flevoland' ),
		'biddinghuizen' => array( 'Biddinghuizen', 52.4550, 5.6930, 'Dronten', 'Flevoland' ),
		'vollenhove'    => array( 'Vollenhove', 52.6800, 5.9550, 'Steenwijkerland', 'Overijssel' ),
		'zwartsluis'    => array( 'Zwartsluis', 52.6360, 6.0720, 'Zwartewaterland', 'Overijssel' ),
		'blokzijl'      => array( 'Blokzijl', 52.7260, 5.9620, 'Steenwijkerland', 'Overijssel' ),
		'kuinre'        => array( 'Kuinre', 52.7910, 5.8320, 'Steenwijkerland', 'Overijssel' ),
		'blankenham'    => array( 'Blankenham', 52.7620, 5.8820, 'Steenwijkerland', 'Overijssel' ),
		'lemmer'        => array( 'Lemmer', 52.8440, 5.7110, 'De Fryske Marren', 'Friesland' ),
	);
}

/** Regio's die naast de plaatsen in teksten en schema genoemd worden. */
function krv_service_regions() {
	return array( 'Noordoostpolder', 'Flevoland', 'Overijssel', 'Urk', 'Dronten', 'Steenwijkerland' );
}

/** Afstand hemelsbreed in km (haversine), afgerond. */
function krv_distance_km( $lat1, $lng1, $lat2, $lng2 ) {
	$r    = 6371;
	$dlat = deg2rad( $lat2 - $lat1 );
	$dlng = deg2rad( $lng2 - $lng1 );
	$a    = sin( $dlat / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dlng / 2 ) ** 2;
	return (int) round( $r * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) ) );
}

/**
 * Alle actieve plaatsen, met eigen tekst en berekende afstand.
 *
 * @return array slug => [ name, lat, lng, gemeente, provincie, km, intro, enabled ]
 */
function krv_locations() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$saved = get_option( 'krv_locations', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$base  = krv_base_location();
	$out   = array();
	foreach ( krv_default_locations() as $slug => $l ) {
		$s = isset( $saved[ $slug ] ) && is_array( $saved[ $slug ] ) ? $saved[ $slug ] : array();
		if ( isset( $s['enabled'] ) && ! $s['enabled'] ) {
			continue;
		}
		$out[ $slug ] = array(
			'slug'      => $slug,
			'name'      => $l[0],
			'lat'       => $l[1],
			'lng'       => $l[2],
			'gemeente'  => $l[3],
			'provincie' => $l[4],
			'km'        => krv_distance_km( $base['lat'], $base['lng'], $l[1], $l[2] ),
			'intro'     => isset( $s['intro'] ) ? (string) $s['intro'] : '',
		);
	}
	uasort(
		$out,
		function ( $a, $b ) {
			return $a['km'] <=> $b['km'];
		}
	);
	$cache = $out;
	return $out;
}

function krv_location( $slug ) {
	$all = krv_locations();
	return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
}

/** Dichtstbijzijnde andere plaatsen. */
function krv_nearby_locations( $slug, $count = 5 ) {
	$here = krv_location( $slug );
	if ( ! $here ) {
		return array();
	}
	$list = array();
	foreach ( krv_locations() as $s => $l ) {
		if ( $s !== $slug ) {
			$l['dist'] = krv_distance_km( $here['lat'], $here['lng'], $l['lat'], $l['lng'] );
			$list[]    = $l;
		}
	}
	usort(
		$list,
		function ( $a, $b ) {
			return $a['dist'] <=> $b['dist'];
		}
	);
	return array_slice( $list, 0, $count );
}

function krv_location_url( $slug ) {
	return get_option( 'permalink_structure' ) ? home_url( '/verhuur/' . $slug . '/' ) : add_query_arg( 'krv_location', $slug, home_url( '/' ) );
}

/** Overzichtspagina van het werkgebied. */
function krv_service_area_url() {
	return get_option( 'permalink_structure' ) ? home_url( '/verhuur/' ) : add_query_arg( 'krv_location', 'overzicht', home_url( '/' ) );
}

/* ---------------------------------------------------------------------------
 * URL's: /verhuur/ en /verhuur/<plaats>/ (weergave via het theme)
 * ------------------------------------------------------------------------- */

add_action(
	'init',
	function () {
		add_rewrite_rule( '^verhuur/?$', 'index.php?krv_location=overzicht', 'top' );
		add_rewrite_rule( '^verhuur/([a-z0-9-]+)/?$', 'index.php?krv_location=$matches[1]', 'top' );
	},
	20
);

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'krv_location';
		return $vars;
	}
);

/** Huidige plaats-slug ('overzicht' voor het overzicht), of '' als dit geen werkgebiedpagina is. */
function krv_current_location_slug() {
	$slug = sanitize_title( (string) get_query_var( 'krv_location' ) );
	if ( '' === $slug ) {
		return '';
	}
	return ( 'overzicht' === $slug || krv_location( $slug ) ) ? $slug : '';
}

/* Onbekende plaats → 404. */
add_action(
	'template_redirect',
	function () {
		$raw = (string) get_query_var( 'krv_location' );
		if ( '' !== $raw && '' === krv_current_location_slug() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}
	},
	1
);

/* ---------------------------------------------------------------------------
 * Veelgestelde vragen per plaats (ook als FAQ-schema voor Google)
 * ------------------------------------------------------------------------- */

function krv_location_faq( $loc ) {
	$company = krv_setting( 'company_name' ) ?: 'KRverhuur';
	$name    = $loc['name'];
	$faq     = array(
		array(
			'q' => 'Bezorgen jullie ook in ' . $name . '?',
			'a' => 'Ja. ' . $company . ' bezorgt en haalt op in ' . $name . ( $loc['km'] > 0 ? ' (ca. ' . $loc['km'] . ' km vanaf onze locatie in Marknesse)' : '' ) . '. Kies bij het reserveren de optie "Halen en brengen" en vul je adres in. Ophalen bij ons in Marknesse kan natuurlijk ook.',
		),
		array(
			'q' => 'Hoe reserveer ik in ' . $name . '?',
			'a' => 'Kies een artikel, selecteer je huurdag(en) in de kalender en leg het in je winkelwagen. Je kunt meerdere artikelen in één keer bestellen. Wij bevestigen je reservering zo snel mogelijk per e-mail.',
		),
		array(
			'q' => 'Kan ik voor één dag of meerdere dagen huren?',
			'a' => 'Allebei. Je kiest zelf of je één dag huurt of een langere periode; de prijs per extra dag is meestal lager dan de eerste dag.',
		),
		array(
			'q' => 'Wat kan ik huren voor een feest in ' . $name . '?',
			'a' => 'Onder andere een photobooth, springkussen, bumperbaan, toiletwagen, statafels en meubilair, terrasverwarming en koeling. Daarnaast verhuren we gereedschap voor klussen in en om huis.',
		),
	);
	return apply_filters( 'krv_location_faq', $faq, $loc );
}

/* ---------------------------------------------------------------------------
 * Beheer: Huurartikelen → Werkgebied (SEO)
 * ------------------------------------------------------------------------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=kr_product', 'Werkgebied (SEO)', 'Werkgebied (SEO)', 'manage_options', 'krv-locations', 'krv_locations_page' );
	}
);

add_action(
	'admin_post_krv_save_locations',
	function () {
		check_admin_referer( 'krv_locations' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		$in  = isset( $_POST['loc'] ) ? wp_unslash( (array) $_POST['loc'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$out = array();
		foreach ( krv_default_locations() as $slug => $l ) {
			$row          = isset( $in[ $slug ] ) ? (array) $in[ $slug ] : array();
			$out[ $slug ] = array(
				'enabled' => empty( $row['enabled'] ) ? 0 : 1,
				'intro'   => sanitize_textarea_field( $row['intro'] ?? '' ),
			);
		}
		update_option( 'krv_locations', $out );
		wp_safe_redirect( admin_url( 'edit.php?post_type=kr_product&page=krv-locations&saved=1' ) );
		exit;
	}
);

function krv_locations_page() {
	$saved = get_option( 'krv_locations', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$base  = krv_base_location();
	?>
	<div class="wrap">
		<h1>Werkgebied (SEO)</h1>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Werkgebied opgeslagen.</p></div>
		<?php endif; ?>
		<p>Voor elke plaats in het werkgebied is er een eigen pagina, bijv. <a href="<?php echo esc_url( krv_location_url( 'urk' ) ); ?>" target="_blank"><?php echo esc_html( krv_location_url( 'urk' ) ); ?></a>, en een overzicht op <a href="<?php echo esc_url( krv_service_area_url() ); ?>" target="_blank"><?php echo esc_html( krv_service_area_url() ); ?></a>.
			Zo word je beter gevonden op zoekopdrachten als "springkussen huren Urk" of "toiletwagen huren Emmeloord".</p>
		<p><strong>Tip:</strong> een eigen tekst per plaats (bijv. een evenement dat je daar verzorgd hebt, of hoe je er bezorgt) maakt de pagina uniek en waardevoller voor Google en bezoekers. Een paar zinnen is genoeg.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="krv_save_locations">
			<?php wp_nonce_field( 'krv_locations' ); ?>
			<table class="widefat striped" style="max-width:1100px">
				<thead><tr><th style="width:60px">Actief</th><th style="width:160px">Plaats</th><th style="width:170px">Gemeente / afstand</th><th>Eigen tekst (optioneel)</th></tr></thead>
				<tbody>
				<?php foreach ( krv_default_locations() as $slug => $l ) : ?>
					<?php
					$row     = isset( $saved[ $slug ] ) ? $saved[ $slug ] : array();
					$enabled = ! isset( $row['enabled'] ) || $row['enabled'];
					?>
					<tr>
						<td><input type="checkbox" name="loc[<?php echo esc_attr( $slug ); ?>][enabled]" value="1" <?php checked( $enabled ); ?>></td>
						<td><strong><?php echo esc_html( $l[0] ); ?></strong><br><a href="<?php echo esc_url( krv_location_url( $slug ) ); ?>" target="_blank">Bekijk pagina</a></td>
						<td><?php echo esc_html( $l[3] . ', ' . $l[4] ); ?><br><span class="description">ca. <?php echo (int) krv_distance_km( $base['lat'], $base['lng'], $l[1], $l[2] ); ?> km</span></td>
						<td><textarea name="loc[<?php echo esc_attr( $slug ); ?>][intro]" rows="2" style="width:100%" placeholder="Bijv. Al jaren verzorgen we in <?php echo esc_attr( $l[0] ); ?> de verhuur voor bruiloften, verjaardagen en het dorpsfeest."><?php echo esc_textarea( $row['intro'] ?? '' ); ?></textarea></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( 'Werkgebied opslaan' ); ?>
		</form>
	</div>
	<?php
}
