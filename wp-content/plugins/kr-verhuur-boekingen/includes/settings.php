<?php
/**
 * Instellingen en extra opties (opgeslagen in wp_options).
 */

defined( 'ABSPATH' ) || exit;

function krv_default_settings() {
	return array(
		'company_name'    => 'KR Verhuur',
		'email'           => get_option( 'admin_email' ),
		'notify_email'    => get_option( 'admin_email' ),
		'phone'           => '06 00 00 00 00',
		'region'          => '',
		'goboony_url'     => 'https://www.goboony.nl/',
		'min_lead_days'   => 1,
		'max_days_ahead'  => 365,
		'blocking_status' => array( 'pending', 'confirmed' ),
		'vat_rate'        => 21,
		'prices_incl_vat' => 1,      // Ingevoerde prijzen (artikelen, extra opties) zijn incl. btw.
		'display_vat'     => 'incl', // Website toont prijzen 'incl' of 'excl' btw.
	);
}

function krv_settings() {
	$saved = get_option( 'krv_settings', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), krv_default_settings() );
}

function krv_setting( $key ) {
	$s = krv_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/**
 * Extra opties. type "fixed" = per boeking, "perDay" = per huurdag.
 * Te beheren via Boekingen → Instellingen.
 */
function krv_default_extras() {
	return array(
		'delivery'       => array( 'label' => 'Halen en brengen', 'description' => 'Wij bezorgen, plaatsen en halen weer op (binnen 25 km).', 'price' => 45, 'type' => 'fixed', 'needs_address' => 1 ),
		'setup'          => array( 'label' => 'Opbouwen en afbreken', 'description' => 'Wij bouwen alles voor je op en weer af.', 'price' => 35, 'type' => 'fixed', 'needs_address' => 0 ),
		'cleaning'       => array( 'label' => 'Schoonmaakkosten', 'description' => 'Geen gedoe achteraf: wij maken alles schoon.', 'price' => 30, 'type' => 'fixed', 'needs_address' => 0 ),
		'toiletcleaning' => array( 'label' => 'Schoonmaak & afvoer', 'description' => 'Legen, reinigen en afvoeren van afvalwater.', 'price' => 75, 'type' => 'fixed', 'needs_address' => 0 ),
		'supplies'       => array( 'label' => 'Toiletpakket', 'description' => 'Toiletpapier, handzeep en papieren handdoekjes.', 'price' => 15, 'type' => 'fixed', 'needs_address' => 0 ),
		'printpack'      => array( 'label' => 'Extra printpakket', 'description' => '400 extra prints voor de photobooth.', 'price' => 40, 'type' => 'fixed', 'needs_address' => 0 ),
		'props'          => array( 'label' => 'Props & accessoires', 'description' => 'Brillen, hoeden, borden en meer.', 'price' => 15, 'type' => 'fixed', 'needs_address' => 0 ),
		'attendant'      => array( 'label' => 'Begeleiding', 'description' => 'Een medewerker aanwezig tijdens je feest.', 'price' => 95, 'type' => 'perDay', 'needs_address' => 0 ),
		'fuel'           => array( 'label' => 'Brandstof / gasfles', 'description' => 'Volle gasfles of tank brandstof inbegrepen.', 'price' => 25, 'type' => 'perDay', 'needs_address' => 0 ),
		'tablecloths'    => array( 'label' => 'Tafelkleden', 'description' => 'Witte tafelkleden, gewassen en gestreken.', 'price' => 20, 'type' => 'fixed', 'needs_address' => 0 ),
		'insurance'      => array( 'label' => 'Breukverzekering', 'description' => 'Schade door breuk verzekerd (eigen risico € 50).', 'price' => 10, 'type' => 'perDay', 'needs_address' => 0 ),
	);
}

function krv_extras() {
	$saved = get_option( 'krv_extras', null );
	return is_array( $saved ) ? $saved : krv_default_extras();
}

/** Boekingsstatussen. */
function krv_statuses() {
	return array(
		'pending'   => 'Aanvraag',
		'confirmed' => 'Bevestigd',
		'completed' => 'Afgerond',
		'cancelled' => 'Geannuleerd',
	);
}

/** Statussen die de agenda blokkeren. */
function krv_blocking_statuses() {
	$s = krv_setting( 'blocking_status' );
	return is_array( $s ) && $s ? $s : array( 'pending', 'confirmed' );
}

/* ---------------------------------------------------------------------------
 * Btw
 *
 * Bedragen in boekingen (regels, totaal) worden altijd INCLUSIEF btw opgeslagen:
 * dat is wat de klant betaalt. Ingevoerde prijzen worden zo nodig omgerekend.
 * ------------------------------------------------------------------------- */

function krv_vat_rate() {
	return max( 0, (float) krv_setting( 'vat_rate' ) );
}

/** Toont de website prijzen exclusief btw? */
function krv_show_excl_vat() {
	return 'excl' === krv_setting( 'display_vat' );
}

/** Ingevoerde prijs → bedrag incl. btw. */
function krv_entered_to_incl( $amount ) {
	$amount = (float) $amount;
	return krv_setting( 'prices_incl_vat' ) ? $amount : $amount * ( 1 + krv_vat_rate() / 100 );
}

function krv_incl_to_excl( $amount ) {
	return (float) $amount / ( 1 + krv_vat_rate() / 100 );
}

/** Bedrag incl. btw → bedrag zoals de website het toont. */
function krv_display_amount( $incl ) {
	return krv_show_excl_vat() ? krv_incl_to_excl( $incl ) : (float) $incl;
}

/** "incl. btw" of "excl. btw". */
function krv_vat_label() {
	return krv_show_excl_vat() ? 'excl. btw' : 'incl. btw';
}

/** Btw-opbouw van een bedrag incl. btw. */
function krv_vat_breakdown( $incl ) {
	$incl = round( (float) $incl, 2 );
	$excl = round( krv_incl_to_excl( $incl ), 2 );
	return array( 'excl' => $excl, 'vat' => round( $incl - $excl, 2 ), 'incl' => $incl, 'rate' => krv_vat_rate() );
}

/** Extra opties met prijzen omgerekend naar incl. btw (voor berekeningen). */
function krv_extras_incl() {
	$out = array();
	foreach ( krv_extras() as $k => $x ) {
		$x['price'] = round( krv_entered_to_incl( $x['price'] ), 4 );
		$out[ $k ]  = $x;
	}
	return $out;
}
