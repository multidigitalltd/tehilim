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
			<h2>כך זה עובד</h2>
			<div class="cards-grid">
				<div class="card">
					<h3>1. צרו</h3>
					<p>התחילו קמפיין למטרה או אדם בצורך. בחרו את הסיבה, קבעו מטרה והזמינו אחרים להשתתף.</p>
				</div>
				<div class="card">
					<h3>2. הזמינו</h3>
					<p>שתפו את הקישור האישי שלכם עם חברים וחברים בקהילה. עקבו בזמן אמת אחרי מי שהצטרף והתקדמות שלו.</p>
				</div>
				<div class="card">
					<h3>3. אמרו</h3>
					<p>אמרו פרקי תהילים ביחד במרחב זה. כל פרק מקרב את הקהילה כולה למטרה.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- Recent Campaigns -->
	<section class="recent-campaigns">
		<div class="container">
			<h2>קמפיינים פעילים</h2>
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
					echo '<p>אין קמפיינים עדיין. בואו להיות הראשונים ליצור!</p>';
				}
				?>
			</div>
			<div style="text-align: center; margin-top: 2rem;">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>" class="btn btn-secondary">
					צפו בכל הקמפיינים
				</a>
			</div>
		</div>
	</section>

	<!-- FAQ -->
	<section class="faq">
		<div class="container">
			<h2>שאלות נפוצות</h2>
			<details class="faq-item">
				<summary>מה זה תהילים?</summary>
				<p>תהילים היא פלטפורמה קהילתית לאמירת תהילים ביחד למטרות משמעותיות.</p>
			</details>
			<details class="faq-item">
				<summary>איך אני יוצר קמפיין?</summary>
				<p>בואו לעמוד ההיצור שלנו, מלאו את הפרטים, ושתפו את הקישור האישי שלכם עם אחרים.</p>
			</details>
			<details class="faq-item">
				<summary>האם אני יכול לעקוב אחרי התקדמות?</summary>
				<p>בטח! כל קמפיין מציג התקדמות בזמן אמת כשפרקים משלימים.</p>
			</details>
		</div>
	</section>

	<!-- CTA Banner -->
	<section class="cta-banner">
		<div class="container">
			<h2>מוכנים להתחיל?</h2>
			<a href="<?php echo esc_url( home_url( '/create' ) ); ?>" class="btn btn-light">
				צרו קמפיין
			</a>
		</div>
	</section>
</main>

<?php
get_footer();
