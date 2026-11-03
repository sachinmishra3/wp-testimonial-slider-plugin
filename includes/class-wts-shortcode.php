<?php
/**
 * Shortcode and markup rendering.
 *
 * @package WP_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the testimonial slider.
 */
class WTS_Shortcode {

	const TAG = 'wp_testimonial_slider';

	/**
	 * Hook into WordPress.
	 */
	public function __construct() {
		add_shortcode( self::TAG, array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register (but do not enqueue) front-end assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'wts-slider', WTS_PLUGIN_URL . 'assets/css/wts-slider.css', array(), WTS_VERSION );
		wp_register_script( 'wts-slider', WTS_PLUGIN_URL . 'assets/js/wts-slider.js', array(), WTS_VERSION, true );
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'desktop'      => null,
				'tablet'       => null,
				'mobile'       => null,
				'autoplay'     => null,
				'loop'         => null,
				'arrows'       => null,
				'dots'         => null,
				'center_mode'  => null,
				'show_photo'   => null,
				'show_quote'   => null,
				'show_name'    => null,
				'show_role'    => null,
				'show_rating'  => null,
				'gap'          => null,
				'speed'        => null,
				'autoplay_speed' => null,
				'orderby'      => null,
				'limit'        => null,
				'category'     => null,
				'accent_color' => null,
				'text_color'   => null,
				'card_bg'      => null,
				'class'        => '',
			),
			$atts,
			self::TAG
		);

		$config = $this->build_config( $atts );

		return $this->render( $config, $atts['class'] );
	}

	/**
	 * Merge shortcode attributes over the saved settings.
	 *
	 * @param array $atts Raw shortcode attributes.
	 * @return array
	 */
	private function build_config( $atts ) {
		$config = WTS_Settings::get();

		$booleans = array( 'autoplay', 'loop', 'arrows', 'dots', 'center_mode', 'show_photo', 'show_quote', 'show_name', 'show_role', 'show_rating' );

		foreach ( $atts as $key => $value ) {
			if ( null === $value ) {
				continue;
			}

			if ( in_array( $key, $booleans, true ) ) {
				$config[ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? 1 : 0;
			} elseif ( in_array( $key, array( 'desktop', 'tablet', 'mobile', 'gap', 'speed', 'autoplay_speed', 'limit' ), true ) ) {
				$config[ $key ] = max( 0, (int) $value );
			} else {
				$config[ $key ] = $value;
			}
		}

		$config['desktop'] = max( 1, (int) $config['desktop'] );
		$config['tablet']  = max( 1, (int) $config['tablet'] );
		$config['mobile']  = max( 1, (int) $config['mobile'] );

		return $config;
	}

	/**
	 * Fetch testimonials for the given config.
	 *
	 * @param array $config Config.
	 * @return WP_Query
	 */
	private function query( $config ) {
		$args = array(
			'post_type'           => WTS_Post_Type::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $config['limit'] > 0 ? (int) $config['limit'] : -1,
			'orderby'             => $config['orderby'],
			'order'               => 'menu_order' === $config['orderby'] ? 'ASC' : 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( 'rand' === $config['orderby'] ) {
			$args['orderby'] = 'rand';
			$args['order']   = 'DESC';
		}

		if ( ! empty( $config['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => WTS_Post_Type::TAXONOMY,
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', explode( ',', $config['category'] ) ),
				),
			);
		}

		return new WP_Query( $args );
	}

	/**
	 * Render the slider markup.
	 *
	 * @param array  $config Config.
	 * @param string $extra_class Extra wrapper class.
	 * @return string
	 */
	public function render( $config, $extra_class = '' ) {
		$query = $this->query( $config );

		if ( ! $query->have_posts() ) {
			return '';
		}

		wp_enqueue_style( 'wts-slider' );
		wp_enqueue_script( 'wts-slider' );

		static $instance = 0;
		$instance++;
		$id = 'wts-slider-' . $instance;

		$style = sprintf(
			'--wts-accent:%1$s;--wts-text:%2$s;--wts-gap:%3$dpx;',
			esc_attr( $config['accent_color'] ),
			esc_attr( $config['text_color'] ),
			(int) $config['gap']
		);

		$data = array(
			'desktop'        => (int) $config['desktop'],
			'tablet'         => (int) $config['tablet'],
			'mobile'         => (int) $config['mobile'],
			'breakpointTablet' => (int) $config['breakpoint_tablet'],
			'breakpointMobile' => (int) $config['breakpoint_mobile'],
			'autoplay'       => (int) $config['autoplay'],
			'autoplaySpeed'  => (int) $config['autoplay_speed'],
			'speed'          => (int) $config['speed'],
			'loop'           => (int) $config['loop'],
			'arrows'         => (int) $config['arrows'],
			'dots'           => (int) $config['dots'],
			'centerMode'     => (int) $config['center_mode'],
			'equalHeight'    => (int) $config['equal_height'],
		);

		ob_start();
		?>
		<div class="wts-slider-wrap<?php echo $extra_class ? ' ' . esc_attr( trim( $extra_class ) ) : ''; ?>" id="<?php echo esc_attr( $id ); ?>" style="<?php echo esc_attr( $style ); ?>">
			<div class="wts-viewport" data-wts-config="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
				<div class="wts-track" role="list">
					<?php
					$index = 0;

					while ( $query->have_posts() ) :
						$query->the_post();
						$post_id = get_the_ID();
						?>
						<div class="wts-slide" role="listitem" data-index="<?php echo esc_attr( $index ); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
							<div class="wts-tile">
								<?php
								$role    = get_post_meta( $post_id, '_wts_role', true );
								$company = get_post_meta( $post_id, '_wts_company', true );
								$url     = get_post_meta( $post_id, '_wts_url', true );
								$rating  = (int) get_post_meta( $post_id, '_wts_rating', true );
								?>
								<?php if ( (int) $config['show_photo'] && has_post_thumbnail( $post_id ) ) : ?>
									<?php echo get_the_post_thumbnail( $post_id, 'large', array( 'alt' => esc_attr( get_the_title() ) ) ); ?>
								<?php else : ?>
									<?php echo $this->placeholder_avatar( get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
								<?php endif; ?>
							</div>

							<div class="wts-slide-content" hidden>
								<?php if ( (int) $config['show_quote'] ) : ?>
									<div class="wts-quote"><?php echo wp_kses_post( wpautop( get_the_content() ) ); ?></div>
								<?php endif; ?>

								<?php if ( (int) $config['show_name'] ) : ?>
									<span class="wts-name">
										<?php if ( $url ) : ?>
											<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( get_the_title() ); ?></a>
										<?php else : ?>
											<?php echo esc_html( get_the_title() ); ?>
										<?php endif; ?>
									</span>
								<?php endif; ?>

								<?php if ( (int) $config['show_role'] && ( $role || $company ) ) : ?>
									<span class="wts-role"><?php echo esc_html( implode( ', ', array_filter( array( $role, $company ) ) ) ); ?></span>
								<?php endif; ?>

								<?php if ( (int) $config['show_rating'] && $rating > 0 ) : ?>
									<div class="wts-rating" aria-label="<?php echo esc_attr( sprintf( __( '%d out of 5 stars', 'wp-testimonial-slider' ), $rating ) ); ?>">
										<?php
										for ( $i = 1; $i <= 5; $i++ ) {
											echo '<span class="wts-star' . ( $i <= $rating ? ' is-on' : '' ) . '">★</span>';
										}
										?>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<?php
						$index++;
					endwhile;
					wp_reset_postdata();
					?>
				</div>

				<?php if ( (int) $config['arrows'] ) : ?>
					<button type="button" class="wts-arrow wts-prev" aria-label="<?php esc_attr_e( 'Previous testimonial', 'wp-testimonial-slider' ); ?>">
						<span aria-hidden="true">&#8249;</span>
					</button>
					<button type="button" class="wts-arrow wts-next" aria-label="<?php esc_attr_e( 'Next testimonial', 'wp-testimonial-slider' ); ?>">
						<span aria-hidden="true">&#8250;</span>
					</button>
				<?php endif; ?>
			</div>

			<div class="wts-quote-panel" aria-live="polite"></div>

			<?php if ( (int) $config['dots'] ) : ?>
				<div class="wts-dots" role="tablist" aria-label="<?php esc_attr_e( 'Testimonial navigation', 'wp-testimonial-slider' ); ?>"></div>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Colored circle with the person's initials, used when there is no photo.
	 *
	 * @param string $name Person name.
	 * @return string
	 */
	private function placeholder_avatar( $name ) {
		$name     = trim( wp_strip_all_tags( $name ) );
		$parts    = preg_split( '/\s+/', $name );
		$initials = '';

		foreach ( (array) $parts as $part ) {
			if ( '' !== $part ) {
				$initials .= function_exists( 'mb_substr' ) ? mb_substr( $part, 0, 1 ) : substr( $part, 0, 1 );
			}

			if ( strlen( $initials ) >= 2 ) {
				break;
			}
		}

		$initials = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $initials ) : strtoupper( $initials );

		return '<span class="wts-avatar-placeholder" aria-hidden="true">' . esc_html( $initials ) . '</span>';
	}
}
