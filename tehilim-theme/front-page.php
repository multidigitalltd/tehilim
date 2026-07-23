<?php
/**
 * Front Page Template - Tehilim Home
 * The main homepage with hero, campaigns, features, and CTA
 */

get_header();
?>

<main class="site-main page-anim">

	<!-- Hero Section -->
	<section class="hero-section">
		<svg class="hero-bg-circles" width="540" height="540" viewBox="0 0 540 540" fill="none">
			<circle cx="270" cy="270" r="118" stroke="#E7CBA6" stroke-width="1"></circle>
			<circle cx="270" cy="270" r="182" stroke="#EBD3B0" stroke-width="1"></circle>
			<circle cx="270" cy="270" r="248" stroke="#EFDCBE" stroke-width="1"></circle>
		</svg>

		<div class="hero-container">
			<!-- Left: Text Content -->
			<div class="hero-text-block">
				<h1>
					מתאחדים סביב<br>
					קריאת תהילים משותפת<br>
					<span style="color: #B24328;">לברכה, לרפואה ולישועה</span>
				</h1>
				<p>בחרו מטרה, הזמינו שגרירים ועקבו בזמן אמת אחרי כל פרק — עד שהקהילה כולה משלימה יחד אלפי פרקי תהילים.</p>

				<div class="hero-cta-group">
					<a href="<?php echo esc_url( home_url( '/create/' ) ); ?>" class="btn-hero-primary">
						צרו קמפיין
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFF7F2" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
							<path d="M19 12H5M11 18l-6-6 6-6"></path>
						</svg>
					</a>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>" class="btn-hero-secondary">
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
						כבר <strong data-site-stat="participants">4,280</strong> אומרים תהילים ב־<strong data-site-stat="campaigns">128</strong> קמפיינים
					</div>
				</div>
			</div>

			<!-- Right: Hero Card -->
			<div class="hero-card">
				<div class="hero-card-blur"></div>

				<div class="hero-card-content">
					<!-- Card Header -->
					<div class="card-header">
						<div class="card-meta">
							<div class="card-avatar">מ</div>
							<div>
								<div class="card-title">משה בן חיה</div>
								<div class="card-subtitle">קמפיין לזיווג הגון</div>
							</div>
						</div>
						<div class="status-badge">
							<span style="width: 6px; height: 6px; border-radius: 50%; background: #4E8B5E; display: block;"></span>
							שידור חי
						</div>
					</div>

					<!-- Card Body -->
					<div style="display: flex; align-items: center; gap: 20px; margin-bottom: 18px;">
						<div class="progress-circle">
							<div class="progress-circle-inner">
								<div class="progress-percent">30%</div>
								<div class="progress-label">מהיעד</div>
							</div>
						</div>
						<div style="flex: 1; display: flex; flex-direction: column; gap: 11px;">
							<div style="display: flex; align-items: center; justify-content: space-between;">
								<span style="font-size: 13px; color: #6B5D4C; font-weight: 600;">ספרים</span>
								<span style="font-weight: 800; font-size: 14px; color: #2E2318;">3 / 10</span>
							</div>
							<div style="height: 1px; background: #F0E7D6;"></div>
							<div style="display: flex; align-items: center; justify-content: space-between;">
								<span style="font-size: 13px; color: #6B5D4C; font-weight: 600;">פרקים נאמרו</span>
								<span style="font-weight: 800; font-size: 14px; color: #2E2318;">46,800</span>
							</div>
							<div style="height: 1px; background: #F0E7D6;"></div>
							<div style="display: flex; align-items: center; justify-content: space-between;">
								<span style="font-size: 13px; color: #6B5D4C; font-weight: 600;">משתתפים</span>
								<span style="font-weight: 800; font-size: 14px; color: #2E2318;">87</span>
							</div>
						</div>
					</div>

					<!-- Card Footer -->
					<div style="border-top: 1px solid #F0E7D6; padding-top: 16px; display: flex; align-items: center; justify-content: space-between;">
						<div style="display: flex; align-items: center;">
							<div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #C05A3A, #A94B2E); border: 2px solid #fff; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 11px;">ר</div>
							<div style="margin-right: -8px; width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #D9A441, #B9822B); border: 2px solid #fff; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 11px;">ש</div>
							<div style="margin-right: -8px; width: 28px; height: 28px; border-radius: 50%; background: #8A6B4A; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 11px;">י</div>
							<div style="margin-right: -8px; width: 28px; height: 28px; border-radius: 50%; background: #EFE0C6; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; color: #A9773A; font-weight: 800; font-size: 10px;">+4</div>
						</div>
						<div style="font-size: 12.5px; color: #7A6B58; font-weight: 600;">7 שגרירים פעילים</div>
					</div>
				</div>

				<!-- Floating Badges -->
				<div class="card-badge top">
					<div class="badge-icon">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4E8B5E" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
							<path d="M5 13l4 4L19 7"></path>
						</svg>
					</div>
					<div>
						<div class="badge-title">פרק ק״ג הושלם</div>
						<div class="badge-subtitle">לפני רגע · שירה</div>
					</div>
				</div>

		</div>
		</div>

		<!-- Stats Bar -->
		<div class="stats-bar">
			<div class="stat-item">
				<div class="stat-value" data-site-stat="participants">4,280</div>
				<div class="stat-label">משתתפים</div>
			</div>
			<div class="stat-divider"></div>
			<div class="stat-item">
				<div class="stat-value" data-site-stat="chapters">46,800</div>
				<div class="stat-label">פרקי תהילים נאמרו</div>
			</div>
			<div class="stat-divider"></div>
			<div class="stat-item">
				<div class="stat-value" data-site-stat="books">312</div>
				<div class="stat-label">ספרים הושלמו</div>
			</div>
			<div class="stat-divider"></div>
			<div class="stat-item">
				<div class="stat-value" data-site-stat="campaigns">128</div>
				<div class="stat-label">קמפיינים</div>
			</div>
		</div>
	</section>

	<!-- Recent Campaigns Section -->
	<section class="section">
		<div class="section-header">
			<h2>קמפיינים שעלו לאחרונה</h2>
			<p>הצטרפו לקהילות שכבר התחילו לומר תהילים יחד.</p>
		</div>

		<div class="campaigns-grid">
			<?php
			$recent_campaigns = get_posts( array(
				'post_type'      => 'campaign',
				'posts_per_page' => 3,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			) );

			if ( $recent_campaigns ) :
				foreach ( $recent_campaigns as $post ) :
					setup_postdata( $post );
					get_template_part( 'template-parts/campaign-card' );
				endforeach;
				wp_reset_postdata();
			else :
				echo '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><p>אין קמפיינים עדיין. בואו להיות הראשונים!</p></div>';
			endif;
			?>
		</div>

		<div style="text-align: center;">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>" class="btn-all-campaigns">
				לכל הקמפיינים
			</a>
		</div>
	</section>

	<!-- How It Works Section -->
	<section class="how-it-works" id="how-it-works">
		<div class="section-header">
			<h2>איך זה עובד?</h2>
			<p>שלוש פעולות פשוטות. כל השאר מתגלגל מעצמו.</p>
		</div>

		<div class="steps-grid">
			<div class="step-card">
				<div class="step-number">01</div>
				<div class="step-icon">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2" stroke-linecap="round">
						<path d="M12 5v14M5 12h14"></path>
					</svg>
				</div>
				<h3>פותחים קמפיין</h3>
				<p>שם, מטרה ויעד. שתי דקות והקמפיין באוויר.</p>
			</div>

			<div class="step-card">
				<div class="step-number">02</div>
				<div class="step-icon">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2">
						<circle cx="9" cy="8" r="3"></circle>
						<path d="M3 19c0-3 2.7-5.5 6-5.5S15 16 15 19M17 5a3 3 0 0 1 0 6M21 19c0-2.3-1.4-4.3-3.5-5"></path>
					</svg>
				</div>
				<h3>מזמינים שגרירים</h3>
				<p>כל שגריר מקבל קישור אישי ומגייס את הסביבה שלו.</p>
			</div>

			<div class="step-card">
				<div class="step-number">03</div>
				<div class="step-icon">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M4 18l5-5 3 3 7-8"></path>
						<path d="M16 8h4v4"></path>
					</svg>
				</div>
				<h3>עוקבים בזמן אמת</h3>
				<p>מד ההתקדמות חי, פיד פעילות, ולוח שגרירים שמדרבן.</p>
			</div>
		</div>
	</section>

	<!-- Features Section -->
	<section class="features">
		<div class="section-header">
			<h2>כל מה שצריך לקמפיין מנצח</h2>
		</div>

		<div class="features-grid">
			<div class="feature-item">
				<div class="feature-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
						<path d="M4 19V5M4 19h16M8 16v-4M12 16V8M16 16v-6"></path>
					</svg>
				</div>
				<h3>התקדמות בזמן אמת</h3>
				<p>מד גדול, ספירת פרקים וספרים שמתעדכנת בכל רגע.</p>
			</div>

			<div class="feature-item">
				<div class="feature-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9">
						<path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5M12 6v13"></path>
					</svg>
				</div>
				<h3>ספר תהילים מלא</h3>
				<p>טקסט מלא ומנוקד, זמין לכל משתתף ישירות בעמוד.</p>
			</div>

			<div class="feature-item">
				<div class="feature-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9">
						<circle cx="9" cy="8" r="3"></circle>
						<path d="M3 19c0-3 2.7-5.5 6-5.5S15 16 15 19M17 5a3 3 0 0 1 0 6M21 19c0-2.3-1.4-4.3-3.5-5"></path>
					</svg>
				</div>
				<h3>מערכת שגרירים</h3>
				<p>כל שגריר עם קישור אישי, יעד ומיני-דשבורד משלו.</p>
			</div>

			<div class="feature-item">
				<div class="feature-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9">
						<circle cx="12" cy="12" r="9"></circle>
						<circle cx="12" cy="12" r="5"></circle>
						<circle cx="12" cy="12" r="1.4" fill="#A94B2E"></circle>
					</svg>
				</div>
				<h3>קמפיין לכל מטרה</h3>
				<p>רפואה, ישועה, זיווג, פרנסה, עילוי נשמה ועוד.</p>
			</div>

			<div class="feature-item">
				<div class="feature-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
						<path d="M3 12h4l2-6 4 12 2-6h6"></path>
					</svg>
				</div>
				<h3>סטטיסטיקות חיות</h3>
				<p>מי אמר, מתי וכמה — הכל שקוף ומעודכן.</p>
			</div>

			<div class="feature-item">
				<div class="feature-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9" stroke-linejoin="round">
						<circle cx="12" cy="9" r="5"></circle>
						<path d="M9 13l-2 8 5-3 5 3-2-8"></path>
					</svg>
				</div>
				<h3>תגי הישג</h3>
				<p>פרק ראשון, ספר שלם, השלמת יעד — מדליות שמעודדות.</p>
			</div>

			<div class="feature-item">
				<div class="feature-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9">
						<circle cx="7" cy="9" r="2.4"></circle>
						<circle cx="17" cy="9" r="2.4"></circle>
						<path d="M3 18c0-2.3 1.8-4 4-4s4 1.7 4 4M13 18c0-2.3 1.8-4 4-4s4 1.7 4 4"></path>
					</svg>
				</div>
				<h3>השתתפות קבוצתית</h3>
				<p>משפחות, בתי כנסת וקהילות מתאחדים סביב יעד אחד.</p>
			</div>

			<div class="feature-item">
				<div class="feature-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
						<path d="M12 20a8 8 0 1 0-6.9-4L4 20l4-1.1A8 8 0 0 0 12 20z"></path>
					</svg>
				</div>
				<h3>שיתוף בוואטסאפ</h3>
				<p>כפתור אחד, והקמפיין מגיע לכל קבוצה וכל איש קשר.</p>
			</div>
		</div>
	</section>

	<!-- Testimonials Section -->
	<section class="testimonials">
		<div class="section-header">
			<div class="testimonials-header">דברי חכמינו ז״ל</div>
			<h2>מעלת אמירת תהילים יחד!</h2>
			<p>מדברי חז״ל וגדולי ישראל בשבח מעלת אמירת התהילים.</p>
		</div>

		<div class="testimonials-grid">
			<?php foreach ( tehilim_get_home_quotes() as $tehilim_quote ) :
				$tehilim_q_initial = function_exists( 'mb_substr' ) ? mb_substr( $tehilim_quote['name'], 0, 1, 'UTF-8' ) : substr( $tehilim_quote['name'], 0, 1 );
				?>
				<div class="testimonial-card">
					<div class="quote-mark">”</div>
					<p class="testimonial-text"><?php echo esc_html( $tehilim_quote['text'] ); ?></p>
					<?php if ( ! empty( $tehilim_quote['name'] ) ) : ?>
						<div class="testimonial-author">
							<div class="author-avatar"><?php echo esc_html( $tehilim_q_initial ); ?></div>
							<div>
								<div class="author-name"><?php echo esc_html( $tehilim_quote['name'] ); ?></div>
								<?php if ( ! empty( $tehilim_quote['title'] ) ) : ?>
									<div class="author-title"><?php echo esc_html( $tehilim_quote['title'] ); ?></div>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="carousel-buttons">
			<button class="carousel-btn">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8A6B4A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M15 6l-6 6 6 6"></path>
				</svg>
			</button>
			<button class="carousel-btn">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8A6B4A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M9 6l6 6-6 6"></path>
				</svg>
			</button>
		</div>
	</section>

	<!-- FAQ Section -->
	<section class="faq-section" id="faq">
		<div class="faq-badge">שאלות ותשובות</div>
		<h2>שאלות נפוצות</h2>
		<p>כל מה שצריך לדעת לפני שפותחים קמפיין.</p>

		<div class="faq-list">
			<div class="faq-item">
				<button class="faq-question">
					<span class="faq-number">01</span>
					<span class="faq-text">האם השימוש בפלטפורמה כרוך בתשלום?</span>
					<span class="faq-toggle">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M6 9l6 6 6-6"></path>
						</svg>
					</span>
				</button>
				<div class="faq-answer">
					לא. הפלטפורמה חינמית לחלוטין, ללא מטרות רווח — נבנתה כדי לאפשר לכל אחד לאחד אנשים סביב תהילים.
				</div>
			</div>

			<div class="faq-item">
				<button class="faq-question">
					<span class="faq-number">02</span>
					<span class="faq-text">האם זה עוד אתר לקריאת תהילים?</span>
					<span class="faq-toggle">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M6 9l6 6 6-6"></path>
						</svg>
					</span>
				</button>
				<div class="faq-answer">
					לא. זו פלטפורמה קהילתית: פותחים קמפיין סביב מטרה, מזמינים שגרירים, וכל הקהילה אומרת יחד — עם מעקב חי אחרי כל פרק, לוח שגרירים ופיד פעילות.
				</div>
			</div>

			<div class="faq-item">
				<button class="faq-question">
					<span class="faq-number">03</span>
					<span class="faq-text">איך נספרים הפרקים?</span>
					<span class="faq-toggle">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M6 9l6 6 6-6"></path>
						</svg>
					</span>
				</button>
				<div class="faq-answer">
					בכל לחיצה על "סמן שקראתי" נרשם פרק אחד לקמפיין. ספר תהילים נחשב מושלם רק כשכל 150 הפרקים נאמרו — ואז מתחיל ספר חדש, עד השלמת היעד.
				</div>
			</div>

			<div class="faq-item">
				<button class="faq-question">
					<span class="faq-number">04</span>
					<span class="faq-text">מהו תפקיד השגריר?</span>
					<span class="faq-toggle">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M6 9l6 6 6-6"></path>
						</svg>
					</span>
				</button>
				<div class="faq-answer">
					שגריר מקבל עמוד אישי וקישור משלו, משתף אותם עם הסביבה שלו — וכל פרק שנאמר דרך הקישור נזקף לזכותו בלוח השגרירים של הקמפיין.
				</div>
			</div>

			<div class="faq-item">
				<button class="faq-question">
					<span class="faq-number">05</span>
					<span class="faq-text">האם ניתן לשתף את הקמפיין?</span>
					<span class="faq-toggle">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M6 9l6 6 6-6"></path>
						</svg>
					</span>
				</button>
				<div class="faq-answer">
					כמובן. בכל קמפיין יש כפתור שיתוף ל-WhatsApp והעתקת קישור בלחיצה אחת — לקבוצות, לסטטוס ולכל מקום שתרצו.
				</div>
			</div>

			<div class="faq-item">
				<button class="faq-question">
					<span class="faq-number">06</span>
					<span class="faq-text">כמה זמן לוקח לפתוח קמפיין?</span>
					<span class="faq-toggle">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M6 9l6 6 6-6"></path>
						</svg>
					</span>
				</button>
				<div class="faq-answer">
					פחות משתי דקות: בוחרים מטרה, כותבים למי מוקדש, קובעים יעד — והקמפיין באוויר ומוכן לשיתוף.
				</div>
			</div>

			<div class="faq-item">
				<button class="faq-question">
					<span class="faq-number">07</span>
					<span class="faq-text">האם הנתונים שלי מאובטחים?</span>
					<span class="faq-toggle">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M6 9l6 6 6-6"></path>
						</svg>
					</span>
				</button>
				<div class="faq-answer">
					כן. אנחנו שומרים רק את הפרטים הנחוצים לקמפיין, כתובות אימייל לעולם אינן מוצגות בפומבי, וכל התקשורת עם האתר מאובטחת.
				</div>
			</div>
		</div>
	</section>

	<!-- CTA Section -->
	<section class="cta-section">
		<div class="cta-container">
			<div class="cta-light-border"></div>
			<div class="cta-blur-circle"></div>

			<svg class="cta-bg-icon" width="360" height="360" viewBox="0 0 24 24" fill="none" stroke="#E9B75A" stroke-width="0.6">
				<path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5M12 6v13"></path>
			</svg>

			<div class="cta-content">
				<!-- Left Column -->
				<div class="cta-text">
					<div class="cta-badge">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="#EFC978">
							<path d="M12 2l2.4 5.6L20 8l-4.4 4 1.3 6L12 15l-4.9 3 1.3-6L4 8l5.6-.4z"></path>
						</svg>
						חינם · ללא הרשמה · תוך דקות
					</div>
					<h2>צרו קמפיין תהילים <span style="color: #E9B75A;">משלכם</span></h2>
					<p>אחדו סביבכם משפחה, חברים וקהילה סביב יעד תהילים משותף — בחרו מטרה, הזמינו שגרירים, ועקבו אחרי כל פרק בזמן אמת.</p>

					<div style="display: flex; flex-wrap: wrap; gap: 13px; align-items: center;">
						<a href="<?php echo esc_url( home_url( '/create/' ) ); ?>" class="btn-cta-primary">
							צרו קמפיין עכשיו
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2A1B0C" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
								<path d="M19 12H5M11 18l-6-6 6-6"></path>
							</svg>
						</a>
						<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>" class="btn-cta-secondary">
							גלו קמפיינים קיימים
						</a>
					</div>

					<div style="display: flex; align-items: center; gap: 13px; margin-top: 28px;">
						<div class="cta-avatars">
							<div class="cta-avatar cta-avatar-1">ר</div>
							<div class="cta-avatar cta-avatar-2">ש</div>
							<div class="cta-avatar cta-avatar-3">י</div>
							<div class="cta-avatar cta-avatar-4">+</div>
						</div>
						<div class="cta-stats">
							<strong data-site-stat="participants">4,280</strong> אומרים תהילים ב־<strong data-site-stat="campaigns">128</strong> קמפיינים פעילים
						</div>
					</div>
				</div>

				<!-- Right Column -->
				<div class="cta-card-group">
					<div class="cta-card-bg"></div>

					<div class="cta-card">
						<div class="cta-card-header">
							<div class="cta-card-avatar">מ</div>
							<div>
								<div class="cta-card-name">משה בן חיה</div>
								<div class="cta-card-occasion">לזיווג הגון</div>
							</div>
						</div>

						<div class="cta-card-progress">
							<div class="cta-progress-circle">
								<div class="cta-progress-inner">
									<div class="cta-progress-percent">30%</div>
								</div>
							</div>
							<div style="flex: 1;">
								<div class="cta-progress-bar">
									<div class="cta-progress-fill"></div>
								</div>
								<div class="cta-progress-info">3 מתוך 10 ספרים · 87 משתתפים</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
