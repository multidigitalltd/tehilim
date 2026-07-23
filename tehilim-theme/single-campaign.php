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
					<div class="progress-card" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>">
						<div class="campaign-stats">
							<div class="stat">
								<strong>0</strong>
								<span><?php esc_html_e( 'Books', 'tehilim' ); ?></span>
							</div>
							<div class="stat">
								<strong>0</strong>
								<span><?php esc_html_e( 'Chapters', 'tehilim' ); ?></span>
							</div>
							<div class="stat">
								<strong>0</strong>
								<span><?php esc_html_e( 'Participants', 'tehilim' ); ?></span>
							</div>
							<div class="stat">
								<strong>0</strong>
								<span><?php esc_html_e( 'Ambassadors', 'tehilim' ); ?></span>
							</div>
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
									<?php esc_html_e( 'Ambassadors will appear here...', 'tehilim' ); ?>
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
						<?php esc_html_e( 'Activity will load here...', 'tehilim' ); ?>
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
