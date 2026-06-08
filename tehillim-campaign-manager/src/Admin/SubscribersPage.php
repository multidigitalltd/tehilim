<?php
/**
 * Admin subscribers management.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\Database\SubscribersRepository;
use TCM\PostTypes\CampaignPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists opt-in subscribers (daily chapter + Tehillim Corps), with status
 * toggling and a CSV export. Admin only, nonce-protected.
 */
final class SubscribersPage implements Registerable {

	const PER_PAGE      = 50;
	const STATUS_ACTION = 'tcm_sub_status';
	const EXPORT_ACTION = 'tcm_sub_export';

	/**
	 * @var SubscribersRepository
	 */
	private $subscribers;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->subscribers = new SubscribersRepository();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_' . self::STATUS_ACTION, array( $this, 'handle_status' ) );
		add_action( 'admin_post_' . self::EXPORT_ACTION, array( $this, 'handle_export' ) );
	}

	/**
	 * Register the submenu page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'edit.php?post_type=' . CampaignPostType::POST_TYPE,
			__( 'Subscribers', 'tehillim-campaign-manager' ),
			__( 'Subscribers', 'tehillim-campaign-manager' ),
			'manage_options',
			'tcm-subscribers',
			array( $this, 'render' )
		);
	}

	/**
	 * List labels.
	 *
	 * @return array<string,string>
	 */
	private function list_labels() {
		return array(
			'daily_chapter'   => __( 'Daily chapter', 'tehillim-campaign-manager' ),
			'campaign_alerts' => __( 'Tehillim Corps', 'tehillim-campaign-manager' ),
		);
	}

	/**
	 * Status labels.
	 *
	 * @return array<string,string>
	 */
	private function status_labels() {
		return array(
			'active'       => __( 'Active', 'tehillim-campaign-manager' ),
			'unsubscribed' => __( 'Unsubscribed', 'tehillim-campaign-manager' ),
			'pending'      => __( 'Pending', 'tehillim-campaign-manager' ),
		);
	}

	/**
	 * Read sanitized list/status filters from the request.
	 *
	 * @return array{list:string,status:string}
	 */
	private function current_filters() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list filters.
		$list   = isset( $_GET['list'] ) ? sanitize_key( wp_unslash( $_GET['list'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $this->list_labels()[ $list ] ) ) {
			$list = '';
		}
		if ( ! isset( $this->status_labels()[ $status ] ) ) {
			$status = '';
		}
		return array(
			'list'   => $list,
			'status' => $status,
		);
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$filters = $this->current_filters();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination.
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset = ( $paged - 1 ) * self::PER_PAGE;

		$total = $this->subscribers->count_filtered( $filters['list'], $filters['status'] );
		$rows  = $this->subscribers->paged( $filters['list'], $filters['status'], self::PER_PAGE, $offset );
		$pages = (int) ceil( $total / self::PER_PAGE );

		$list_labels   = $this->list_labels();
		$status_labels = $this->status_labels();
		$base_url      = admin_url( 'edit.php?post_type=' . CampaignPostType::POST_TYPE . '&page=tcm-subscribers' );
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'Subscribers', 'tehillim-campaign-manager' ); ?></h1>

			<form method="get" style="margin:14px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( CampaignPostType::POST_TYPE ); ?>">
				<input type="hidden" name="page" value="tcm-subscribers">
				<select name="list">
					<option value=""><?php esc_html_e( 'All lists', 'tehillim-campaign-manager' ); ?></option>
					<?php foreach ( $list_labels as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['list'], $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="status">
					<option value=""><?php esc_html_e( 'Any status', 'tehillim-campaign-manager' ); ?></option>
					<?php foreach ( $status_labels as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button"><?php esc_html_e( 'Filter', 'tehillim-campaign-manager' ); ?></button>
				<a class="button button-primary" href="<?php echo esc_url( $this->export_url( $filters ) ); ?>"><?php esc_html_e( 'Export CSV', 'tehillim-campaign-manager' ); ?></a>
				<span class="description"><?php echo esc_html( sprintf( /* translators: %s: subscriber count. */ __( '%s subscribers', 'tehillim-campaign-manager' ), number_format_i18n( $total ) ) ); ?></span>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Contact', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Channel', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'List', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Joined', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Action', 'tehillim-campaign-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No subscribers found.', 'tehillim-campaign-manager' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->name ? $row->name : '-' ); ?></td>
							<td><?php echo esc_html( 'whatsapp' === $row->channel ? $row->phone : $row->email ); ?></td>
							<td><?php echo esc_html( 'whatsapp' === $row->channel ? __( 'WhatsApp', 'tehillim-campaign-manager' ) : __( 'Email', 'tehillim-campaign-manager' ) ); ?></td>
							<td><?php echo esc_html( $list_labels[ $row->list_key ] ?? $row->list_key ); ?></td>
							<td><?php echo esc_html( $status_labels[ $row->status ] ?? $row->status ); ?></td>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $row->created_at ) ); ?></td>
							<td>
								<?php
								$to    = 'active' === $row->status ? 'unsubscribed' : 'active';
								$label = 'active' === $row->status ? __( 'Unsubscribe', 'tehillim-campaign-manager' ) : __( 'Reactivate', 'tehillim-campaign-manager' );
								?>
								<a class="button button-small" href="<?php echo esc_url( $this->status_url( (int) $row->id, $to, $filters, $paged ) ); ?>"><?php echo esc_html( $label ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%', $base_url . '&list=' . $filters['list'] . '&status=' . $filters['status'] ),
								'format'    => '',
								'current'   => $paged,
								'total'     => $pages,
								'prev_text' => '‹',
								'next_text' => '›',
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Build a nonce-protected status-change URL.
	 *
	 * @param int                              $id      Subscriber id.
	 * @param string                           $to      Target status.
	 * @param array{list:string,status:string} $filters Current filters.
	 * @param int                              $paged   Current page.
	 * @return string
	 */
	private function status_url( $id, $to, $filters, $paged ) {
		return wp_nonce_url(
			admin_url(
				'admin-post.php?action=' . self::STATUS_ACTION . '&id=' . $id . '&to=' . $to
				. '&list=' . $filters['list'] . '&status=' . $filters['status'] . '&paged=' . $paged
			),
			self::STATUS_ACTION . '_' . $id
		);
	}

	/**
	 * Build a nonce-protected export URL.
	 *
	 * @param array{list:string,status:string} $filters Current filters.
	 * @return string
	 */
	private function export_url( $filters ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::EXPORT_ACTION . '&list=' . $filters['list'] . '&status=' . $filters['status'] ),
			self::EXPORT_ACTION
		);
	}

	/**
	 * Handle a status change, then redirect back to the list.
	 *
	 * @return void
	 */
	public function handle_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tehillim-campaign-manager' ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( self::STATUS_ACTION . '_' . $id );

		$to = isset( $_GET['to'] ) ? sanitize_key( wp_unslash( $_GET['to'] ) ) : '';
		if ( $id && in_array( $to, array( 'active', 'unsubscribed' ), true ) ) {
			$this->subscribers->set_status( $id, $to );
		}

		$list   = isset( $_GET['list'] ) ? sanitize_key( wp_unslash( $_GET['list'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		wp_safe_redirect(
			admin_url( 'edit.php?post_type=' . CampaignPostType::POST_TYPE . '&page=tcm-subscribers&list=' . $list . '&status=' . $status . '&paged=' . $paged )
		);
		exit;
	}

	/**
	 * Stream a CSV of the filtered subscribers.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tehillim-campaign-manager' ) );
		}
		check_admin_referer( self::EXPORT_ACTION );

		$filters = $this->current_filters();
		$rows    = $this->subscribers->paged( $filters['list'], $filters['status'], 5000, 0 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=tehillim-subscribers-' . gmdate( 'Ymd' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array( 'name', 'email', 'phone', 'channel', 'list', 'status', 'joined' ) );
		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				array_map(
					array( Exporter::class, 'csv_cell' ),
					array( $row->name, $row->email, $row->phone, $row->channel, $row->list_key, $row->status, $row->created_at )
				)
			);
		}
		fclose( $out );
		exit;
	}
}
