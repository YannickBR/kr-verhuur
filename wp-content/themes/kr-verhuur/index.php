<?php
/**
 * Standaard template (blog, zoekresultaten).
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="page-hero">
	<div class="container">
		<h1><?php echo esc_html( is_search() ? 'Zoekresultaten' : ( is_home() ? get_the_title( get_option( 'page_for_posts' ) ) : get_the_archive_title() ) ); ?></h1>
	</div>
</section>
<section class="overlap">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article class="card card-list">
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<div class="card empty-state"><p>Niets gevonden.</p></div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
