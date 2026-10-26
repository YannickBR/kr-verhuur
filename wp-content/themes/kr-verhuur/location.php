<?php
/**
 * Werkgebied: overzicht (/verhuur/) en een pagina per plaats (/verhuur/<plaats>/).
 * Teksten zijn per plaats aan te vullen via Huurartikelen → Werkgebied (SEO).
 */

defined( 'ABSPATH' ) || exit;

$slug    = krv_current_location_slug();
$company = krv_company();
get_header();

if ( 'overzicht' === $slug ) :
	$by_prov = array();
	foreach ( krv_locations() as $s => $l ) {
		$by_prov[ $l['provincie'] ][ $s ] = $l;
	}
	?>
	<section class="page-hero">
		<div class="container">
			<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / Werkgebied</div>
			<h1>Ons werkgebied</h1>
			<p><?php echo esc_html( $company ); ?> zit in Marknesse en bezorgt in de hele Noordoostpolder, Urk, Flevoland, de kop van Overijssel en Lemmer.</p>
		</div>
	</section>
	<section class="overlap">
		<div class="container">
			<div class="card seo-text">
				<h2>Verhuur in de Noordoostpolder en omgeving</h2>
				<p>Van een photobooth voor een bruiloft in Emmeloord tot een toiletwagen voor een evenement in Urk of terrasverwarming voor een feest in Vollenhove: wij brengen het naar je toe en halen het weer op. Kies hieronder je woonplaats voor meer informatie over bezorgen bij jou in de buurt, of <a href="<?php echo esc_url( krt_catalog_url() ); ?>">bekijk direct het assortiment</a>.</p>
			</div>
			<?php foreach ( $by_prov as $prov => $locs ) : ?>
				<h2 class="area-prov"><?php echo esc_html( $prov ); ?></h2>
				<div class="area-grid">
					<?php foreach ( $locs as $s => $l ) : ?>
						<a class="area-card" href="<?php echo esc_url( krv_location_url( $s ) ); ?>">
							<span class="area-card-icon"><?php echo krt_icon( 'pin' ); // phpcs:ignore ?></span>
							<strong>Verhuur <?php echo esc_html( $l['name'] ); ?></strong>
							<small>Gemeente <?php echo esc_html( $l['gemeente'] ); ?><?php echo $l['km'] > 0 ? ' · ca. ' . (int) $l['km'] . ' km' : ' · onze thuisbasis'; ?></small>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
