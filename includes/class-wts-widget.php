<?php
/**
 * Testimonial slider widget.
 *
 * @package WP_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets site owners drop the slider into any widget area.
 */
class WTS_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'wts_testimonial_slider',
			__( 'Testimonial Slider', 'wp-testimonial-slider' ),
			array( 'description' => __( 'Displays your testimonials in a responsive slider.', 'wp-testimonial-slider' ) )
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Widget args.
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		$plugin = wts_plugin();
		echo $plugin->shortcode->render( WTS_Settings::get(), 'wts-in-widget' ); // phpcs:ignore WordPress.Security.EscapeOutput

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Admin form.
	 *
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'wp-testimonial-slider' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p class="description"><?php esc_html_e( 'Slider layout is controlled from Testimonials &rarr; Slider Settings.', 'wp-testimonial-slider' ); ?></p>
		<?php
	}

	/**
	 * Save widget settings.
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
		);
	}
}
