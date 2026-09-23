<?php
/**
 * Winkelwagen en bestelling plaatsen. De inhoud wordt opgebouwd door assets/js/cart-page.js.
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="page-hero">
	<div class="container">
		<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> / Winkelwagen</div>
		<h1>Winkelwagen</h1>
		<p>Controleer je artikelen en plaats je bestelling in één keer.</p>
	</div>
</section>
<section class="overlap">
	<div class="container" id="cart-root">
		<div class="card empty-state"><p>Winkelwagen laden…</p>
			<noscript><p>Zet JavaScript aan om te bestellen, of neem contact met ons op.</p></noscript>
		</div>
	</div>
</section>
<?php
get_footer();
