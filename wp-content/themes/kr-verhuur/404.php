<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="page-hero">
	<div class="container"><h1>Pagina niet gevonden</h1><p>Deze pagina bestaat niet (meer).</p></div>
</section>
<section class="overlap">
	<div class="container">
		<div class="card empty-state">
			<p><a class="btn btn-primary" href="<?php echo esc_url( krt_catalog_url() ); ?>">Naar het assortiment</a></p>
		</div>
	</div>
</section>
<?php
get_footer();
