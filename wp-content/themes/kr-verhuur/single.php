<?php
/**
 * Pagina's (bijv. over ons, voorwaarden).
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<section class="page-hero">
		<div class="container"><h1><?php the_title(); ?></h1></div>
	</section>
	<section class="overlap">
		<div class="container">
			<article class="card entry-content"><?php the_content(); ?></article>
		</div>
	</section>
	<?php
endwhile;
get_footer();
