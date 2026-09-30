<?php
/**
 * Huurartikel met reserveringskalender.
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	$p     = krv_get_product( get_the_ID() );
	$group = $p['group'];
	$gmeta = $group ? krv_group_meta( $group ) : null;
	?>
	<section class="page-hero">
		<div class="container">
			<div class="crumbs">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> /
				<a href="<?php echo esc_url( krt_catalog_url() ); ?>">Assortiment</a> /
				<?php if ( $group ) : ?><a href="<?php echo esc_url( krv_group_url( $group ) ); ?>"><?php echo esc_html( $group->name ); ?></a> /<?php endif; ?>
				<?php the_title(); ?>
			</div>
			<h1><?php the_title(); ?></h1>
			<?php if ( $gmeta ) : ?><p><?php echo esc_html( $gmeta['tagline'] ); ?></p><?php endif; ?>
		</div>
	</section>

	<section class="overlap">
		<div class="container">
			<div class="detail">
				<div>
					<div class="card">
						<div class="detail-media"><?php krt_product_gallery( get_the_ID() ); ?></div>
						<div class="entry-content"><?php the_content(); ?></div>
						<?php if ( $p['features'] ) : ?>
							<ul class="feature-list">
								<?php foreach ( $p['features'] as $f ) : ?>
									<li><?php echo krt_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( $f ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
					<?php if ( ! $p['external_url'] || $p['price_day'] > 0 ) : ?>
					<div class="card card-spaced">
						<h3><?php echo $p['is_sale'] ? 'Prijs' : 'Tarieven'; ?> <small class="vat-note">(<?php echo krt_vat_label(); // phpcs:ignore ?>)</small></h3>
						<table class="price-table">
							<?php if ( $p['is_sale'] ) : ?>
								<tr><td>Prijs per <?php echo esc_html( $p['unit'] ); ?></td><td><?php echo krt_price( $p['price_day'] ); // phpcs:ignore ?></td></tr>
								<tr><td>Maximaal per bestelling</td><td><?php echo (int) $p['stock']; ?></td></tr>
							<?php else : ?>
								<tr><td><?php echo $p['external_url'] ? 'Vanaf, per dag' : 'Eerste dag'; ?></td><td><?php echo krt_price( $p['price_day'] ); // phpcs:ignore ?></td></tr>
								<?php if ( ! $p['external_url'] ) : ?>
									<tr><td>Elke extra dag</td><td><?php echo krt_price( $p['price_extra_day'] ); // phpcs:ignore ?></td></tr>
									<?php if ( $p['deposit'] > 0 ) : ?><tr><td>Borg</td><td><?php echo esc_html( krt_euro( $p['deposit'] ) ); ?></td></tr><?php endif; ?>
									<tr><td>Maximale huurperiode</td><td><?php echo (int) $p['max_days']; ?> dagen</td></tr>
								<?php endif; ?>
							<?php endif; ?>
						</table>
					</div>
					<?php endif; ?>
				</div>
				<?php if ( $p['external_url'] ) : ?>
					<?php $host = preg_replace( '/^www\./', '', (string) wp_parse_url( $p['external_url'], PHP_URL_HOST ) ); ?>
					<aside class="card booking booking-external" aria-label="Reserveren">
						<h2><?php echo krt_icon( 'calendar' ); // phpcs:ignore ?>Reserveren</h2>
						<p><?php the_title(); ?> reserveer je via <strong><?php echo esc_html( ucfirst( strtok( $host, '.' ) ) ); ?></strong>. Daar zie je direct de beschikbaarheid en de prijs, en rond je de boeking af.</p>
						<a class="btn btn-primary btn-block" href="<?php echo esc_url( $p['external_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $p['external_label'] ); ?> <?php echo krt_icon( 'external' ); // phpcs:ignore ?></a>
						<?php $phone = krt_setting( 'phone' ); ?>
						<?php if ( $phone ) : ?>
							<p class="summary-note">Vragen? Bel ons op <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>.</p>
						<?php endif; ?>
					</aside>
				<?php else : ?>
					<aside class="card booking" id="booking" aria-label="<?php echo $p['is_sale'] ? 'Bestellen' : 'Reserveren'; ?>">
						<noscript><p>Zet JavaScript aan om online te bestellen, of neem contact met ons op.</p></noscript>
					</aside>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
