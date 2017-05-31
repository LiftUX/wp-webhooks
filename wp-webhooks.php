<?php
/**
 * Plugin Name:     WordPress Webhooks
 * Plugin URI:      https://liftux.com
 * Description:     Provides an infrastructure to register webhooks to send/receive.
 * Author:          Christian Chung
 * Author URI:      https://liftux.com
 * Text Domain:     wp-webhooks
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Lift\WP_Webhooks
 */

namespace Lift\WP_Webhooks;

// Autoload via composer if possible, fallback to requires.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
} else {
	require_once plugin_dir_path( __FILE__ ) . 'src/classes/class-wp-webhooks.php';
	require_once plugin_dir_path( __FILE__ ) . 'src/classes/integrations/class-wp-webhooks-route.php';
}
do_action( 'wp_webhooks_did_autoload' );

// Define plugin locations.
define( 'WP_WEBHOOKS_BASE_DIR', dirname( __FILE__ ) );
define( 'WP_WEBHOOKS_BASE_URI', plugin_dir_url( __FILE__ ) );

/**
 * Ready
 *
 * @return void
 */
function ready() {
	try {
		$instance = WP_Webhooks::factory()->setup();
	} catch ( \Exception $exception ) {
		wp_die( esc_html( $exception->getMessage() ) );
	}
	do_action( 'webhooks_ready' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\ready', 100 );
