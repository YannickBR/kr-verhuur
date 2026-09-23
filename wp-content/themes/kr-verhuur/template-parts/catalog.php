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
		<h1><?php echo esc_html( $current ? $current->name . ' huren' : 'Ons assortiment' ); ?></h1>
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
	</div>
</section>
