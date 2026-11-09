<?php
/**
 * Instellingen en extra opties (opgeslagen in wp_options).
 */

defined( 'ABSPATH' ) || exit;

function krv_default_settings() {
	return array(
		'company_name'    => 'KRverhuur',
		'email'           => get_option( 'admin_email' ),
		'notify_email'    => get_option( 'admin_email' ),
		'phone'           => '06 168 799 16',
		'address_street'  => 'Waterpas 2-9',
		'address_city'    => '8316 GJ  Marknesse',
		'region'          => '',
		'goboony_url'     => 'https://www.goboony.nl/',
		'min_lead_days'   => 1,
		'max_days_ahead'  => 365,
		'blocking_status' => array( 'pending', 'confirmed' ),
		'vat_rate'        => 21,
		'prices_incl_vat' => 1,      // Ingevoerde prijzen (artikelen, extra opties) zijn incl. btw.
		'display_vat'     => 'incl', // Standaardweergave op de website: 'incl' of 'excl' btw.
		'vat_toggle'      => 1,      // Bezoeker mag zelf wisselen tussen incl. en excl. btw.
		'terms_page'      => 0,      // Pagina met de algemene voorwaarden.
		'closed_weekdays' => array(), // Vaste gesloten dagen: 0 = zondag … 6 = zaterdag.
		'closed_dates'    => '',      // Losse gesloten dagen/periodes, één per regel.
		'closed_inside'   => 1,       // Huurperiode mag over een gesloten dag heen lopen.
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

/**
 * Link naar de algemene voorwaarden, alleen als de gekozen pagina gepubliceerd is.
 * Zolang er geen gepubliceerde pagina is, vraagt de winkelwagen ook geen akkoord.
 */
function krv_terms_url() {
	$id = (int) krv_setting( 'terms_page' );
	return $id && 'publish' === get_post_status( $id ) ? get_permalink( $id ) : '';
}

/* ---------------------------------------------------------------------------
 * Gesloten dagen: geen ophalen/bezorgen of terugbrengen
 * ------------------------------------------------------------------------- */

/** Vaste gesloten weekdagen (0 = zondag … 6 = zaterdag). */
function krv_closed_weekdays() {
	$days = krv_setting( 'closed_weekdays' );
	return array_values( array_unique( array_map( 'intval', array_intersect( (array) $days, range( 0, 6 ) ) ) ) );
}

/** Datum uit "2026-12-25" of "25-12-2026" (ook met / of .), of '' als ongeldig. */
function krv_parse_closed_date( $s ) {
	if ( preg_match( '/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $s, $m ) ) {
		$iso = sprintf( '%04d-%02d-%02d', $m[1], $m[2], $m[3] );
	} elseif ( preg_match( '/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $s, $m ) ) {
		$iso = sprintf( '%04d-%02d-%02d', $m[3], $m[2], $m[1] );
	} else {
		return '';
	}
	return krv_valid_date( $iso ) ? $iso : '';
}

/**
 * Losse gesloten dagen: [ 'YYYY-MM-DD' => true ]. Per regel één datum of een periode
 * (twee datums, bijv. "24-12-2026 t/m 26-12-2026"); tekst erachter mag (bijv. "Kerst").
 */
function krv_closed_dates() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	foreach ( preg_split( '/\r?\n/', (string) krv_setting( 'closed_dates' ) ) as $line ) {
		preg_match_all( '/\d{1,4}[-\/.]\d{1,2}[-\/.]\d{1,4}/', $line, $m );
		$dates = array_values( array_filter( array_map( 'krv_parse_closed_date', $m[0] ) ) );
		if ( ! $dates ) {
			continue;
		}
		$from = $dates[0];
		$to   = isset( $dates[1] ) ? $dates[1] : $from;
		if ( $to < $from ) {
			list( $from, $to ) = array( $to, $from );
		}
		foreach ( array_slice( krv_date_range( $from, $to ), 0, 400 ) as $d ) {
			$cache[ $d ] = true;
		}
	}
	return $cache;
}

/** Zijn we op deze dag gesloten? */
function krv_is_closed_day( $iso ) {
	if ( isset( krv_closed_dates()[ $iso ] ) ) {
		return true;
	}
	$ts = strtotime( $iso . ' 12:00:00 UTC' );
	return $ts && in_array( (int) gmdate( 'w', $ts ), krv_closed_weekdays(), true );
}

/** Gesloten dagen voor de kalender op de website. */
function krv_closed_for_js() {
	$today = krv_today();
	$max   = wp_date( 'Y-m-d', strtotime( '+' . ( (int) krv_setting( 'max_days_ahead' ) + 1 ) . ' days' ) );
	$dates = array();
	foreach ( array_keys( krv_closed_dates() ) as $d ) {
		if ( $d >= $today && $d <= $max ) {
			$dates[] = $d;
		}
	}
	return array(
		'weekdays' => krv_closed_weekdays(),
		'dates'    => $dates,
		'inside'   => krv_setting( 'closed_inside' ) ? 1 : 0,
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

/** Naam van de cookie waarin de keuze van de bezoeker (incl/excl) staat. */
define( 'KRV_VAT_COOKIE', 'krv_vat' );

/** Standaardweergave volgens de instellingen: 'incl' of 'excl'. */
function krv_vat_default_mode() {
	return 'excl' === krv_setting( 'display_vat' ) ? 'excl' : 'incl';
}

/** Mag de bezoeker zelf wisselen? */
function krv_vat_toggle_enabled() {
	return (bool) krv_setting( 'vat_toggle' );
}

/**
 * Huidige weergave: de keuze van de bezoeker (cookie) als wisselen is toegestaan,
 * anders de standaard. In wp-admin geldt altijd de standaard.
 */
function krv_vat_mode() {
	if ( krv_vat_toggle_enabled() && ! is_admin() && isset( $_COOKIE[ KRV_VAT_COOKIE ] ) ) {
		$c = sanitize_key( wp_unslash( $_COOKIE[ KRV_VAT_COOKIE ] ) );
		if ( in_array( $c, array( 'incl', 'excl' ), true ) ) {
			return $c;
		}
	}
	return krv_vat_default_mode();
}

/** Worden prijzen nu exclusief btw getoond? */
function krv_show_excl_vat() {
	return 'excl' === krv_vat_mode();
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
