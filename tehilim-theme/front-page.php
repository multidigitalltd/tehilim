<?php
/**
 * Front Page - Tehilim Home (Hebrew RTL Design)
 */
get_header();
?>

<main class="site-main">
	<!-- HERO SECTION -->
	<section class="hero">
		<div class="hero-grid">
			<!-- Left: Text Content -->
			<div style="animation: floatIn .6s ease both;">
				<h1>מתאחדים סביב<br>קריאת תהילים משותפת<br><span class="highlight">לברכה, לרפואה ולישועה</span></h1>
				<p style="font-size: 18.5px; line-height: 1.65; color: #6B5D4C; max-width: 480px; margin: 0 0 32px;">בחרו מטרה, הזמינו שגרירים ועקבו בזמן אמת אחרי כל פרק — עד שהקהילה כולה משלימה יחד אלפי פרקי תהילים.</p>

				<div style="display: flex; flex-wrap: wrap; gap: 13px; align-items: center; margin-bottom: 28px;">
					<a href="<?php echo esc_url( home_url( '/create/' ) ); ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 9px; padding: 16px 30px; border-radius: 15px; font-size: 16.5px; font-weight: 800;">
						צרו קמפיין
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFF7F2" stroke-width="2.4" stroke-linecap="round"><path d="M19 12H5M11 18l-6-6 6-6"></path></svg>
					</a>
					<a href="<?php echo esc_url( home_url( '/campaigns/' ) ); ?>" class="btn btn-secondary" style="padding: 15px 26px; border-radius: 15px;">גלו קמפיינים</a>
				</div>

				<!-- Social Proof -->
				<div style="display: flex; align-items: center; gap: 12px;">
					<div style="display: flex; align-items: center;">
						<div style="z-index: 4; width: 33px; height: 33px; border-radius: 50%; background: linear-gradient(135deg, #C05A3A, #A94B2E); border: 2px solid #FBEFDF; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 12px;">ר</div>
						<div style="margin-right: -10px; width: 33px; height: 33px; border-radius: 50%; background: linear-gradient(135deg, #D9A441, #B9822B); border: 2px solid #FBEFDF; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 12px;">ש</div>
						<div style="margin-right: -10px; width: 33px; height: 33px; border-radius: 50%; background: #8A6B4A; border: 2px solid #FBEFDF; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 12px;">י</div>
						<div style="margin-right: -10px; width: 33px; height: 33px; border-radius: 50%; background: #EFE0C6; border: 2px solid #FBEFDF; display: flex; align-items: center; justify-content: center; color: #A9773A; font-weight: 800; font-size: 11px;">+83</div>
					</div>
					<div style="font-size: 14px; color: #7A6B58;">כבר <b style="color: #3A2C1C;">4,280</b> אומרים תהילים ב־<b style="color: #3A2C1C;">128</b> קמפיינים</div>
				</div>
			</div>

			<!-- Right: Campaign Card Preview (Placeholder) -->
			<div style="position: relative; min-height: 400px; animation: floatIn .72s ease both; display: none;">
				<!-- Card will be shown on wider screens via CSS -->
			</div>
		</div>

		<!-- STATS STRIP -->
		<div class="stats-strip">
			<div class="stat">
				<div class="stat-value">4,280</div>
				<div class="stat-label">משתתפים</div>
			</div>
			<div class="stat-divider"></div>
			<div class="stat">
				<div class="stat-value">46,800</div>
				<div class="stat-label">פרקי תהילים נאמרו</div>
			</div>
			<div class="stat-divider"></div>
			<div class="stat">
				<div class="stat-value">312</div>
				<div class="stat-label">ספרים הושלמו</div>
			</div>
			<div class="stat-divider"></div>
			<div class="stat">
				<div class="stat-value">128</div>
				<div class="stat-label">קמפיינים פעילים</div>
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
