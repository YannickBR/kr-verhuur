<?php
/**
 * Activatie: standaardinstellingen, huurgroepen en voorbeeldartikelen.
 * Er wordt alleen iets aangemaakt als het nog niet bestaat.
 */

defined( 'ABSPATH' ) || exit;

function krv_activate() {
	krv_register_types();

	if ( false === get_option( 'krv_settings', false ) ) {
		add_option( 'krv_settings', krv_default_settings() );
	}
	if ( false === get_option( 'krv_extras', false ) ) {
		add_option( 'krv_extras', krv_default_extras() );
	}

	krv_seed_content();
	flush_rewrite_rules();
}

function krv_seed_groups() {
	return array(
		'camper'       => array( 'Camper', 'camper', 'Op avontuur met onze camper – boeken via Goboony.', 'goboony' ),
		'photobooth'   => array( 'Photobooth', 'camera', 'Onvergetelijke foto\'s op elk feest.', '' ),
		'toiletwagen'  => array( 'Toiletwagen', 'toilet', 'Nette, schone sanitaire voorzieningen.', '' ),
		'springkussen' => array( 'Springkussen', 'castle', 'Uren springplezier voor jong en oud.', '' ),
		'bumperbaan'   => array( 'Bumperbaan', 'bumper', 'Botsen, lachen en racen in de bumperbaan.', '' ),
		'meubilair'    => array( 'Meubilair', 'chair', 'Statafels, bierbanken, stoelen en tafels.', '' ),
		'verwarming'   => array( 'Verwarming', 'flame', 'Terrasheaters en heaters voor elk seizoen.', '' ),
		'gereedschap'  => array( 'Gereedschap', 'tool', 'Professioneel gereedschap voor elke klus.', '' ),
	);
}

/** Voorbeeldartikelen – VOORBEELDPRIJZEN, pas aan in het beheer. */
function krv_seed_products() {
	return array(
		array( 'photobooth', 'Photobooth Classic', 'Complete photobooth met touchscreen, studiolamp en directe fotoprints. Inclusief 400 prints en digitale galerij.', "Directe prints (10x15)\nDigitale galerij\nEigen tekst op de foto", 249, 125, 150, 3, 1, 0, array( 'delivery', 'setup', 'printpack', 'props', 'attendant' ) ),
		array( 'photobooth', 'Magic Mirror', 'Een spiegel op ware grootte die foto\'s maakt. Met animaties en een eigen rand op de foto.', "Full-length spiegel\nAnimaties & touch\nOnbeperkt prints", 349, 175, 250, 3, 1, 0, array( 'delivery', 'setup', 'printpack', 'props', 'attendant' ) ),
		array( 'toiletwagen', 'Luxe toiletwagen', 'Luxe toiletwagen met aparte dames- en herenruimte, verlichting, verwarming en stromend water.', "2 toiletten + 2 urinoirs\nWastafels met stromend water\nVerlichting & verwarming", 295, 95, 250, 14, 1, 0, array( 'delivery', 'toiletcleaning', 'supplies' ) ),
		array( 'springkussen', 'Springkussen Kasteel', 'Kleurrijk springkasteel voor kinderen tot 12 jaar. Afmeting 4 x 4 meter.', "4 x 4 m\nTot 8 kinderen\nInclusief blower", 85, 45, 100, 7, 1, 0, array( 'delivery', 'setup', 'cleaning' ) ),
		array( 'springkussen', 'Stormbaan', 'Uitdagende stormbaan met hindernissen en glijbaan. Afmeting 10 x 3 meter.', "10 x 3 m\nVoor jong en oud\nInclusief blowers", 175, 90, 150, 7, 1, 0, array( 'delivery', 'setup', 'cleaning', 'attendant' ) ),
		array( 'bumperbaan', 'Bumperbaan compleet', 'Opblaasbare baan met elektrische bumperauto\'s. Een topper op elk evenement.', "Baan 12 x 8 m\n6 bumperauto's\nLaders inbegrepen", 495, 250, 300, 5, 1, 0, array( 'delivery', 'setup', 'attendant', 'cleaning' ) ),
		array( 'meubilair', 'Statafel', 'Stevige statafel met een diameter van 80 cm. Mooi met een rok of kleed.', "Ø 80 cm\nHoogte 110 cm\nInklapbaar", 7.5, 3, 0, 14, 30, 1, array( 'delivery', 'tablecloths', 'cleaning' ) ),
		array( 'meubilair', 'Biertafelset', 'Tafel met twee banken, geschikt voor 8 personen. Binnen en buiten te gebruiken.', "8 personen\n220 x 50 cm\nMakkelijk te vervoeren", 12.5, 5, 0, 14, 20, 1, array( 'delivery', 'cleaning' ) ),
		array( 'verwarming', 'Terrasheater op gas', 'Staande terrasheater van 13 kW. Voor een warm terras of feest buiten.', "13 kW\nHoogte 2,2 m\nWerkt op propaan", 35, 15, 50, 14, 6, 1, array( 'delivery', 'fuel', 'cleaning' ) ),
		array( 'verwarming', 'Heteluchtkanon', 'Krachtig heteluchtkanon voor tenten, loodsen en bouwplaatsen.', "30 kW\nMet thermostaat\nVoor tent of hal", 65, 30, 100, 30, 2, 1, array( 'delivery', 'fuel' ) ),
		array( 'gereedschap', 'Trilplaat', 'Trilplaat voor het verdichten van zand en grind. Ideaal voor bestrating.', "90 kg\nBenzinemotor\nWerkbreedte 50 cm", 55, 35, 100, 14, 1, 0, array( 'delivery', 'fuel', 'cleaning', 'insurance' ) ),
		array( 'gereedschap', 'Hogedrukreiniger', 'Professionele hogedrukreiniger voor terrassen, opritten en gevels.', "200 bar\nInclusief vuilfrees\nSnoerlengte 10 m", 45, 25, 50, 14, 1, 0, array( 'delivery', 'cleaning', 'insurance' ) ),
		array( 'gereedschap', 'Breekhamer', 'Zware breekhamer voor sloopwerk aan beton en tegels.', "1500 W\nInclusief beitels\nIn koffer", 39, 25, 75, 14, 1, 0, array( 'delivery', 'cleaning', 'insurance' ) ),
	);
}

