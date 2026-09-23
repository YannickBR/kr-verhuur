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
						<div class="detail-media"><?php krt_product_media( get_the_ID(), 'large' ); ?></div>
						<div class="entry-content"><?php the_content(); ?></div>
						<?php if ( $p['features'] ) : ?>
							<ul class="feature-list">
								<?php foreach ( $p['features'] as $f ) : ?>
									<li><?php echo krt_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( $f ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
					<div class="card card-spaced">
						<h3>Tarieven</h3>
						<table class="price-table">
							<tr><td>Eerste dag</td><td><?php echo esc_html( krt_euro( $p['price_day'] ) ); ?></td></tr>
							<tr><td>Elke extra dag</td><td><?php echo esc_html( krt_euro( $p['price_extra_day'] ) ); ?></td></tr>
							<?php if ( $p['deposit'] > 0 ) : ?><tr><td>Borg</td><td><?php echo esc_html( krt_euro( $p['deposit'] ) ); ?></td></tr><?php endif; ?>
							<tr><td>Maximale huurperiode</td><td><?php echo (int) $p['max_days']; ?> dagen</td></tr>
						</table>
					</div>
				</div>
				<aside class="card booking" id="booking" aria-label="Reserveren">
					<noscript><p>Zet JavaScript aan om online te reserveren, of neem contact met ons op.</p></noscript>
				</aside>
			</div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
