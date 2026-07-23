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
			<h1>לוח הבקרה שלי</h1>
		</header>

		<!-- Personal Stats -->
		<section class="dashboard-stats">
			<div class="stats-grid">
				<div class="stat-card">
					<h3>פרקים שנאמרו</h3>
					<strong class="stat-value">0</strong>
				</div>
				<div class="stat-card">
					<h3>פרקים שגויסו</h3>
					<strong class="stat-value">0</strong>
				</div>
				<div class="stat-card">
					<h3>דירוג בטבלת הדירוגים</h3>
					<strong class="stat-value">—</strong>
				</div>
			</div>
		</section>

		<!-- Personal Referral Link -->
		<section class="referral-section">
			<h2>קישורי ההנחיה שלך</h2>
			<div class="referral-card">
				<p>הקמפיינים שלך יופיעו כאן...</p>
			</div>
		</section>

		<!-- Pending Campaigns -->
		<section class="pending-campaigns">
			<h2>הקמפיינים שלי</h2>
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
								<?php echo 'צפו בקמפיין'; ?>
							</a>
						</div>
						<?php
					}
				} else {
					?>
					<p><?php echo 'לא יצרתם קמפיינים עדיין.'; ?></p>
					<?php
				}
				?>
			</div>
		</section>
	</div>
</main>

<?php
get_footer();
