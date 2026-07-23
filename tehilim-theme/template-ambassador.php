<?php
/**
 * Template Name: Ambassador Referral
 * Description: Personalized ambassador referral page
 */
get_header();
?>

<main class="site-main">
	<?php
	$ambassador_name = get_query_var( 'ambassador' );
	$campaign_name = get_query_var( 'name' );

	if ( $campaign_name && $ambassador_name ) {
		$campaign = get_posts( array(
			'post_type'  => 'campaign',
			'name'       => $campaign_name,
			'numberposts' => 1,
		) );

		$ambassador = get_posts( array(
			'post_type'  => 'ambassador',
			'name'       => $ambassador_name,
			'numberposts' => 1,
		) );

		if ( $campaign && $ambassador ) {
			$campaign = $campaign[0];
			$ambassador = $ambassador[0];
			?>

			<!-- Hero Section -->
			<section class="ambassador-hero">
				<div class="container">
					<h1>
						<?php
						printf(
							esc_html__( 'You\'re invited by %s', 'tehilim' ),
							esc_html( $ambassador->post_title )
						);
						?>
					</h1>
					<p><?php echo esc_html( $campaign->post_title ); ?></p>
				</div>
			</section>

			<!-- Progress Card -->
			<section class="ambassador-progress">
				<div class="container">
					<div class="progress-card" data-campaign-id="<?php echo esc_attr( $campaign->ID ); ?>" data-ambassador-id="<?php echo esc_attr( $ambassador->ID ); ?>">
						<div class="ambassador-info">
							<h2><?php echo esc_html( $ambassador->post_title ); ?></h2>
						</div>
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
						</div>
					</div>
				</div>
			</section>

			<!-- Chapter Reader (same as single campaign) -->
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
								<button class="btn btn-say-chapter" data-campaign-id="<?php echo esc_attr( $campaign->ID ); ?>" data-ambassador-id="<?php echo esc_attr( $ambassador->ID ); ?>" data-chapter-number="1">
									<?php esc_html_e( 'I Said This', 'tehilim' ); ?>
								</button>
								<button class="btn btn-next"><?php esc_html_e( 'Next Chapter', 'tehilim' ); ?></button>
								<button class="btn btn-random"><?php esc_html_e( 'Random', 'tehilim' ); ?></button>
							</div>
						</div>

						<!-- Sidebar -->
						<aside class="reader-sidebar">
							<div class="share-card">
								<h3><?php esc_html_e( 'Share', 'tehilim' ); ?></h3>
								<button class="btn btn-share" data-share-type="whatsapp" data-share-url="<?php echo esc_url( get_permalink() ); ?>" data-share-text="<?php echo esc_attr( $ambassador->post_title . ' - ' . $campaign->post_title ); ?>">
									<?php esc_html_e( 'WhatsApp', 'tehilim' ); ?>
								</button>
								<button class="btn btn-share" data-share-type="copy" data-share-url="<?php echo esc_url( get_permalink() ); ?>">
									<?php esc_html_e( 'Copy Link', 'tehilim' ); ?>
								</button>
							</div>
						</aside>
					</div>
				</div>
			</section>

			<?php
		} else {
			?>
			<div class="container">
				<p><?php esc_html_e( 'Campaign or ambassador not found.', 'tehilim' ); ?></p>
			</div>
			<?php
		}
	} else {
		?>
		<div class="container">
			<p><?php esc_html_e( 'Invalid referral link.', 'tehilim' ); ?></p>
		</div>
		<?php
	}
	?>
</main>

<?php
get_footer();
