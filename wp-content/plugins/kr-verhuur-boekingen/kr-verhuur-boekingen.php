<?php
/**
 * Plugin Name:       KR Verhuur – Boekingen
 * Description:       Huurartikelen, huurgroepen, reserveringskalender en boekingsbeheer voor KR Verhuur.
 * Version:           1.1.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            KR Verhuur
 * Text Domain:       kr-verhuur
 */

defined( 'ABSPATH' ) || exit;

define( 'KRV_VERSION', '1.1.0' );
define( 'KRV_FILE', __FILE__ );
define( 'KRV_DIR', plugin_dir_path( __FILE__ ) );
define( 'KRV_URL', plugin_dir_url( __FILE__ ) );

require_once KRV_DIR . 'includes/helpers.php';
require_once KRV_DIR . 'includes/settings.php';
require_once KRV_DIR . 'includes/post-types.php';
require_once KRV_DIR . 'includes/pricing.php';
require_once KRV_DIR . 'includes/availability.php';
require_once KRV_DIR . 'includes/bookings.php';
require_once KRV_DIR . 'includes/emails.php';
require_once KRV_DIR . 'includes/rest-api.php';
require_once KRV_DIR . 'includes/install.php';

if ( is_admin() ) {
	require_once KRV_DIR . 'includes/admin-products.php';
	require_once KRV_DIR . 'includes/admin-bookings.php';
	require_once KRV_DIR . 'includes/admin-planning.php';
	require_once KRV_DIR . 'includes/admin-settings.php';
}

register_activation_hook( __FILE__, 'krv_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