function krv_seed_content() {
	$order = 0;
	foreach ( krv_seed_groups() as $slug => $g ) {
		$order++;
		$term = get_term_by( 'slug', $slug, 'kr_group' );
		if ( ! $term ) {
			$res = wp_insert_term( $g[0], 'kr_group', array( 'slug' => $slug ) );
			if ( is_wp_error( $res ) ) {
				continue;
			}
			$tid = $res['term_id'];
			update_term_meta( $tid, '_krv_icon', $g[1] );
			update_term_meta( $tid, '_krv_tagline', $g[2] );
			update_term_meta( $tid, '_krv_order', $order );
			if ( 'goboony' === $g[3] ) {
				update_term_meta( $tid, '_krv_external_url', krv_setting( 'goboony_url' ) );
			}
		}
	}

	$existing = get_posts( array( 'post_type' => 'kr_product', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $existing ) {
		return;
	}
	$menu_order = 0;
	foreach ( krv_seed_products() as $p ) {
		list( $group, $name, $desc, $features, $price, $extra, $deposit, $max, $stock, $qty, $extras ) = $p;
		$id = wp_insert_post(
			array(
				'post_type'    => 'kr_product',
				'post_status'  => 'publish',
				'post_title'   => $name,
				'post_excerpt' => $desc,
				'post_content' => $desc,
				'menu_order'   => $menu_order++,
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		wp_set_object_terms( $id, $group, 'kr_group' );
		update_post_meta( $id, '_krv_price_day', $price );
		update_post_meta( $id, '_krv_price_extra_day', $extra );
		update_post_meta( $id, '_krv_deposit', $deposit );
		update_post_meta( $id, '_krv_max_days', $max );
		update_post_meta( $id, '_krv_stock', $stock );
		update_post_meta( $id, '_krv_allow_quantity', $qty );
		update_post_meta( $id, '_krv_features', $features );
		update_post_meta( $id, '_krv_extras', $extras );
	}
}
