<?php
/**
 * Admin dashboard.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\AnalyticsService;
use TCM\Services\StatsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Overview screen listing campaigns and their progress.
 */
final class Dashboard implements Registerable {

	/**
	 * @var StatsService
	 */
	private $stats;

	/**
	 * @var AnalyticsService
	 */
	private $analytics;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->stats     = new StatsService();
		$this->analytics = new AnalyticsService();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'edit.php?post_type=' . CampaignPostType::POST_TYPE,
			__( 'Distribution dashboard', 'tehillim-campaign-manager' ),
			__( 'Dashboard', 'tehillim-campaign-manager' ),
			'manage_options',
			'tcm-dashboard',
			array( $this, 'render' )
		);
	}

	/**
	 * Render the dashboard.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$campaigns = get_posts(
			array(
				'post_type'      => CampaignPostType::POST_TYPE,
				'posts_per_page' => 100,
				'post_status'    => 'any',
			)
		);
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'Tehillim distribution dashboard', 'tehillim-campaign-manager' ); ?></h1>
			<?php $this->render_kpis(); ?>
			<?php $this->render_trend(); ?>
			<p><?php esc_html_e( 'Archive shortcode:', 'tehillim-campaign-manager' ); ?> <code>[tehillim_campaigns]</code></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Campaign', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Base goal', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Bonus', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Completed', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Current book', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Progress', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Export', 'tehillim-campaign-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $campaigns as $campaign ) : ?>
						<?php $s = $this->stats->for_campaign( $campaign->ID ); ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $campaign->ID ) ); ?>"><?php echo esc_html( get_the_title( $campaign ) ); ?></a>
								&nbsp;·&nbsp;
								<a href="<?php echo esc_url( get_permalink( $campaign->ID ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'tehillim-campaign-manager' ); ?></a>
							</td>
							<td><?php echo esc_html( $s['target'] ); ?></td>
							<td><?php echo esc_html( $s['bonus'] ); ?></td>
							<td><?php echo esc_html( $s['completed_books'] ); ?></td>
							<td><?php echo esc_html( $s['round'] ); ?></td>
							<td><?php echo esc_html( $s['percent'] ); ?>%</td>
							<td>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . Exporter::ACTION . '&campaign_id=' . $campaign->ID ), 'tcm_export' ) ); ?>"><?php esc_html_e( 'CSV', 'tehillim-campaign-manager' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the site-wide KPI strip above the campaigns table.
	 *
	 * @return void
	 */
	private function render_kpis() {
		$k = $this->analytics->summary();

		$cards = array(
			array(
				'label' => __( 'Active campaigns', 'tehillim-campaign-manager' ),
				'value' => number_format_i18n( $k['campaigns'] ),
				'hint'  => '',
			),
			array(
				'label' => __( 'Chapters completed', 'tehillim-campaign-manager' ),
				'value' => number_format_i18n( $k['chapters_done'] ),
				'hint'  => sprintf(
					/* translators: %s: chapters completed in the last 30 days. */
					__( '%s in the last 30 days', 'tehillim-campaign-manager' ),
					number_format_i18n( $k['done_30d'] )
				),
			),
			array(
				'label' => __( 'Participants', 'tehillim-campaign-manager' ),
				'value' => number_format_i18n( $k['participants'] ),
				'hint'  => '',
			),
			array(
				'label' => __( 'Active subscribers', 'tehillim-campaign-manager' ),
				'value' => number_format_i18n( $k['subscribers_active'] ),
				'hint'  => '',
			),
			array(
				'label' => __( 'Ambassadors', 'tehillim-campaign-manager' ),
				'value' => number_format_i18n( $k['ambassadors'] ),
				'hint'  => '',
			),
			array(
				'label' => __( 'Ad clicks', 'tehillim-campaign-manager' ),
				'value' => number_format_i18n( $k['ad_clicks'] ),
				'hint'  => sprintf(
					/* translators: 1: impressions, 2: click-through rate. */
					__( '%1$s impressions · %2$s%% CTR', 'tehillim-campaign-manager' ),
					number_format_i18n( $k['ad_impressions'] ),
					number_format_i18n( $k['ad_ctr'], 1 )
				),
			),
		);
		?>
		<div class="tcm-kpis">
			<?php foreach ( $cards as $card ) : ?>
				<div class="tcm-kpi">
					<span class="tcm-kpi-value"><?php echo esc_html( $card['value'] ); ?></span>
					<span class="tcm-kpi-label"><?php echo esc_html( $card['label'] ); ?></span>
					<?php if ( '' !== $card['hint'] ) : ?>
						<span class="tcm-kpi-hint"><?php echo esc_html( $card['hint'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<style>
			.tcm-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin:18px 0 22px}
			.tcm-kpi{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px 18px;display:flex;flex-direction:column;gap:3px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
			.tcm-kpi-value{font-size:1.8rem;font-weight:800;line-height:1.1;color:#1d2327}
			.tcm-kpi-label{font-size:.82rem;font-weight:600;color:#50575e}
			.tcm-kpi-hint{font-size:.74rem;color:#787c82}
		</style>
		<?php
	}

	/**
	 * Render a dependency-free inline-SVG bar chart of chapters completed per
	 * day over the last 30 days.
	 *
	 * @return void
	 */
	private function render_trend() {
		$series = $this->analytics->daily_trend( 30 );
		$max    = 0;
		$sum    = 0;
		foreach ( $series as $point ) {
			$max  = max( $max, $point['count'] );
			$sum += $point['count'];
		}
		if ( $sum <= 0 ) {
			return;
		}

		$count    = count( $series );
		$width    = 720;
		$height   = 140;
		$gap      = 3;
		$bar_w    = ( $width - ( $gap * ( $count - 1 ) ) ) / $count;
		$date_fmt = get_option( 'date_format' );
		?>
		<div class="tcm-trend">
			<h2><?php esc_html_e( 'Chapters completed — last 30 days', 'tehillim-campaign-manager' ); ?></h2>
			<svg class="tcm-trend-svg" viewBox="0 0 <?php echo esc_attr( (string) $width ); ?> <?php echo esc_attr( (string) $height ); ?>" preserveAspectRatio="none" role="img" aria-label="<?php esc_attr_e( 'Daily completed chapters, last 30 days', 'tehillim-campaign-manager' ); ?>">
				<?php
				foreach ( $series as $i => $point ) {
					$h = $max > 0 ? ( $point['count'] / $max ) * ( $height - 4 ) : 0;
					$x = $i * ( $bar_w + $gap );
					$y = $height - $h;
					/* translators: 1: date, 2: chapter count. */
					$title = sprintf( __( '%1$s: %2$s', 'tehillim-campaign-manager' ), mysql2date( $date_fmt, $point['date'] ), number_format_i18n( $point['count'] ) );
					printf(
						'<rect x="%1$s" y="%2$s" width="%3$s" height="%4$s" rx="2" fill="%5$s"><title>%6$s</title></rect>',
						esc_attr( (string) round( $x, 2 ) ),
						esc_attr( (string) round( $y, 2 ) ),
						esc_attr( (string) round( $bar_w, 2 ) ),
						esc_attr( (string) round( $h, 2 ) ),
						esc_attr( $point['count'] > 0 ? '#c39a45' : '#e7e2d6' ),
						esc_html( $title )
					);
				}
				?>
			</svg>
		</div>
		<style>
			.tcm-trend{margin:0 0 24px}
			.tcm-trend h2{font-size:1rem;margin:0 0 8px}
			.tcm-trend-svg{width:100%;height:140px;background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:6px;box-sizing:border-box}
		</style>
		<?php
	}
}
