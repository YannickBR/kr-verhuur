<?php defined( 'ABSPATH' ) || exit; ?>
</main>
<footer class="site-footer">
	<div class="container">
		<div class="footer-grid">
			<div class="footer-brand">
				<?php echo krt_logo( 'light' ); // phpcs:ignore ?>
				<p>Huur alles voor je feest, evenement of klus. Eenvoudig online reserveren, wij regelen de rest.</p>
			</div>
			<div>
				<h4>Huurgroepen</h4>
				<ul>
					<?php if ( krt_has_plugin() ) : ?>
						<?php foreach ( krv_get_groups() as $g ) : ?>
							<?php $url = krv_group_url( $g ); ?>
							<li><a href="<?php echo esc_url( $url ); ?>"<?php echo krt_is_external( $url ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $g->name ); ?></a></li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</div>
			<div>
				<h4>Contact</h4>
				<ul>
					<?php $phone = krt_setting( 'phone' ); $email = krt_setting( 'email', get_option( 'admin_email' ) ); $region = krt_setting( 'region' ); ?>
					<?php if ( $phone ) : ?><li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
					<li><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
					<?php if ( $region ) : ?><li><?php echo esc_html( $region ); ?></li><?php endif; ?>
				</ul>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'fallback_cb'    => false,
						'depth'          => 1,
						'menu_class'     => 'footer-menu',
					)
				);
				?>
			</div>
		</div>
		<div class="footer-bottom">
			<span>&copy; <?php echo esc_html( wp_date( 'Y' ) . ' ' . krt_setting( 'company_name', get_bloginfo( 'name' ) ) ); ?></span>
			<span>Alle prijzen incl. btw</span>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
