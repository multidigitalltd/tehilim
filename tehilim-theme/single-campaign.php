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
								<span>ספרים</span>
							</div>
							<div class="stat">
								<strong><?php echo esc_html( $progress['chapters_done'] ); ?></strong>
								<span>פרקים</span>
							</div>
							<div class="stat">
								<strong><?php echo esc_html( $progress['total_chapters'] ); ?></strong>
								<span>סה״כ אמורים</span>
							</div>
							<div class="stat">
								<strong><?php echo esc_html( $progress['goal_books'] ); ?></strong>
								<span>מטרה</span>
							</div>
						</div>
						<div style="margin-top: 1rem;">
							<div class="progress-bar" style="height: 8px;">
								<div class="progress-fill" style="width: <?php echo esc_attr( $progress['progress_percent'] ); ?>%;"></div>
							</div>
							<p style="text-align: center; margin-top: 0.5rem; color: var(--text-muted);">
								<?php echo esc_html( $progress['progress_percent'] ); ?>% בוצע
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
								<h2 class="chapter-title">תהילים א</h2>
								<div class="chapter-text">
									טקסט הפרק יטען כאן...
								</div>
							</div>

							<div class="reader-controls">
								<button class="btn btn-say-chapter" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>" data-chapter-number="1">
									אמרתי זאת
								</button>
								<button class="btn btn-next">
									פרק הבא
								</button>
								<button class="btn btn-random">
									אקראי
								</button>
							</div>
						</div>

						<!-- Sidebar -->
						<aside class="reader-sidebar">
							<!-- Share Card -->
							<div class="share-card">
								<h3>שתפו</h3>
								<button class="btn btn-share" data-share-type="whatsapp" data-share-url="<?php echo esc_url( get_permalink() ); ?>" data-share-text="<?php echo esc_attr( get_the_title() ); ?>">
									WhatsApp
								</button>
								<button class="btn btn-share" data-share-type="copy" data-share-url="<?php echo esc_url( get_permalink() ); ?>">
									העתיקו קישור
								</button>
							</div>

							<!-- Leaderboard -->
							<div class="leaderboard">
								<h3>שגרירים מובילים</h3>
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
										echo '<p style="color: var(--text-muted);">אין שגרירים עדיין.</p>';
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
					<h2>פעילות אחרונה</h2>
					<div class="activity-feed">
						<?php
						$recitations = tehilim_get_recent_recitations( $campaign_id, 10 );
						if ( $recitations ) {
							foreach ( $recitations as $rec ) {
								$time_ago = human_time_diff( strtotime( $rec->created_at ), current_time( 'mysql' ) );
								?>
								<div style="padding: 1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
									<div>
										<strong><?php echo esc_html( $rec->reciter_name ?: 'אנונימי' ); ?></strong>
										<span style="color: var(--text-muted);"> אמר תהילים <?php echo esc_html( $rec->chapter_number ); ?></span>
									</div>
									<span style="color: var(--text-muted); font-size: 0.85rem;"><?php echo esc_html( $time_ago ); ?> לפני</span>
								</div>
								<?php
							}
						} else {
							echo '<p style="color: var(--text-muted); padding: 1rem;">אין פעילות עדיין. בואו להיות הראשונים לומר פרק!</p>';
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
