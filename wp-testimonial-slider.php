<?php
/**
 * Plugin Name:       WP Testimonial Slider
 * Plugin URI:        https://github.com/sachinmishra3/wp-testimonial-slider-plugin
 * Description:       A responsive testimonial slider with a highlighted active slide. Shows 5-6 slides on desktop, 3-4 on tablet and 1-2 on mobile. Includes a settings page and a shortcode.
 * Version:           1.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Sachin Mishra
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-testimonial-slider
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WTS_VERSION', '1.0.0' );
define( 'WTS_PLUGIN_FILE', __FILE__ );
define( 'WTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WTS_PLUGIN_DIR . 'includes/class-wts-plugin.php';

/**
 * Boot the plugin.
 *
 * @return \WTS_Plugin
 */
function wts_plugin() {
	return \WTS_Plugin::instance();
}

wts_plugin();

register_activation_hook( __FILE__, array( 'WTS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WTS_Plugin', 'deactivate' ) );
