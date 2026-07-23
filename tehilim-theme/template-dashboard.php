<?php
/**
 * Template Name: Ambassador Dashboard
 * Description: Private ambassador dashboard
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();
?>

<main class="site-main">
	<div class="container">
		<header class="dashboard-header">
			<h1><?php esc_html_e( 'My Dashboard', 'tehilim' ); ?></h1>
		</header>

		<!-- Personal Stats -->
		<section class="dashboard-stats">
			<div class="stats-grid">
				<div class="stat-card">
					<h3><?php esc_html_e( 'Chapters Said', 'tehilim' ); ?></h3>
					<strong class="stat-value">0</strong>
				</div>
				<div class="stat-card">
					<h3><?php esc_html_e( 'Recruited Chapters', 'tehilim' ); ?></h3>
					<strong class="stat-value">0</strong>
				</div>
				<div class="stat-card">
					<h3><?php esc_html_e( 'Leaderboard Rank', 'tehilim' ); ?></h3>
					<strong class="stat-value">—</strong>
				</div>
			</div>
		</section>

		<!-- Personal Referral Link -->
		<section class="referral-section">
			<h2><?php esc_html_e( 'Your Referral Links', 'tehilim' ); ?></h2>
			<div class="referral-card">
				<p><?php esc_html_e( 'Your campaigns will appear here...', 'tehilim' ); ?></p>
			</div>
		</section>

		<!-- Pending Campaigns -->
		<section class="pending-campaigns">
			<h2><?php esc_html_e( 'My Campaigns', 'tehilim' ); ?></h2>
			<div class="campaigns-list">
				<?php
				$campaigns = get_posts( array(
					'post_type'   => 'campaign',
					'post_status' => array( 'draft', 'publish' ),
					'author'      => get_current_user_id(),
					'numberposts' => 20,
				) );

				if ( $campaigns ) {
					foreach ( $campaigns as $campaign ) {
						?>
						<div class="campaign-item">
							<h3><?php echo esc_html( $campaign->post_title ); ?></h3>
							<a href="<?php echo esc_url( get_permalink( $campaign->ID ) ); ?>" class="btn btn-link">
								<?php esc_html_e( 'View Campaign', 'tehilim' ); ?>
							</a>
						</div>
						<?php
					}
				} else {
					?>
					<p><?php esc_html_e( 'You haven\'t created any campaigns yet.', 'tehilim' ); ?></p>
					<?php
				}
				?>
			</div>
		</section>
	</div>
</main>

<?php
get_footer();
