<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="sr-only" href="#main">Naar de inhoud</a>
<header class="site-header">
	<div class="container">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> – home">
			<?php echo krt_logo( 'light' ); // phpcs:ignore ?>
		</a>
		<a class="cart-link" href="<?php echo esc_url( krt_cart_url() ); ?>" aria-label="Winkelwagen">
			<?php echo krt_icon( 'cart' ); // phpcs:ignore ?>
			<span class="cart-count" data-cart-count hidden>0</span>
		</a>
		<button class="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="site-nav">
			<?php echo krt_icon( 'menu', 'i-open' ) . krt_icon( 'close', 'i-close' ); // phpcs:ignore ?>
		</button>
		<nav class="nav" id="site-nav" aria-label="Hoofdmenu">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => 'krt_default_menu',
					'depth'          => 1,
				)
			);
			?>
			<a class="btn btn-primary" href="<?php echo esc_url( krt_catalog_url() ); ?>"><?php echo krt_icon( 'calendar' ); // phpcs:ignore ?>Direct reserveren</a>
		</nav>
	</div>
</header>
<main id="main">
