<?php
/**
 * Testimonial custom post type, taxonomy and meta boxes.
 *
 * @package WP_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "Testimonial" content type and its custom fields.
 */
class WTS_Post_Type {

	const POST_TYPE = 'wts_testimonial';
	const TAXONOMY  = 'wts_testimonial_cat';

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_column' ), 10, 2 );
	}

	/**
	 * Register the testimonial post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Testimonials', 'wp-testimonial-slider' ),
			'singular_name'      => __( 'Testimonial', 'wp-testimonial-slider' ),
			'menu_name'          => __( 'Testimonials', 'wp-testimonial-slider' ),
			'add_new'            => __( 'Add New', 'wp-testimonial-slider' ),
			'add_new_item'       => __( 'Add New Testimonial', 'wp-testimonial-slider' ),
			'edit_item'          => __( 'Edit Testimonial', 'wp-testimonial-slider' ),
			'new_item'           => __( 'New Testimonial', 'wp-testimonial-slider' ),
			'view_item'          => __( 'View Testimonial', 'wp-testimonial-slider' ),
			'search_items'       => __( 'Search Testimonials', 'wp-testimonial-slider' ),
			'not_found'          => __( 'No testimonials found', 'wp-testimonial-slider' ),
			'not_found_in_trash' => __( 'No testimonials found in Trash', 'wp-testimonial-slider' ),
			'all_items'          => __( 'All Testimonials', 'wp-testimonial-slider' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => $labels,
				'public'          => true,
				'has_archive'     => false,
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-format-quote',
				'menu_position'   => 26,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
				'rewrite'         => array( 'slug' => 'testimonial' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Register the testimonial category taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Testimonial Groups', 'wp-testimonial-slider' ),
					'singular_name' => __( 'Testimonial Group', 'wp-testimonial-slider' ),
				),
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register the details meta box.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'wts_testimonial_details',
			__( 'Testimonial Details', 'wp-testimonial-slider' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the details meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'wts_save_meta', 'wts_meta_nonce' );

		$role     = get_post_meta( $post->ID, '_wts_role', true );
		$company  = get_post_meta( $post->ID, '_wts_company', true );
		$url      = get_post_meta( $post->ID, '_wts_url', true );
		$rating   = (int) get_post_meta( $post->ID, '_wts_rating', true );
		$rating   = $rating > 0 ? $rating : 5;
		?>
		<p>
			<label for="wts_role"><strong><?php esc_html_e( 'Role / Position', 'wp-testimonial-slider' ); ?></strong></label><br />
			<input type="text" id="wts_role" name="wts_role" class="widefat" value="<?php echo esc_attr( $role ); ?>" placeholder="<?php esc_attr_e( 'CEO, Marketing Lead...', 'wp-testimonial-slider' ); ?>" />
		</p>
		<p>
			<label for="wts_company"><strong><?php esc_html_e( 'Company / Website', 'wp-testimonial-slider' ); ?></strong></label><br />
			<input type="text" id="wts_company" name="wts_company" class="widefat" value="<?php echo esc_attr( $company ); ?>" placeholder="<?php esc_attr_e( 'Acme Inc.', 'wp-testimonial-slider' ); ?>" />
		</p>
		<p>
			<label for="wts_url"><strong><?php esc_html_e( 'Link (optional)', 'wp-testimonial-slider' ); ?></strong></label><br />
			<input type="url" id="wts_url" name="wts_url" class="widefat" value="<?php echo esc_url( $url ); ?>" placeholder="https://" />
		</p>
		<p>
			<label for="wts_rating"><strong><?php esc_html_e( 'Rating', 'wp-testimonial-slider' ); ?></strong></label><br />
			<select id="wts_rating" name="wts_rating">
				<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $rating, $i ); ?>>
						<?php echo esc_html( str_repeat( '★', $i ) ); ?>
					</option>
				<?php endfor; ?>
			</select>
		</p>
		<p class="description">
			<?php esc_html_e( 'Use the "Featured image" panel for the person\'s photo. The post title is the person\'s name and the content is the testimonial text.', 'wp-testimonial-slider' ); ?>
		</p>
		<?php
	}

	/**
	 * Persist meta box values.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['wts_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wts_meta_nonce'] ) ), 'wts_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['wts_role'] ) ) {
			update_post_meta( $post_id, '_wts_role', sanitize_text_field( wp_unslash( $_POST['wts_role'] ) ) );
		}

		if ( isset( $_POST['wts_company'] ) ) {
			update_post_meta( $post_id, '_wts_company', sanitize_text_field( wp_unslash( $_POST['wts_company'] ) ) );
		}

		if ( isset( $_POST['wts_url'] ) ) {
			update_post_meta( $post_id, '_wts_url', esc_url_raw( wp_unslash( $_POST['wts_url'] ) ) );
		}

		if ( isset( $_POST['wts_rating'] ) ) {
			$rating = max( 1, min( 5, (int) $_POST['wts_rating'] ) );
			update_post_meta( $post_id, '_wts_rating', $rating );
		}

		unset( $post );
	}

	/**
	 * Add admin list columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function admin_columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['wts_photo'] = __( 'Photo', 'wp-testimonial-slider' );
			}

			$new[ $key ] = $label;
		}

		$new['wts_role']   = __( 'Role', 'wp-testimonial-slider' );
		$new['wts_rating'] = __( 'Rating', 'wp-testimonial-slider' );

		return $new;
	}

	/**
	 * Render admin list column values.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_admin_column( $column, $post_id ) {
		switch ( $column ) {
			case 'wts_photo':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'style' => 'border-radius:50%;' ) );
				} else {
					echo '&mdash;';
				}
				break;

			case 'wts_role':
				$role    = get_post_meta( $post_id, '_wts_role', true );
				$company = get_post_meta( $post_id, '_wts_company', true );
				$out     = array_filter( array( $role, $company ) );
				echo $out ? esc_html( implode( ', ', $out ) ) : '&mdash;';
				break;

			case 'wts_rating':
				$rating = (int) get_post_meta( $post_id, '_wts_rating', true );
				echo $rating > 0 ? esc_html( str_repeat( '★', $rating ) ) : '&mdash;';
				break;
		}
	}
}
