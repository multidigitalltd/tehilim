<?php
/**
 * Advertisement custom post type.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\PostTypes;

use TCM\Contracts\Registerable;
use TCM\Services\AdService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the private `tcm_ad` post type and its settings meta box (zone,
 * target URL, image, active flag and schedule).
 */
final class AdPostType implements Registerable {

	const POST_TYPE = 'tcm_ad';
	const NONCE     = 'tcm_save_ad';

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save' ), 10, 1 );
	}

	/**
	 * Register the CPT (admin-managed, not publicly queryable).
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Ads', 'tehillim-campaign-manager' ),
					'singular_name' => __( 'Ad', 'tehillim-campaign-manager' ),
					'add_new_item'  => __( 'Add ad', 'tehillim-campaign-manager' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'edit.php?post_type=' . CampaignPostType::POST_TYPE,
				'supports'     => array( 'title' ),
			)
		);
	}

	/**
	 * Register the settings meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box( 'tcm_ad_settings', __( 'Ad settings', 'tehillim-campaign-manager' ), array( $this, 'render' ), self::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::NONCE, 'tcm_ad_nonce' );
		$zone   = get_post_meta( $post->ID, '_tcm_ad_zone', true );
		$url    = get_post_meta( $post->ID, '_tcm_ad_url', true );
		$image  = get_post_meta( $post->ID, '_tcm_ad_image', true );
		$active = get_post_meta( $post->ID, '_tcm_ad_active', true );
		$start  = get_post_meta( $post->ID, '_tcm_ad_start', true );
		$end    = get_post_meta( $post->ID, '_tcm_ad_end', true );
		?>
		<p>
			<label for="tcm_ad_zone"><strong><?php esc_html_e( 'Zone', 'tehillim-campaign-manager' ); ?></strong></label><br>
			<select id="tcm_ad_zone" name="tcm_ad_zone">
				<?php foreach ( AdService::ZONES as $z ) : ?>
					<option value="<?php echo esc_attr( $z ); ?>" <?php selected( $zone, $z ); ?>><?php echo esc_html( $z ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php esc_html_e( 'Ads never appear inside the chapter/prayer reading area.', 'tehillim-campaign-manager' ); ?></span>
		</p>
		<p>
			<label for="tcm_ad_image"><strong><?php esc_html_e( 'Image URL', 'tehillim-campaign-manager' ); ?></strong></label><br>
			<input type="url" id="tcm_ad_image" name="tcm_ad_image" class="large-text" value="<?php echo esc_attr( $image ); ?>">
		</p>
		<p>
			<label for="tcm_ad_url"><strong><?php esc_html_e( 'Target URL', 'tehillim-campaign-manager' ); ?></strong></label><br>
			<input type="url" id="tcm_ad_url" name="tcm_ad_url" class="large-text" value="<?php echo esc_attr( $url ); ?>">
		</p>
		<p>
			<label><input type="checkbox" name="tcm_ad_active" value="1" <?php checked( $active, '1' ); ?>> <?php esc_html_e( 'Active', 'tehillim-campaign-manager' ); ?></label>
		</p>
		<p>
			<label for="tcm_ad_start"><?php esc_html_e( 'Start date', 'tehillim-campaign-manager' ); ?></label>
			<input type="date" id="tcm_ad_start" name="tcm_ad_start" value="<?php echo esc_attr( $start ); ?>">
			&nbsp;
			<label for="tcm_ad_end"><?php esc_html_e( 'End date', 'tehillim-campaign-manager' ); ?></label>
			<input type="date" id="tcm_ad_end" name="tcm_ad_end" value="<?php echo esc_attr( $end ); ?>">
		</p>
		<?php
	}

	/**
	 * Persist the meta.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public function save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['tcm_ad_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tcm_ad_nonce'] ) ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$zone = sanitize_key( $_POST['tcm_ad_zone'] ?? '' );
		update_post_meta( $post_id, '_tcm_ad_zone', in_array( $zone, AdService::ZONES, true ) ? $zone : '' );
		update_post_meta( $post_id, '_tcm_ad_image', esc_url_raw( wp_unslash( $_POST['tcm_ad_image'] ?? '' ) ) );
		update_post_meta( $post_id, '_tcm_ad_url', esc_url_raw( wp_unslash( $_POST['tcm_ad_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_tcm_ad_active', empty( $_POST['tcm_ad_active'] ) ? '0' : '1' );
		update_post_meta( $post_id, '_tcm_ad_start', sanitize_text_field( $_POST['tcm_ad_start'] ?? '' ) );
		update_post_meta( $post_id, '_tcm_ad_end', sanitize_text_field( $_POST['tcm_ad_end'] ?? '' ) );
	}
}
