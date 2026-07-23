<?php
/**
 * Front Page - Tehilim Home (Hebrew RTL Design)
 */
get_header();
?>

<main class="site-main">
	<!-- HERO SECTION -->
	<section class="hero">
		<div class="container">
			<div class="hero-content">
				<div class="hero-text">
					<h1>מתאחדים סביב<br>קריאת תהילים משותפת<br><span style="color: var(--primary);">לברכה, לרפואה ולישועה</span></h1>
					<p class="hero-subtitle">בחרו מטרה, הזמינו שגרירים ועקבו בזמן אמת אחרי כל פרק — עד שהקהילה כולה משלימה יחד אלפי פרקי תהילים.</p>

					<div class="hero-cta">
						<a href="<?php echo esc_url( home_url( '/create/' ) ); ?>" class="btn btn-primary">
							צרו קמפיין
						</a>
						<a href="<?php echo esc_url( home_url( '/campaigns/' ) ); ?>" class="btn btn-secondary">
							גלו קמפיינים
						</a>
					</div>

					<!-- Social Proof -->
					<div class="social-proof">
						<div class="avatars">
							<div class="avatar avatar-1">ר</div>
							<div class="avatar avatar-2">ש</div>
							<div class="avatar avatar-3">י</div>
							<div class="avatar avatar-count">+83</div>
						</div>
						<div class="proof-text">
							כבר <strong>4,280</strong> אומרים תהילים ב־<strong>128</strong> קמפיינים
						</div>
					</div>
				</div>

				<!-- Stats Strip (inside hero) -->
				<div class="stats-strip">
					<div class="stat">
						<div class="stat-value">4,280</div>
						<div class="stat-label">משתתפים</div>
					</div>
					<div class="stat-divider"></div>
					<div class="stat">
						<div class="stat-value">46,800</div>
						<div class="stat-label">פרקי תהילים</div>
					</div>
					<div class="stat-divider"></div>
					<div class="stat">
						<div class="stat-value">312</div>
						<div class="stat-label">ספרים הושלמו</div>
					</div>
					<div class="stat-divider"></div>
					<div class="stat">
						<div class="stat-value">128</div>
						<div class="stat-label">קמפיינים</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- How It Works Section -->
	<section class="how-it-works">
		<div class="container">
			<div class="section-header">
				<h2>כך זה עובד</h2>
				<p>שלושה שלבים פשוטים להתחיל קמפיין משלכם</p>
			</div>

			<div class="steps-grid">
				<div class="step-card">
					<div class="step-number">1</div>
					<h3>צרו קמפיין</h3>
					<p>בחרו מטרה, הגדירו סיבה וקבעו מטרה כמה ספרים תהילים תרצו להשלים.</p>
				</div>
				<div class="step-card">
					<div class="step-number">2</div>
					<h3>הזמינו שגרירים</h3>
					<p>שתפו את הקישור האישי שלכם. כל שגריר מקבל קישור אישי להצטרפות ועקיבה.</p>
				</div>
				<div class="step-card">
					<div class="step-number">3</div>
					<h3>אמרו פרקים</h3>
					<p>אמרו פרקי תהילים וצפו כיצד הקמפיין מקדם בזמן אמת עם העדכונים.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- Features Section -->
	<section class="features">
		<div class="container">
			<div class="section-header">
				<h2>למה בחרים בתהילים</h2>
				<p>פלטפורמה מקיפה למטרות ביחד</p>
			</div>

			<div class="features-grid">
				<div class="feature-item">
					<div class="feature-icon">📊</div>
					<h3>עקיבה בזמן אמת</h3>
					<p>צפו בהתקדמות הקמפיין בזמן אמת עם עדכונים מיידיים.</p>
				</div>
				<div class="feature-item">
					<div class="feature-icon">👥</div>
					<h3>הנהלת שגרירים</h3>
					<p>הזמינו שגרירים עם קישורים אישיים לעקיבה והנהלה.</p>
				</div>
				<div class="feature-item">
					<div class="feature-icon">🏆</div>
					<h3>דירוג חברים</h3>
					<p>צפו בדירוג כי מי משתתפים הכי פעילים בקמפיין.</p>
				</div>
				<div class="feature-item">
					<div class="feature-icon">🔔</div>
					<h3>הודעות עדכון</h3>
					<p>קבלו הודעות על התקדמות וחברים חדשים.</p>
				</div>
				<div class="feature-item">
					<div class="feature-icon">🎯</div>
					<h3>מטרות ברורות</h3>
					<p>קבעו מטרות בהירות ועקבו בכל שלב של ההשלמה.</p>
				</div>
				<div class="feature-item">
					<div class="feature-icon">🔐</div>
					<h3>בטוח ופרטי</h3>
					<p>כל המידע שלכם מוגן בסטנדרטים גבוהים של אבטחה.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- Recent Campaigns Section -->
	<section class="recent-campaigns">
		<div class="container">
			<div class="section-header">
				<h2>קמפיינים פעילים</h2>
				<p>הצטרפו לקמפיין קיים או צרו שלכם</p>
			</div>

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
					echo '<div class="no-campaigns"><p>אין קמפיינים עדיין. בואו להיות הראשונים!</p></div>';
				}
				?>
			</div>

			<div class="section-cta">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>" class="btn btn-secondary btn-lg">
					צפו בכל הקמפיינים
				</a>
			</div>
		</div>
	</section>

	<!-- Testimonials Section -->
	<section class="testimonials">
		<div class="container">
			<div class="section-header">
				<h2>מה אומרים עליים</h2>
				<p>חוויות ממשות מהקהילה שלנו</p>
			</div>

			<div class="testimonials-grid">
				<div class="testimonial-card">
					<div class="testimonial-stars">⭐⭐⭐⭐⭐</div>
					<p class="testimonial-text">"פלטפורמה מדהימה לארגון קמפיינים קהילתיים. קל לשימוש וממש עזר לנו להשלים את מטרתנו!"</p>
					<p class="testimonial-author">— רחל כהן</p>
				</div>
				<div class="testimonial-card">
					<div class="testimonial-stars">⭐⭐⭐⭐⭐</div>
					<p class="testimonial-text">"כמעט לא כולם יודעים איך זה עובד, ופשוט. הממשק ברור מאוד ותומך בהתקדמות בזמן אמת."</p>
					<p class="testimonial-author">— דוד ברק</p>
				</div>
				<div class="testimonial-card">
					<div class="testimonial-stars">⭐⭐⭐⭐⭐</div>
					<p class="testimonial-text">"הצלחנו להשלים שלושה קמפיינים ברצף. תהילים הפכה לחלק חיוני מהקהילה שלנו."</p>
					<p class="testimonial-author">— שרה לוי</p>
				</div>
			</div>
		</div>
	</section>

	<!-- FAQ Section -->
	<section class="faq">
		<div class="container">
			<div class="section-header">
				<h2>שאלות נפוצות</h2>
				<p>תשובות לשאלות הנפוצות ביותר</p>
			</div>

			<div class="faq-grid">
				<details class="faq-item">
					<summary>מה זה תהילים בדיוק?</summary>
					<p>תהילים היא פלטפורמה קהילתית המאפשרת לך ליצור קמפיינים לאמירת תהילים ביחד עם קהילה. אתה יכול לבחור מטרה, להזמין שגרירים, ולעקוב אחרי התקדמות בזמן אמת.</p>
				</details>

				<details class="faq-item">
					<summary>איך אני יוצר קמפיין?</summary>
					<p>בואו לעמוד ההיצור שלנו, מלאו את הפרטים (מטרה, סיבה, שם), קבעו כמה ספרים תרצו להשלים, ושתפו את הקישור האישי שלכם עם אחרים כדי שהם יוכלו להשתתף.</p>
				</details>

				<details class="faq-item">
					<summary>האם יש עלות להשתמש בתהילים?</summary>
					<p>לא! תהילים הוא שירות חינם לחלוטין לכל אחד. אין דמי מנוי או הוצאות כלשהן.</p>
				</details>

				<details class="faq-item">
					<summary>איך נראה הקישור האישי שלי?</summary>
					<p>קישור אישי נראה כך: https://site.com/c/campaign-slug/ambassador-slug. תוכל לשתף את זה עם חברים כדי שהם יוכלו לחתום על הקמפיין שלך ולעקוב אחרי הערכים שהם אומרים.</p>
				</details>

				<details class="faq-item">
					<summary>האם אוכל לעקוב אחרי התקדמות?</summary>
					<p>כן! כל קמפיין מציג סטטיסטיקות בזמן אמת של כמה פרקים הושלמו, כמה שגרירים הצטרפו, ודירוג של המשתתפים הכי פעילים.</p>
				</details>

				<details class="faq-item">
					<summary>אני רוצה לערוך קמפיין קיים. האם זה אפשרי?</summary>
					<p>בהחלט! אתה יכול לערוך את פרטי הקמפיין שלך, לעדכן את המטרה, ולהוסיף או להסיר שגרירים לפי הצורך.</p>
				</details>

				<details class="faq-item">
					<summary>מה אם אני צריך עזרה?</summary>
					<p>אנחנו כאן כדי לעזור! אתה יכול לפנות אלינו דרך מסך ההוא. אנחנו תמיד שמחים לשמוע משוב והצעות.</p>
				</details>
			</div>
		</div>
	</section>

	<!-- CTA Banner -->
	<section class="cta-banner">
		<div class="container">
			<div class="cta-content">
				<h2>מוכנים להתחיל קמפיין?</h2>
				<p>צרו קמפיין היום והזמינו את הקהילה שלכם להשתתף</p>
				<a href="<?php echo esc_url( home_url( '/create/' ) ); ?>" class="btn btn-light btn-lg">
					צרו קמפיין עכשיו
				</a>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
