<?php
/**
 * Campaign approvals (pending -> publish / reject).
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A focused screen for moderating user-created campaigns that are awaiting
 * approval: one-click approve (publish) or reject (back to draft). The menu
 * shows a count bubble so pending campaigns are not missed. Admin only,
 * nonce-protected.
 */
final class CampaignApprovals implements Registerable {

	const APPROVE_ACTION = 'tcm_campaign_approve';
	const REJECT_ACTION  = 'tcm_campaign_reject';

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_' . self::APPROVE_ACTION, array( $this, 'handle_approve' ) );
		add_action( 'admin_post_' . self::REJECT_ACTION, array( $this, 'handle_reject' ) );
	}

	/**
	 * Number of campaigns awaiting approval.
	 *
	 * @return int
	 */
	private function pending_count() {
		$counts = wp_count_posts( CampaignPostType::POST_TYPE );
		return isset( $counts->pending ) ? (int) $counts->pending : 0;
	}

	/**
	 * Register the submenu page (with a pending-count bubble).
	 *
	 * @return void
	 */
	public function add_page() {
		$pending = $this->pending_count();
		$label   = __( 'Approvals', 'tehillim-campaign-manager' );
		if ( $pending > 0 ) {
			$label .= ' <span class="awaiting-mod"><span class="pending-count">' . esc_html( number_format_i18n( $pending ) ) . '</span></span>';
		}

		add_submenu_page(
			'edit.php?post_type=' . CampaignPostType::POST_TYPE,
			__( 'Campaign approvals', 'tehillim-campaign-manager' ),
			$label,
			'manage_options',
			'tcm-approvals',
			array( $this, 'render' )
		);
	}

	/**
	 * Render the approvals screen.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pending  = get_posts(
			array(
				'post_type'      => CampaignPostType::POST_TYPE,
				'post_status'    => 'pending',
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'ASC',
			)
		);
		$date_fmt = get_option( 'date_format' );
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'Campaign approvals', 'tehillim-campaign-manager' ); ?></h1>

			<?php if ( empty( $pending ) ) : ?>
				<p><?php esc_html_e( 'No campaigns are awaiting approval.', 'tehillim-campaign-manager' ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'These campaigns were created by users and are waiting to be published. Approving a campaign makes it public and announces it.', 'tehillim-campaign-manager' ); ?></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Campaign', 'tehillim-campaign-manager' ); ?></th>
							<th><?php esc_html_e( 'Dedicated to', 'tehillim-campaign-manager' ); ?></th>
							<th><?php esc_html_e( 'Created by', 'tehillim-campaign-manager' ); ?></th>
							<th><?php esc_html_e( 'Submitted', 'tehillim-campaign-manager' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'tehillim-campaign-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $pending as $post ) :
							$author = get_userdata( (int) $post->post_author );
							?>
							<tr>
								<td>
									<strong><a href="<?php echo esc_url( (string) get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></strong>
									&nbsp;·&nbsp;
									<a href="<?php echo esc_url( (string) get_preview_post_link( $post ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview', 'tehillim-campaign-manager' ); ?></a>
								</td>
								<td><?php echo esc_html( (string) get_post_meta( $post->ID, '_tcm_dedicated_to', true ) ); ?></td>
								<td><?php echo esc_html( $author ? $author->display_name : '-' ); ?></td>
								<td><?php echo esc_html( mysql2date( $date_fmt, $post->post_date ) ); ?></td>
								<td>
									<a class="button button-primary" href="<?php echo esc_url( $this->action_url( self::APPROVE_ACTION, (int) $post->ID ) ); ?>"><?php esc_html_e( 'Approve & publish', 'tehillim-campaign-manager' ); ?></a>
									<a class="button" href="<?php echo esc_url( $this->action_url( self::REJECT_ACTION, (int) $post->ID ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Reject this campaign? It will be moved back to draft.', 'tehillim-campaign-manager' ) ); ?>');"><?php esc_html_e( 'Reject', 'tehillim-campaign-manager' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Build a nonce-protected action URL.
	 *
	 * @param string $action Action name.
	 * @param int    $id     Campaign id.
	 * @return string
	 */
	private function action_url( $action, $id ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . $action . '&id=' . $id ),
			$action . '_' . $id
		);
	}

	/**
	 * Approve (publish) a pending campaign.
	 *
	 * @return void
	 */
	public function handle_approve() {
		$id = $this->authorize( self::APPROVE_ACTION );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->redirect_back();
	}

	/**
	 * Reject (move back to draft) a pending campaign.
	 *
	 * @return void
	 */
	public function handle_reject() {
		$id = $this->authorize( self::REJECT_ACTION );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'draft',
			)
		);
		$this->redirect_back();
	}

	/**
	 * Validate capability + nonce + that the post is a pending campaign.
	 *
	 * @param string $action Action name.
	 * @return int Campaign id.
	 */
	private function authorize( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tehillim-campaign-manager' ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( $action . '_' . $id );

		if ( ! $id || CampaignPostType::POST_TYPE !== get_post_type( $id ) || 'pending' !== get_post_status( $id ) ) {
			wp_die( esc_html__( 'Invalid campaign.', 'tehillim-campaign-manager' ) );
		}
		return $id;
	}

	/**
	 * Redirect back to the approvals screen.
	 *
	 * @return void
	 */
	private function redirect_back() {
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . CampaignPostType::POST_TYPE . '&page=tcm-approvals' ) );
		exit;
	}
}
