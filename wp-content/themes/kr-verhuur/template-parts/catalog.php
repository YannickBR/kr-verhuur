<?php
/**
 * Assortiment: alle huurartikelen of één huurgroep.
 */

defined( 'ABSPATH' ) || exit;

$current = is_tax( 'kr_group' ) ? get_queried_object() : null;
$meta    = $current ? krv_group_meta( $current ) : null;
?>
<section class="page-hero">
	<div class="container">
		<div class="crumbs">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> /
			<?php if ( $current ) : ?>
				<a href="<?php echo esc_url( krt_catalog_url() ); ?>">Assortiment</a> / <?php echo esc_html( $current->name ); ?>
			<?php else : ?>
				Assortiment
			<?php endif; ?>
		</div>
		<h1><?php echo esc_html( $current ? krv_with_huren( $current->name ) : 'Ons assortiment' ); ?></h1>
		<p><?php echo esc_html( $current ? $meta['tagline'] . ' Kies een product, selecteer je huurdagen en reserveer direct.' : 'Kies een huurgroep of product, selecteer je huurdagen en reserveer direct online.' ); ?></p>
	</div>
</section>

<section class="overlap">
	<div class="container">
		<div class="card filter-card">
			<nav class="filter-bar" aria-label="Huurgroepen">
				<a class="chip<?php echo $current ? '' : ' active'; ?>" href="<?php echo esc_url( krt_catalog_url() ); ?>">Alles</a>
				<?php foreach ( krv_get_groups() as $g ) : ?>
					<?php
					$gm  = krv_group_meta( $g );
					$ext = ! empty( $gm['external_url'] );
					?>
					<a class="chip<?php echo $current && $current->term_id === $g->term_id ? ' active' : ''; ?>" href="<?php echo esc_url( krv_group_url( $g ) ); ?>"<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php echo krt_icon( $gm['icon'] ); // phpcs:ignore ?><?php echo esc_html( $g->name ); ?><?php echo $ext ? ' ' . krt_icon( 'external' ) : ''; // phpcs:ignore ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>

		<?php if ( have_posts() ) : ?>
			<div class="product-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					krt_product_card( get_the_ID() );
				endwhile;
				?>
			</div>
		<?php else : ?>
			<div class="card empty-state">
				<h2>Nog geen artikelen</h2>
				<p>In deze groep staan nog geen artikelen. Neem gerust contact met ons op!</p>
			</div>
		<?php endif; ?>

		<?php
		// Tekst voor bezoekers en Google: eigen beschrijving van de huurgroep, anders een standaardtekst.
		$places  = krv_list_text( krv_seo_top_places( 3 ) );
		$heading = $current ? krv_with_huren( $current->name ) . ' in de Noordoostpolder' : 'Huren in de Noordoostpolder en omgeving';
		$text    = $current ? trim( term_description( $current ) ) : '';
		?>
		<div class="card seo-text">
			<h2><?php echo esc_html( $heading ); ?></h2>
			<?php if ( $text ) : ?>
				<?php echo wp_kses_post( wpautop( $text ) ); ?>
			<?php elseif ( $current ) : ?>
				<p><?php echo esc_html( krv_with_huren( $current->name ) ); ?> doe je eenvoudig online bij <?php echo esc_html( krv_company() ); ?> in Marknesse. Kies het artikel dat bij je feest, evenement of klus past, selecteer één of meerdere huurdagen in de kalender en reserveer direct. Wij bezorgen en halen op in <?php echo esc_html( $places ); ?> en de rest van de Noordoostpolder, Flevoland en de kop van Overijssel. Ophalen bij ons in Marknesse kan ook.</p>
			<?php else : ?>
				<p>Bij <?php echo esc_html( krv_company() ); ?> in Marknesse huur je alles voor je feest, evenement of klus: van photobooth en springkussen tot toiletwagen, statafels, terrasverwarming, koeling en gereedschap. Reserveer online voor één of meerdere dagen en laat het bezorgen in <?php echo esc_html( $places ); ?> of elders in de Noordoostpolder, Flevoland en Overijssel.</p>
			<?php endif; ?>
			<?php krt_service_area_links( $current ? krv_with_huren( $current->name ) . ' in de buurt' : 'Wij bezorgen in' ); ?>
		</div>
	</div>
</section>
