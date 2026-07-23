<?php
/**
 * About Page — the platform's mission
 */
get_header();

$site_stats = function_exists( 'tehilim_get_site_stats' ) ? tehilim_get_site_stats() : null;
?>

<div class="about-page page-anim">

	<!-- Hero -->
	<section class="about-hero">
		<div class="about-hero-icon">
			<svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M12 6.4C10.2 5 7.4 4.5 4.6 5v12.3c2.8-.5 5.6 0 7.4 1.4 1.8-1.4 4.6-1.9 7.4-1.4V5c-2.8-.5-5.6 0-7.4 1.4Z" stroke="#FFF3E4" stroke-width="1.7" stroke-linejoin="round"></path><path d="M12 6.4v12.3" stroke="#F0CE7E" stroke-width="1.7" stroke-linecap="round"></path></svg>
		</div>
		<h1><?php esc_html_e( 'אודות הפלטפורמה', 'tehilim' ); ?></h1>
		<p class="about-hero-sub"><?php esc_html_e( 'בית דיגיטלי לאמירת תהילים משותפת — של עם ישראל, למען עם ישראל.', 'tehilim' ); ?></p>
	</section>

	<!-- Mission -->
	<section class="about-block">
		<h2><?php esc_html_e( 'למה הקמנו את האתר', 'tehilim' ); ?></h2>
		<p><?php esc_html_e( 'האתר הזה הוקם ללא שום מטרות רווח — לא פרסומות, לא תשלומים ולא מנויים. מטרה אחת בלבד עומדת מאחוריו: לאסוף זכויות לעם ישראל.', 'tehilim' ); ?></p>
		<p><?php esc_html_e( 'כשמישהו זקוק לרפואה, לישועה, לזיווג או לפרנסה — הדבר הטבעי ביותר בעם שלנו הוא לפתוח ספר תהילים. אבל כשקהילה שלמה מתאחדת סביב אותה מטרה, כל פרק מצטרף לפרק של מישהו אחר, ויחד נשלמים ספרים שלמים. זה הכוח שרצינו לתת לכל אחד ואחת — בלחיצת כפתור.', 'tehilim' ); ?></p>
	</section>

	<!-- Power of together -->
	<section class="about-block about-block-highlight">
		<h2><?php esc_html_e( 'הכוח של תהילים ביחד', 'tehilim' ); ?></h2>
		<p><?php esc_html_e( 'חז״ל לימדו אותנו שאין דבר שעומד בפני רחמי שמים כמו אמירת תהילים בציבור. המדרש אומר: "כל הקורא בספר תהילים — מעלה עליו הכתוב כאילו עוסק בכל התורה כולה". וכשאומרים יחד — כל פרק בודד הופך לחלק מספר שלם, וכל אומר יחיד הופך לחלק מציבור.', 'tehilim' ); ?></p>
		<p><?php esc_html_e( 'כאן כל פרק נספר, כל ספר שמושלם נזקף לזכות מי שהקמפיין הוקדש עבורו, וכל המשתתפים רואים יחד את התמונה הגדולה נבנית — פרק אחר פרק.', 'tehilim' ); ?></p>
	</section>

	<!-- How -->
	<section class="about-block">
		<h2><?php esc_html_e( 'איך זה עובד', 'tehilim' ); ?></h2>
		<p><?php esc_html_e( 'פותחים קמפיין לכל מטרה — רפואה, עליית נשמה, זיווג, פרנסה, זכות או אירוע. מזמינים שגרירים שמגייסים את הסביבה שלהם, וכל אחד שנכנס מקבל פרק פנוי מתוך הספר הנוכחי. המערכת עוקבת בזמן אמת: אילו פרקים נאמרו, כמה ספרים הושלמו, ומי השתתף.', 'tehilim' ); ?></p>
		<p><?php esc_html_e( 'הטקסט המלא והמנוקד של כל 150 פרקי התהילים זמין ישירות באתר — אפשר לומר מכל מקום, בכל רגע, גם מהנייד.', 'tehilim' ); ?></p>
	</section>

	<?php if ( $site_stats ) : ?>
	<!-- Live numbers -->
	<section class="about-stats">
		<div class="about-stat">
			<div class="about-stat-num" data-site-stat="participants"><?php echo esc_html( number_format_i18n( $site_stats['participants'] ) ); ?></div>
			<div class="about-stat-label"><?php esc_html_e( 'משתתפים', 'tehilim' ); ?></div>
		</div>
		<div class="about-stat">
			<div class="about-stat-num" data-site-stat="chapters"><?php echo esc_html( number_format_i18n( $site_stats['chapters'] ) ); ?></div>
			<div class="about-stat-label"><?php esc_html_e( 'פרקים נאמרו', 'tehilim' ); ?></div>
		</div>
		<div class="about-stat">
			<div class="about-stat-num" data-site-stat="books"><?php echo esc_html( number_format_i18n( $site_stats['books'] ) ); ?></div>
			<div class="about-stat-label"><?php esc_html_e( 'ספרים הושלמו', 'tehilim' ); ?></div>
		</div>
		<div class="about-stat">
			<div class="about-stat-num" data-site-stat="campaigns"><?php echo esc_html( number_format_i18n( $site_stats['campaigns'] ) ); ?></div>
			<div class="about-stat-label"><?php esc_html_e( 'קמפיינים', 'tehilim' ); ?></div>
		</div>
	</section>
	<?php endif; ?>

	<!-- CTA -->
	<section class="about-cta">
		<h2><?php esc_html_e( 'הצטרפו אלינו', 'tehilim' ); ?></h2>
		<p><?php esc_html_e( 'פתחו קמפיין למי שיקר לכם, או הצטרפו לקמפיין קיים ואמרו פרק — כי ביחד אפשר להפוך עולמות.', 'tehilim' ); ?></p>
		<div class="about-cta-actions">
			<a class="btn-create-primary" href="<?php echo esc_url( home_url( '/create/' ) ); ?>"><?php esc_html_e( 'פתיחת קמפיין', 'tehilim' ); ?></a>
			<a class="btn-account-secondary" href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>"><?php esc_html_e( 'גלו קמפיינים', 'tehilim' ); ?></a>
		</div>
	</section>
</div>

<?php
get_footer();
