<?php
/**
 * Admin settings page.
 *
 * @package WP_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin settings screen.
 */
class WTS_Settings {

	const OPTION = 'wts_settings';
	const PAGE   = 'wts-settings';

	/**
	 * Hook into admin.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_wts_import_samples', array( $this, 'handle_import' ) );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WTS_PLUGIN_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Import a handful of demo testimonials so the slider can be seen instantly.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'wp-testimonial-slider' ) );
		}

		check_admin_referer( 'wts_import_samples' );

		$samples = array(
			array(
				'name'    => 'Amelia Clarke',
				'role'    => 'Marketing Director',
				'company' => 'Northwind Co.',
				'rating'  => 5,
				'quote'   => 'The new site completely changed how customers find us. Traffic is up and the team finally enjoys updating the pages.',
			),
			array(
				'name'    => 'Daniel Okafor',
				'role'    => 'Founder',
				'company' => 'Bright Loop',
				'rating'  => 5,
				'quote'   => 'Fast, clean and reliable. Our checkout drop-off fell by a third in the first month after launch.',
			),
			array(
				'name'    => 'Priya Nair',
				'role'    => 'Operations Lead',
				'company' => 'Kite Logistics',
				'rating'  => 4,
				'quote'   => 'Support was quick every time we asked. The dashboard they built saves our team hours of manual reporting.',
			),
			array(
				'name'    => 'Lucas Meyer',
				'role'    => 'Product Manager',
				'company' => 'Fern Studio',
				'rating'  => 5,
				'quote'   => 'They understood the product roadmap and shipped on schedule. It felt like an extension of our own team.',
			),
			array(
				'name'    => 'Sofia Rossi',
				'role'    => 'Owner',
				'company' => 'Casa Verde',
				'rating'  => 5,
				'quote'   => 'Bookings are smoother and the site looks beautiful on every phone. Exactly what we were hoping for.',
			),
			array(
				'name'    => 'Ethan Brooks',
				'role'    => 'CTO',
				'company' => 'Marrow Labs',
				'rating'  => 4,
				'quote'   => 'Solid engineering and honest advice. They fixed performance issues we had struggled with for months.',
			),
		);

		$created = 0;

		foreach ( $samples as $sample ) {
			$post_id = wp_insert_post(
				array(
					'post_type'    => WTS_Post_Type::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => $sample['name'],
					'post_content' => $sample['quote'],
					'menu_order'   => $created,
				)
			);

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_wts_role', $sample['role'] );
				update_post_meta( $post_id, '_wts_company', $sample['company'] );
				update_post_meta( $post_id, '_wts_rating', $sample['rating'] );
				$created++;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'  => WTS_Post_Type::POST_TYPE,
					'page'       => self::PAGE,
					'wts_notice' => 'imported',
					'wts_count'  => $created,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Show the import result notice.
	 *
	 * @return void
	 */
	public function render_notice() {
		$screen = get_current_screen();

		if ( ! $screen || WTS_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.
		if ( ! isset( $_GET['wts_notice'] ) || 'imported' !== $_GET['wts_notice'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.
		$count = isset( $_GET['wts_count'] ) ? absint( $_GET['wts_count'] ) : 0;

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( sprintf(
				/* translators: %d: number of testimonials created. */
				_n( '%d sample testimonial was created.', '%d sample testimonials were created.', $count, 'wp-testimonial-slider' ),
				$count
			) )
		);
	}

	/**
	 * Default settings values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'desktop'           => 5,
			'tablet'            => 3,
			'mobile'            => 1,
			'breakpoint_tablet' => 1024,
			'breakpoint_mobile' => 767,
			'autoplay'          => 1,
			'autoplay_speed'    => 4000,
			'speed'             => 500,
			'loop'              => 1,
			'arrows'            => 1,
			'dots'              => 1,
			'center_mode'       => 1,
			'show_photo'        => 1,
			'show_quote'        => 1,
			'show_name'         => 1,
			'show_role'         => 1,
			'show_rating'       => 1,
			'equal_height'      => 1,
			'gap'               => 24,
			'accent_color'      => '#f5a623',
			'text_color'        => '#333333',
			'card_bg'           => '#ffffff',
			'orderby'           => 'menu_order',
			'limit'             => 12,
			'category'          => '',
		);
	}

	/**
	 * Get saved settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Add the admin menu entries.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'edit.php?post_type=' . WTS_Post_Type::POST_TYPE,
			__( 'Testimonial Slider Settings', 'wp-testimonial-slider' ),
			__( 'Slider Settings', 'wp-testimonial-slider' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the option and its sanitizer.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'wts_settings_group',
			self::OPTION,
			array( $this, 'sanitize' )
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$defaults = self::defaults();
		$out      = array();

		$ranges = array(
			'desktop'           => array( 1, 10 ),
			'tablet'            => array( 1, 8 ),
			'mobile'            => array( 1, 4 ),
			'breakpoint_tablet' => array( 480, 2000 ),
			'breakpoint_mobile' => array( 320, 1500 ),
			'autoplay_speed'    => array( 500, 30000 ),
			'speed'             => array( 0, 5000 ),
			'gap'               => array( 0, 80 ),
			'limit'             => array( 0, 100 ),
		);

		$bools = array( 'autoplay', 'loop', 'arrows', 'dots', 'center_mode', 'show_photo', 'show_quote', 'show_name', 'show_role', 'show_rating', 'equal_height' );

		foreach ( $bools as $key ) {
			$out[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
		}

		foreach ( $ranges as $key => $range ) {
			$value        = isset( $input[ $key ] ) ? (int) $input[ $key ] : $defaults[ $key ];
			$out[ $key ]  = max( $range[0], min( $range[1], $value ) );
		}

		$colors = array( 'accent_color', 'text_color', 'card_bg' );

		foreach ( $colors as $key ) {
			$value        = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$out[ $key ]  = $value ? $value : $defaults[ $key ];
		}

		$orderby          = isset( $input['orderby'] ) ? sanitize_key( $input['orderby'] ) : 'menu_order';
		$out['orderby']   = in_array( $orderby, array( 'menu_order', 'date', 'title', 'rand', 'ID' ), true ) ? $orderby : 'menu_order';
		$out['category']  = isset( $input['category'] ) ? sanitize_title( $input['category'] ) : '';

		return $out;
	}

	/**
	 * Enqueue admin assets on our screens.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen || WTS_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'wts-admin', WTS_PLUGIN_URL . 'assets/css/admin.css', array(), WTS_VERSION );
		wp_enqueue_script( 'wts-admin', WTS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), WTS_VERSION, true );
		wp_enqueue_style( 'wp-color-picker' );

		unset( $hook );
	}

	/**
	 * Add a settings link on the plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url = admin_url( 'edit.php?post_type=' . WTS_Post_Type::POST_TYPE . '&page=' . self::PAGE );

		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wp-testimonial-slider' ) . '</a>' );

		return $links;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$opts = self::get();

		$slider_fields = array(
			'desktop'           => array( __( 'Slides on desktop (5-6 recommended)', 'wp-testimonial-slider' ), 'number' ),
			'tablet'            => array( __( 'Slides on tablet (3-4 recommended)', 'wp-testimonial-slider' ), 'number' ),
			'mobile'            => array( __( 'Slides on mobile (1-2 recommended)', 'wp-testimonial-slider' ), 'number' ),
			'breakpoint_tablet' => array( __( 'Tablet breakpoint (px, max width)', 'wp-testimonial-slider' ), 'number' ),
			'breakpoint_mobile' => array( __( 'Mobile breakpoint (px, max width)', 'wp-testimonial-slider' ), 'number' ),
			'gap'               => array( __( 'Space between slides (px)', 'wp-testimonial-slider' ), 'number' ),
			'autoplay_speed'    => array( __( 'Autoplay delay (ms)', 'wp-testimonial-slider' ), 'number' ),
			'speed'             => array( __( 'Transition speed (ms)', 'wp-testimonial-slider' ), 'number' ),
		);

		$toggle_fields = array(
			'autoplay'     => __( 'Autoplay', 'wp-testimonial-slider' ),
			'loop'         => __( 'Infinite loop', 'wp-testimonial-slider' ),
			'arrows'       => __( 'Show prev / next arrows', 'wp-testimonial-slider' ),
			'dots'         => __( 'Show pagination dots', 'wp-testimonial-slider' ),
			'center_mode'  => __( 'Highlight active slide (center mode)', 'wp-testimonial-slider' ),
			'equal_height' => __( 'Equal card heights', 'wp-testimonial-slider' ),
		);

		$content_fields = array(
			'show_photo'  => __( 'Show photo', 'wp-testimonial-slider' ),
			'show_quote'  => __( 'Show testimonial quote', 'wp-testimonial-slider' ),
			'show_name'   => __( 'Show name', 'wp-testimonial-slider' ),
			'show_role'   => __( 'Show role / company', 'wp-testimonial-slider' ),
			'show_rating' => __( 'Show star rating', 'wp-testimonial-slider' ),
		);

		$color_fields = array(
			'accent_color' => __( 'Accent colour (active highlight, stars)', 'wp-testimonial-slider' ),
			'text_color'   => __( 'Text colour', 'wp-testimonial-slider' ),
			'card_bg'      => __( 'Card background', 'wp-testimonial-slider' ),
		);
		?>
		<div class="wrap wts-settings">
			<h1><?php esc_html_e( 'Testimonial Slider Settings', 'wp-testimonial-slider' ); ?></h1>

			<p>
				<?php esc_html_e( 'Add testimonials under the Testimonials menu, then place the slider anywhere with this shortcode:', 'wp-testimonial-slider' ); ?>
				<code>[wp_testimonial_slider]</code>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'wts_settings_group' ); ?>

				<h2 class="title"><?php esc_html_e( 'Slider', 'wp-testimonial-slider' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
					<?php foreach ( $slider_fields as $key => $field ) : ?>
						<tr>
							<th scope="row"><label for="wts_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
							<td>
								<input type="number" id="wts_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $opts[ $key ] ); ?>" class="small-text" min="0" />
							</td>
						</tr>
					<?php endforeach; ?>

					<?php foreach ( $toggle_fields as $key => $label ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td>
								<label class="wts-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( 1, $opts[ $key ] ); ?> />
									<span class="wts-switch-ui"></span>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<h2 class="title"><?php esc_html_e( 'Card Content', 'wp-testimonial-slider' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
					<?php foreach ( $content_fields as $key => $label ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td>
								<label class="wts-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( 1, $opts[ $key ] ); ?> />
									<span class="wts-switch-ui"></span>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<h2 class="title"><?php esc_html_e( 'Appearance', 'wp-testimonial-slider' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
					<?php foreach ( $color_fields as $key => $label ) : ?>
						<tr>
							<th scope="row"><label for="wts_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td>
								<input type="text" class="wts-color-field" id="wts_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $opts[ $key ] ); ?>" data-default-color="<?php echo esc_attr( $opts[ $key ] ); ?>" />
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<h2 class="title"><?php esc_html_e( 'Query', 'wp-testimonial-slider' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="wts_orderby"><?php esc_html_e( 'Order by', 'wp-testimonial-slider' ); ?></label></th>
							<td>
								<select id="wts_orderby" name="<?php echo esc_attr( self::OPTION ); ?>[orderby]">
									<?php
									$orders = array(
										'menu_order' => __( 'Manual order', 'wp-testimonial-slider' ),
										'date'       => __( 'Date published', 'wp-testimonial-slider' ),
										'title'      => __( 'Name', 'wp-testimonial-slider' ),
										'rand'       => __( 'Random', 'wp-testimonial-slider' ),
										'ID'         => __( 'ID', 'wp-testimonial-slider' ),
									);
									foreach ( $orders as $value => $label ) :
										?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $opts['orderby'], $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="wts_limit"><?php esc_html_e( 'Maximum testimonials (0 = all)', 'wp-testimonial-slider' ); ?></label></th>
							<td>
								<input type="number" id="wts_limit" name="<?php echo esc_attr( self::OPTION ); ?>[limit]" value="<?php echo esc_attr( $opts['limit'] ); ?>" class="small-text" min="0" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="wts_category"><?php esc_html_e( 'Testimonial group (slug, optional)', 'wp-testimonial-slider' ); ?></label></th>
							<td>
								<input type="text" id="wts_category" name="<?php echo esc_attr( self::OPTION ); ?>[category]" value="<?php echo esc_attr( $opts['category'] ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Leave empty to show every testimonial.', 'wp-testimonial-slider' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Shortcode Options', 'wp-testimonial-slider' ); ?></h2>
			<p><?php esc_html_e( 'Every setting above can be overridden per shortcode, for example:', 'wp-testimonial-slider' ); ?></p>
			<p><code>[wp_testimonial_slider desktop="6" tablet="4" mobile="2" autoplay="true" dots="false" category="clients"]</code></p>

			<hr />
			<h2><?php esc_html_e( 'Getting Started', 'wp-testimonial-slider' ); ?></h2>
			<p><?php esc_html_e( 'New here? Add six sample testimonials to see the slider in action, then edit or delete them.', 'wp-testimonial-slider' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wts_import_samples" />
				<?php wp_nonce_field( 'wts_import_samples' ); ?>
				<?php submit_button( __( 'Import sample testimonials', 'wp-testimonial-slider' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}
}
