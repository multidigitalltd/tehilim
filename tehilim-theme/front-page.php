<?php
/**
 * Front Page (Homepage)
 */
get_header();
?>

<main class="site-main">
	<!-- Hero Section -->
	<section class="hero">
		<div class="container">
			<div class="hero-content">
				<h1><?php esc_html_e( 'Tehilim Together', 'tehilim' ); ?></h1>
				<p><?php esc_html_e( 'Join a community saying Psalms for meaningful causes', 'tehilim' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/campaigns' ) ); ?>" class="btn btn-primary">
					<?php esc_html_e( 'Explore Campaigns', 'tehilim' ); ?>
				</a>
			</div>
		</div>
	</section>

	<!-- Stats Bar -->
	<section class="stats-bar">
		<div class="container">
			<div class="stats-grid">
				<div class="stat-item">
					<strong class="stat-number">10,000+</strong>
					<span class="stat-label"><?php esc_html_e( 'Chapters Said', 'tehilim' ); ?></span>
				</div>
				<div class="stat-item">
					<strong class="stat-number">500+</strong>
					<span class="stat-label"><?php esc_html_e( 'Active Campaigns', 'tehilim' ); ?></span>
				</div>
				<div class="stat-item">
					<strong class="stat-number">2,000+</strong>
					<span class="stat-label"><?php esc_html_e( 'Community Members', 'tehilim' ); ?></span>
				</div>
				<div class="stat-item">
					<strong class="stat-number">50+</strong>
					<span class="stat-label"><?php esc_html_e( 'Causes Supported', 'tehilim' ); ?></span>
				</div>
			</div>
		</div>
	</section>

	<!-- How It Works -->
	<section class="how-it-works">
		<div class="container">
			<h2><?php esc_html_e( 'How It Works', 'tehilim' ); ?></h2>
			<div class="cards-grid">
				<div class="card">
					<h3>1. <?php esc_html_e( 'Create', 'tehilim' ); ?></h3>
					<p><?php esc_html_e( 'Start a campaign for a cause or person in need. Choose the occasion, set your goal, and invite others to participate.', 'tehilim' ); ?></p>
				</div>
				<div class="card">
					<h3>2. <?php esc_html_e( 'Invite', 'tehilim' ); ?></h3>
					<p><?php esc_html_e( 'Share your personal link with friends and community members. Track who has joined and their progress in real-time.', 'tehilim' ); ?></p>
				</div>
				<div class="card">
					<h3>3. <?php esc_html_e( 'Recite', 'tehilim' ); ?></h3>
					<p><?php esc_html_e( 'Say chapters of Tehilim together in this sacred space. Every chapter brings the community closer to the goal.', 'tehilim' ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<!-- Recent Campaigns -->
	<section class="recent-campaigns">
		<div class="container">
			<h2><?php esc_html_e( 'Active Campaigns', 'tehilim' ); ?></h2>
			<div class="campaigns-grid">
				<?php
				$recent = get_posts( array(
					'post_type'      => 'campaign',
					'posts_per_page' => 6,
					'post_status'    => 'publish',
					'orderby'        => 'date',
					'order'          => 'DESC',
				) );

				if ( $recent ) {
					foreach ( $recent as $post ) {
						setup_postdata( $post );
						get_template_part( 'template-parts/campaign-card' );
					}
					wp_reset_postdata();
				} else {
					echo '<p>' . esc_html__( 'No campaigns yet. Be the first to create one!', 'tehilim' ) . '</p>';
				}
				?>
			</div>
			<div style="text-align: center; margin-top: 2rem;">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>" class="btn btn-secondary">
					<?php esc_html_e( 'View All Campaigns', 'tehilim' ); ?>
				</a>
			</div>
		</div>
	</section>

	<!-- FAQ -->
	<section class="faq">
		<div class="container">
			<h2><?php esc_html_e( 'Frequently Asked Questions', 'tehilim' ); ?></h2>
			<details class="faq-item">
				<summary><?php esc_html_e( 'What is Tehilim?', 'tehilim' ); ?></summary>
				<p><?php esc_html_e( 'Tehilim is a community platform for saying Psalms together for meaningful causes.', 'tehilim' ); ?></p>
			</details>
			<details class="faq-item">
				<summary><?php esc_html_e( 'How do I create a campaign?', 'tehilim' ); ?></summary>
				<p><?php esc_html_e( 'Visit our create page, fill in the details, and share your personal link with others.', 'tehilim' ); ?></p>
			</details>
			<details class="faq-item">
				<summary><?php esc_html_e( 'Can I track progress?', 'tehilim' ); ?></summary>
				<p><?php esc_html_e( 'Yes! Each campaign shows real-time progress as chapters are completed.', 'tehilim' ); ?></p>
			</details>
		</div>
	</section>

	<!-- CTA Banner -->
	<section class="cta-banner">
		<div class="container">
			<h2><?php esc_html_e( 'Ready to Start?', 'tehilim' ); ?></h2>
			<a href="<?php echo esc_url( home_url( '/create' ) ); ?>" class="btn btn-light">
				<?php esc_html_e( 'Create Your Campaign', 'tehilim' ); ?>
			</a>
		</div>
	</section>
</main>

<?php
get_footer();
