<?php
/**
 * Main plugin bootstrap.
 *
 * @package WP_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once WTS_PLUGIN_DIR . 'includes/class-wts-post-type.php';
require_once WTS_PLUGIN_DIR . 'includes/class-wts-settings.php';
require_once WTS_PLUGIN_DIR . 'includes/class-wts-shortcode.php';
require_once WTS_PLUGIN_DIR . 'includes/class-wts-widget.php';

/**
 * Wires the plugin components together.
 */
final class WTS_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var WTS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Testimonial post type handler.
	 *
	 * @var WTS_Post_Type
	 */
	public $post_type;

	/**
	 * Settings handler.
	 *
	 * @var WTS_Settings
	 */
	public $settings;

	/**
	 * Shortcode handler.
	 *
	 * @var WTS_Shortcode
	 */
	public $shortcode;

	/**
	 * Get the singleton instance.
	 *
	 * @return WTS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->post_type = new WTS_Post_Type();
		$this->settings  = new WTS_Settings();
		$this->shortcode = new WTS_Shortcode();

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wp-testimonial-slider', false, dirname( plugin_basename( WTS_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Register the widget.
	 *
	 * @return void
	 */
	public function register_widget() {
		register_widget( 'WTS_Widget' );
	}

	/**
	 * Activation callback.
	 *
	 * Registers the post type once so rewrite rules are flushed with it.
	 *
	 * @return void
	 */
	public static function activate() {
		$post_type = new WTS_Post_Type();
		$post_type->register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
