<?php
/**
 * Single Campaign Template
 */
get_header();
?>

<main class="site-main">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			$campaign_id = get_the_ID();
			?>

			<!-- Campaign Hero -->
			<section class="campaign-hero">
				<div class="campaign-hero-bg">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
				<div class="container">
					<div class="campaign-hero-content">
						<h1><?php the_title(); ?></h1>
						<?php if ( $description = get_the_content() ) : ?>
							<p><?php echo wp_kses_post( $description ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</section>

			<!-- Campaign Progress -->
			<section class="campaign-progress">
				<div class="container">
					<?php
					$progress = tehilim_get_campaign_progress( $campaign_id );
					?>
					<div class="progress-card" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>">
						<div class="campaign-stats">
							<div class="stat">
								<strong><?php echo esc_html( $progress['books_done'] ); ?></strong>
								<span><?php esc_html_e( 'Books', 'tehilim' ); ?></span>
							</div>
							<div class="stat">
								<strong><?php echo esc_html( $progress['chapters_done'] ); ?></strong>
								<span><?php esc_html_e( 'Chapters', 'tehilim' ); ?></span>
							</div>
							<div class="stat">
								<strong><?php echo esc_html( $progress['total_chapters'] ); ?></strong>
								<span><?php esc_html_e( 'Total Said', 'tehilim' ); ?></span>
							</div>
							<div class="stat">
								<strong><?php echo esc_html( $progress['goal_books'] ); ?></strong>
								<span><?php esc_html_e( 'Goal', 'tehilim' ); ?></span>
							</div>
						</div>
						<div style="margin-top: 1rem;">
							<div class="progress-bar" style="height: 8px;">
								<div class="progress-fill" style="width: <?php echo esc_attr( $progress['progress_percent'] ); ?>%;"></div>
							</div>
							<p style="text-align: center; margin-top: 0.5rem; color: var(--text-muted);">
								<?php echo esc_html( $progress['progress_percent'] ); ?>% <?php esc_html_e( 'Complete', 'tehilim' ); ?>
							</p>
						</div>
					</div>
				</div>
			</section>

			<!-- Chapter Reader -->
			<section class="reader-section">
				<div class="container">
					<div class="reader-wrapper">
						<div class="reader-main">
							<div class="chapter-display">
								<h2 class="chapter-title"><?php esc_html_e( 'Psalm 1', 'tehilim' ); ?></h2>
								<div class="chapter-text">
									<?php esc_html_e( 'Chapter text will load here...', 'tehilim' ); ?>
								</div>
							</div>

							<div class="reader-controls">
								<button class="btn btn-say-chapter" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>" data-chapter-number="1">
									<?php esc_html_e( 'I Said This', 'tehilim' ); ?>
								</button>
								<button class="btn btn-next">
									<?php esc_html_e( 'Next Chapter', 'tehilim' ); ?>
								</button>
								<button class="btn btn-random">
									<?php esc_html_e( 'Random', 'tehilim' ); ?>
								</button>
							</div>
						</div>

						<!-- Sidebar -->
						<aside class="reader-sidebar">
							<!-- Share Card -->
							<div class="share-card">
								<h3><?php esc_html_e( 'Share', 'tehilim' ); ?></h3>
								<button class="btn btn-share" data-share-type="whatsapp" data-share-url="<?php echo esc_url( get_permalink() ); ?>" data-share-text="<?php echo esc_attr( get_the_title() ); ?>">
									<?php esc_html_e( 'WhatsApp', 'tehilim' ); ?>
								</button>
								<button class="btn btn-share" data-share-type="copy" data-share-url="<?php echo esc_url( get_permalink() ); ?>">
									<?php esc_html_e( 'Copy Link', 'tehilim' ); ?>
								</button>
							</div>

							<!-- Leaderboard -->
							<div class="leaderboard">
								<h3><?php esc_html_e( 'Top Ambassadors', 'tehilim' ); ?></h3>
								<div class="leaderboard-list">
									<?php
									$ambassadors = tehilim_get_top_ambassadors( $campaign_id );
									if ( $ambassadors ) {
										foreach ( $ambassadors as $index => $ambassador ) {
											?>
											<div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between;">
												<span><strong>#<?php echo esc_html( $index + 1 ); ?></strong> <?php echo esc_html( $ambassador['name'] ); ?></span>
												<span style="color: var(--primary); font-weight: 500;"><?php echo esc_html( $ambassador['count'] ); ?></span>
											</div>
											<?php
										}
									} else {
										echo '<p style="color: var(--text-muted);">' . esc_html__( 'No ambassadors yet.', 'tehilim' ) . '</p>';
									}
									?>
								</div>
							</div>
						</aside>
					</div>
				</div>
			</section>

			<!-- Activity Feed -->
			<section class="activity-section">
				<div class="container">
					<h2><?php esc_html_e( 'Recent Activity', 'tehilim' ); ?></h2>
					<div class="activity-feed">
						<?php
						$recitations = tehilim_get_recent_recitations( $campaign_id, 10 );
						if ( $recitations ) {
							foreach ( $recitations as $rec ) {
								$time_ago = human_time_diff( strtotime( $rec->created_at ), current_time( 'mysql' ) );
								?>
								<div style="padding: 1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
									<div>
										<strong><?php echo esc_html( $rec->reciter_name ?: __( 'Anonymous', 'tehilim' ) ); ?></strong>
										<span style="color: var(--text-muted);"> <?php esc_html_e( 'said Psalm', 'tehilim' ); ?> <?php echo esc_html( $rec->chapter_number ); ?></span>
									</div>
									<span style="color: var(--text-muted); font-size: 0.85rem;"><?php echo esc_html( $time_ago ); ?> ago</span>
								</div>
								<?php
							}
						} else {
							echo '<p style="color: var(--text-muted); padding: 1rem;">' . esc_html__( 'No activity yet. Be the first to say a chapter!', 'tehilim' ) . '</p>';
						}
						?>
					</div>
				</div>
			</section>

			<?php
		}
	}
	?>
</main>

<?php
get_footer();
