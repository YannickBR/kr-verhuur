<?php
/**
 * Homepage.
 */

defined( 'ABSPATH' ) || exit;
get_header();
$phone  = krt_setting( 'phone' );
$email  = krt_setting( 'email', get_option( 'admin_email' ) );
$region = krt_setting( 'region' );
?>
<section class="hero">
	<div class="container">
		<span class="eyebrow eyebrow-light">Verhuur voor feest, evenement &amp; klus</span>
		<h1>Voor als je het <span>zelf</span> niet hebt!</h1>
		<p class="lead">Van photobooth tot trilplaat: bij KR Verhuur huur je het eenvoudig voor één of meerdere dagen. Kies je datum in de kalender en reserveer direct online.</p>
		<div class="hero-actions">
			<a class="btn btn-primary" href="<?php echo esc_url( krt_catalog_url() ); ?>">Bekijk het assortiment</a>
			<a class="btn btn-ghost" href="#hoe-werkt-het">Hoe werkt het?</a>
		</div>
		<ul class="hero-usps">
			<?php foreach ( array( 'Online reserveren via de kalender', '1 of meerdere dagen huren', 'Halen & brengen mogelijk' ) as $usp ) : ?>
				<li><?php echo krt_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( $usp ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<div class="hero-mark"><?php echo krt_mark(); // phpcs:ignore ?></div>
</section>

<?php if ( krt_has_plugin() ) : ?>
<section class="overlap" id="huurgroepen">
	<div class="container">
		<div class="group-grid">
			<?php foreach ( krv_get_groups() as $g ) { krt_group_card( $g ); } ?>
		</div>
	</div>
</section>
<?php endif; ?>


<section class="section" id="hoe-werkt-het">
	<div class="container">
		<div class="section-head">
			<div>
				<span class="eyebrow">Zo simpel is het</span>
				<h2>In 4 stappen geregeld</h2>
			</div>
			<p>Geen gedoe met bellen en mailen: je ziet direct wat beschikbaar is en wat het kost.</p>
		</div>
		<div class="steps">
			<div class="step"><h3>Kies je product</h3><p>Blader door onze huurgroepen en kies wat je nodig hebt.</p></div>
			<div class="step"><h3>Selecteer je dagen</h3><p>Kies in de kalender één dag of een hele periode. Bezette dagen zie je meteen.</p></div>
			<div class="step"><h3>Kies extra's</h3><p>Halen en brengen, opbouwen of schoonmaak? Vink het aan en zie direct de totaalprijs.</p></div>
			<div class="step"><h3>Klaar!</h3><p>Verstuur je aanvraag. Wij bevestigen je reservering zo snel mogelijk.</p></div>
		</div>
	</div>
</section>

<section class="section section-tight" id="contact">
	<div class="container">
		<div class="cta-band">
			<div>
				<h2>Vragen of een offerte op maat?</h2>
				<p>Neem gerust contact met ons op, we denken graag met je mee.</p>
			</div>
			<a class="btn" href="<?php echo esc_url( krt_catalog_url() ); ?>">Direct reserveren</a>
		</div>
		<div class="card contact-card">
			<div class="contact-grid">
				<?php if ( $phone ) : ?>
					<div class="contact-item"><?php echo krt_icon( 'phone' ); // phpcs:ignore ?><div>Bel of app ons<br><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></div></div>
				<?php endif; ?>
				<div class="contact-item"><?php echo krt_icon( 'mail' ); // phpcs:ignore ?><div>Mail ons<br><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></div></div>
				<?php if ( $region ) : ?>
					<div class="contact-item"><?php echo krt_icon( 'pin' ); // phpcs:ignore ?><div>Werkgebied<br><strong><?php echo esc_html( $region ); ?></strong></div></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
<?php
get_footer();