else :
	$l      = krv_location( $slug );
	$name   = $l['name'];
	$home   = 'marknesse' === $slug;
	$near   = krv_nearby_locations( $slug, 6 );
	$groups = array_filter(
		krv_get_groups(),
		function ( $g ) {
			return $g->count > 0;
		}
	);
	// Variatie in de teksten per plaats (vast per plaats, dus geen wisselende inhoud).
	$v       = abs( crc32( $slug ) );
	$events  = array(
		array( 'bruiloft', 'verjaardag', 'buurtfeest' ),
		array( 'tuinfeest', 'jubileum', 'bedrijfsuitje' ),
		array( 'dorpsfeest', 'kinderfeest', 'open dag' ),
		array( 'bruiloft', 'sportdag', 'familiedag' ),
	);
	$ev      = $events[ $v % count( $events ) ];
	$popular = get_posts(
		array(
			'post_type'   => 'kr_product',
			'numberposts' => 6,
			'meta_key'    => '_thumbnail_id',
			'orderby'     => 'menu_order title',
			'order'       => 'ASC',
		)
	);
	?>
	<section class="page-hero">
		<div class="container">
			<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / <a href="<?php echo esc_url( krv_service_area_url() ); ?>">Werkgebied</a> / <?php echo esc_html( $name ); ?></div>
			<h1>Verhuur in <?php echo esc_html( $name ); ?></h1>
			<p>Photobooth, springkussen, toiletwagen, meubilair, verwarming en gereedschap huren in <?php echo esc_html( $name ); ?>.
				<?php echo $home ? 'Hier zit ons bedrijf: ophalen kan direct om de hoek.' : 'Wij bezorgen en halen op – ca. ' . (int) $l['km'] . ' km vanaf onze locatie in Marknesse.'; ?></p>
			<div class="hero-actions" style="margin-top:18px">
				<a class="btn btn-primary" href="<?php echo esc_url( krt_catalog_url() ); ?>">Bekijk het assortiment</a>
			</div>
		</div>
	</section>

	<section class="overlap">
		<div class="container">
			<div class="card seo-text">
				<h2>Huren in <?php echo esc_html( $name ); ?> en omgeving</h2>
				<?php if ( $l['intro'] ) : ?>
					<?php echo wp_kses_post( wpautop( esc_html( $l['intro'] ) ) ); ?>
				<?php endif; ?>
				<p>Organiseer je een <?php echo esc_html( $ev[0] ); ?>, <?php echo esc_html( $ev[1] ); ?> of <?php echo esc_html( $ev[2] ); ?> in <?php echo esc_html( $name ); ?>? Of ga je aan de slag in huis en tuin? Bij <?php echo esc_html( $company ); ?> huur je eenvoudig online wat je nodig hebt, voor één dag of langer. Je ziet direct wat er beschikbaar is en wat het kost.</p>
				<?php if ( $home ) : ?>
					<p>Ons bedrijf zit aan de <?php echo esc_html( krv_setting( 'address_street' ) ?: 'Waterpas' ); ?> in Marknesse. Je kunt je huurartikelen hier zelf ophalen en terugbrengen, of je kiest voor halen en brengen: dan zetten wij alles voor je klaar op je feestlocatie.</p>
				<?php else : ?>
					<p><?php echo esc_html( $name ); ?> ligt in de gemeente <?php echo esc_html( $l['gemeente'] ); ?> (<?php echo esc_html( $l['provincie'] ); ?>), op ongeveer <?php echo (int) $l['km']; ?> kilometer van onze locatie in Marknesse. Kies bij het reserveren voor <strong>halen en brengen</strong>, dan bezorgen wij alles in <?php echo esc_html( $name ); ?> en halen het na afloop weer op. Zelf ophalen in Marknesse kan natuurlijk ook.</p>
				<?php endif; ?>
			</div>

			<div class="section-head area-head">
				<div>
					<span class="eyebrow">Ons assortiment</span>
					<h2>Wat kun je huren in <?php echo esc_html( $name ); ?>?</h2>
				</div>
			</div>
			<div class="group-grid">
				<?php foreach ( $groups as $g ) { krt_group_card( $g ); } ?>
			</div>

			<?php if ( $popular ) : ?>
				<div class="section-head area-head">
					<div>
						<span class="eyebrow">Populair in <?php echo esc_html( $name ); ?></span>
						<h2>Direct reserveren</h2>
					</div>
				</div>
				<div class="product-grid">
					<?php foreach ( $popular as $prod ) { krt_product_card( $prod->ID ); } ?>
				</div>
			<?php endif; ?>

			<div class="card seo-text card-spaced">
				<h2>Veelgestelde vragen over huren in <?php echo esc_html( $name ); ?></h2>
				<div class="faq">
					<?php foreach ( krv_location_faq( $l ) as $f ) : ?>
						<details>
							<summary><?php echo esc_html( $f['q'] ); ?></summary>
							<p><?php echo esc_html( $f['a'] ); ?></p>
						</details>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $near ) : ?>
				<div class="card seo-text card-spaced">
					<div class="area-links">
						<h3>Ook actief in de buurt van <?php echo esc_html( $name ); ?></h3>
						<ul>
							<?php foreach ( $near as $n ) : ?>
								<li><a href="<?php echo esc_url( krv_location_url( $n['slug'] ) ); ?>"><?php echo krt_icon( 'pin' ); // phpcs:ignore ?>Verhuur <?php echo esc_html( $n['name'] ); ?></a></li>
							<?php endforeach; ?>
							<li><a class="area-all" href="<?php echo esc_url( krv_service_area_url() ); ?>">Hele werkgebied <?php echo krt_icon( 'arrow' ); // phpcs:ignore ?></a></li>
						</ul>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
endif;

get_footer();
