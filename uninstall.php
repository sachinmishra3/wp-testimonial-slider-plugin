<?php
/**
 * Runs when the plugin is uninstalled.
 *
 * Removes the plugin settings. Testimonials themselves are left intact so a
 * site owner does not lose content by deleting the plugin.
 *
 * @package WP_Testimonial_Slider
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wts_settings' );

// Multisite: remove the option from every site.
if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $sites as $site_id ) {
		switch_to_blog( $site_id );
		delete_option( 'wts_settings' );
		restore_current_blog();
	}
}
