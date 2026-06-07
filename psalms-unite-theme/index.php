<?php
/**
 * WordPress bridge for the original Psalms Unite design.
 *
 * @package Psalms_Unite
 */

$slug = '';
if ( is_page() ) {
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
}

if ( is_singular( 'tcm_campaign' ) ) {
	$slug = 'campaign_detail';
}

if ( is_post_type_archive( 'tcm_campaign' ) ) {
	$slug = 'campaigns';
}

if ( '' === $slug ) {
	$path = trim( (string) wp_parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
	$slug = '' === $path ? 'home' : sanitize_title( basename( $path ) );
}

if ( 'create-campaign' === $slug ) {
	$slug = 'create';
}

if ( in_array( $slug, array( 'my-campaigns', 'my-activity', 'ambassadors' ), true ) ) {
	$slug = 'dashboard';
}

if ( 'daily-tehillim' === $slug ) {
	$slug = 'subscribe';
}

$nav_items = array(
	'campaigns' => array( 'label' => 'קמפיינים', 'url' => home_url( '/campaigns/' ) ),
	'about'     => array( 'label' => 'אודות', 'url' => home_url( '/about/' ) ),
	'create'    => array( 'label' => 'יצירת קמפיין', 'url' => home_url( '/create/' ) ),
);

$button_class = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow-lg shadow-primary/20 transition-all hover:shadow-xl hover:shadow-primary/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';
$outline_class = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full border border-primary/20 bg-background/60 px-6 py-3 text-sm font-medium text-foreground backdrop-blur-sm transition-colors hover:bg-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';

?><!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-background text-foreground font-sans antialiased' ); ?>>
<?php wp_body_open(); ?>

<header class="sticky top-0 z-50 w-full border-b border-border/60 bg-background/80 backdrop-blur-md">
	<div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2 font-display text-xl font-bold">
			<span class="grid h-9 w-9 place-items-center rounded-xl bg-primary text-primary-foreground">ת</span>
			<span>קמפייני תהילים</span>
		</a>
		<nav class="hidden items-center gap-6 text-sm font-medium text-muted-foreground md:flex" aria-label="ניווט ראשי">
			<?php foreach ( $nav_items as $item_slug => $item ) : ?>
				<a class="transition-colors hover:text-foreground <?php echo esc_attr( $slug === $item_slug ? 'text-foreground' : '' ); ?>" href="<?php echo esc_url( $item['url'] ); ?>">
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			<?php endforeach; ?>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<a class="transition-colors hover:text-foreground" href="<?php echo esc_url( admin_url( 'edit.php?post_type=tcm_campaign&page=tcm-settings&tab=design' ) ); ?>">עיצוב ופונקציות</a>
			<?php endif; ?>
		</nav>
		<a class="rounded-full border border-border bg-background px-4 py-2 text-sm font-medium hover:bg-accent" href="<?php echo esc_url( home_url( '/auth/' ) ); ?>">התחברות</a>
	</div>
</header>

<main id="content">
	<?php if ( current_user_can( 'manage_options' ) && class_exists( '\TCM\Plugin' ) ) : ?>
		<div class="psalms-unite-admin-note">
			ניהול התוסף:
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tcm_campaign' ) ); ?>">קמפיינים</a>
			&nbsp;|&nbsp;
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tcm_campaign&page=tcm-settings' ) ); ?>">הגדרות פונקציות</a>
			&nbsp;|&nbsp;
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tcm_campaign&page=tcm-settings&tab=design' ) ); ?>">עיצוב ופונט של התוסף</a>
			&nbsp;|&nbsp;
			<a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=psalms_unite_typography' ) ); ?>">פונט התבנית</a>
		</div>
	<?php endif; ?>

	<?php if ( 'campaign_detail' === $slug ) : ?>
		<section class="mx-auto max-w-6xl px-4 py-10">
			<?php
			echo psalms_unite_shortcode(
				'tehillim_campaign',
				array( 'id' => get_queried_object_id() ),
				'<div class="rounded-xl border border-dashed border-border/60 bg-card p-12 text-center text-muted-foreground">יש להפעיל את התוסף Tehillim Campaign Manager כדי להציג קמפיין.</div>'
			);
			?>
		</section>
	<?php elseif ( 'campaigns' === $slug ) : ?>
		<section class="mx-auto max-w-6xl px-4 py-12">
			<div class="mx-auto max-w-2xl text-center">
				<h1 class="font-display text-4xl font-bold">קמפיינים</h1>
				<p class="mt-2 text-muted-foreground">כל הקמפיינים הפעילים במקום אחד.</p>
			</div>
			<div class="psalms-unite-shortcode mt-10">
				<?php
				echo psalms_unite_shortcode_slot(
					'campaigns',
					'<div class="rounded-xl border border-dashed border-border/60 bg-card p-12 text-center text-muted-foreground">כאן יופיע רכיב הקמפיינים מהתוסף לאחר חיבור shortcode מתאים.</div>'
				);
				?>
			</div>
		</section>
	<?php elseif ( 'create' === $slug ) : ?>
		<section class="mx-auto max-w-3xl px-4 py-12">
			<div class="mb-8 text-center">
				<h1 class="font-display text-4xl font-bold">יצירת קמפיין</h1>
				<p class="mt-2 text-muted-foreground">טופס יצירה מחובר לתוסף הפונקציות.</p>
			</div>
			<div class="psalms-unite-shortcode">
				<?php
				echo psalms_unite_shortcode_slot(
					'create',
					'<div class="rounded-xl border border-dashed border-border/60 bg-card p-12 text-center text-muted-foreground">כאן יופיע טופס יצירת הקמפיין מהתוסף.</div>'
				);
				?>
			</div>
		</section>
	<?php elseif ( 'dashboard' === $slug ) : ?>
		<section class="mx-auto max-w-5xl px-4 py-12">
			<h1 class="font-display text-4xl font-bold">אזור אישי</h1>
			<div class="psalms-unite-shortcode mt-8 grid gap-8">
				<?php
				echo psalms_unite_shortcode_slot( 'my_campaigns' );
				echo psalms_unite_shortcode_slot( 'my_activity' );
				echo psalms_unite_shortcode_slot( 'ambassadors' );
				?>
			</div>
		</section>
	<?php elseif ( 'auth' === $slug ) : ?>
		<section class="mx-auto max-w-md px-4 py-16">
			<div class="rounded-xl border border-border/60 bg-card p-8 shadow-sm">
				<h1 class="font-display text-3xl font-bold">התחברות</h1>
				<div class="psalms-unite-shortcode mt-6">
					<?php wp_login_form( array( 'redirect' => home_url( '/dashboard/' ) ) ); ?>
				</div>
			</div>
		</section>
	<?php elseif ( 'subscribe' === $slug ) : ?>
		<section class="mx-auto max-w-3xl px-4 py-12">
			<div class="mb-8 text-center">
				<h1 class="font-display text-4xl font-bold">תהילים יומי</h1>
				<p class="mt-2 text-muted-foreground">הרשמה לעדכונים ותזכורות מהתוסף.</p>
			</div>
			<div class="psalms-unite-shortcode">
				<?php echo psalms_unite_shortcode_slot( 'subscribe', '<div class="rounded-xl border border-dashed border-border/60 bg-card p-12 text-center text-muted-foreground">יש להפעיל את רכיב ההרשמה בתוסף.</div>' ); ?>
			</div>
		</section>
	<?php elseif ( 'about' === $slug ) : ?>
		<section class="mx-auto grid max-w-6xl gap-10 px-4 py-20 md:grid-cols-12">
			<div class="md:col-span-5">
				<span class="inline-flex items-center gap-2 rounded-full border border-gold/40 bg-gold/10 px-3 py-1 text-xs font-medium text-foreground">אודות</span>
				<h1 class="mt-4 font-display text-4xl font-bold md:text-5xl">פלטפורמה קהילתית לקריאת תהילים משותפת.</h1>
			</div>
			<div class="space-y-5 text-muted-foreground md:col-span-7">
				<p>האתר מאפשר לפתוח קמפיין, לחלק פרקים, להזמין משתתפים ולעקוב אחרי ההתקדמות.</p>
				<p>התבנית הזו שומרת על שפת העיצוב המקורית ומיועדת להתחבר לתוסף WordPress שמטפל בפונקציות.</p>
			</div>
		</section>
	<?php else : ?>
		<section class="gradient-warm relative overflow-hidden">
			<div aria-hidden="true" class="pointer-events-none absolute inset-0">
				<div class="absolute -top-24 start-1/4 h-72 w-72 rounded-full bg-gold/25 blur-3xl"></div>
				<div class="absolute -bottom-32 end-1/4 h-80 w-80 rounded-full bg-primary/20 blur-3xl"></div>
			</div>
			<div class="relative mx-auto max-w-6xl px-4 py-20 md:py-28">
				<div class="mx-auto max-w-3xl text-center">
					<span class="inline-flex items-center gap-2 rounded-full border border-gold/40 bg-gold/10 px-3 py-1 text-xs font-medium text-foreground shadow-sm backdrop-blur-sm">
						<span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-gold opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-gold"></span></span>
						קהילה אחת, ספר תהילים אחד
					</span>
					<h1 class="mt-6 font-display text-4xl font-bold text-balance md:text-6xl">מאחדים אנשים סביב יעד תהילים משותף</h1>
					<p class="mx-auto mt-5 max-w-2xl text-balance text-base text-muted-foreground md:text-lg">צרו קמפיין, הזמינו משתתפים, חלקו פרקים ועקבו אחרי ההתקדמות בזמן אמת.</p>
					<div class="mt-8 flex flex-wrap justify-center gap-3">
						<a class="<?php echo esc_attr( $button_class ); ?>" href="<?php echo esc_url( home_url( '/create/' ) ); ?>">יצירת קמפיין</a>
						<a class="<?php echo esc_attr( $outline_class ); ?>" href="<?php echo esc_url( home_url( '/campaigns/' ) ); ?>">צפייה בקמפיינים</a>
					</div>
					<div class="mt-8 inline-flex items-center gap-3 rounded-full border border-border/60 bg-background/60 px-4 py-2 text-xs text-muted-foreground shadow-sm backdrop-blur-sm">
						<span class="font-medium text-foreground">חינם לקהילה</span>
					</div>
				</div>
			</div>
		</section>

		<div class="psalms-unite-shortcode">
			<?php echo psalms_unite_shortcode_slot( 'stats' ); ?>
		</div>

		<section class="mx-auto max-w-6xl px-4 py-20">
			<div class="mx-auto max-w-2xl text-center">
				<h2 class="font-display text-3xl font-bold md:text-4xl">איך זה עובד</h2>
				<p class="mt-3 text-muted-foreground">שלושה צעדים פשוטים לקריאה משותפת.</p>
			</div>
			<div class="mt-12 grid gap-6 md:grid-cols-3">
				<?php foreach ( array( array( '01', 'פותחים קמפיין', 'מגדירים מטרה, הקדשה ותיאור קצר.' ), array( '02', 'משתפים קישור', 'מזמינים משפחה, חברים ושגרירים.' ), array( '03', 'עוקבים יחד', 'רואים התקדמות והשלמות בצורה ברורה.' ) ) as $step ) : ?>
					<article class="rounded-xl border border-border/60 bg-card p-7">
						<div class="font-display text-3xl font-bold text-primary/40"><?php echo esc_html( $step[0] ); ?></div>
						<h3 class="mt-3 text-lg font-semibold"><?php echo esc_html( $step[1] ); ?></h3>
						<p class="mt-2 text-sm text-muted-foreground"><?php echo esc_html( $step[2] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="mx-auto max-w-6xl px-4 pb-20">
			<div class="flex flex-wrap items-end justify-between gap-4">
				<div>
					<h2 class="font-display text-3xl font-bold md:text-4xl">קמפיינים אחרונים</h2>
					<p class="mt-2 text-muted-foreground">נמשך מתוך התוסף כאשר הוא מחובר.</p>
				</div>
				<a class="rounded-full px-4 py-2 text-sm font-medium hover:bg-accent" href="<?php echo esc_url( home_url( '/campaigns/' ) ); ?>">לכל הקמפיינים</a>
			</div>
			<div class="psalms-unite-shortcode mt-10">
				<?php echo psalms_unite_shortcode_slot( 'campaigns', '<div class="rounded-xl border border-dashed border-border/60 bg-card p-12 text-center text-muted-foreground">חבר את shortcode הקמפיינים של התוסף כדי להציג כאן נתונים חיים.</div>' ); ?>
			</div>
		</section>

		<section class="bg-card/40 py-20">
			<div class="mx-auto max-w-6xl px-4">
				<h2 class="text-center font-display text-3xl font-bold md:text-4xl">כל מה שצריך לקמפיין מנצח</h2>
				<div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
					<?php foreach ( array(
						array( 'יצירת קמפיין במגוון מטרות', 'רפואה, ישועה, זיווג, הצלחה, לעילוי נשמה ועוד.' ),
						array( 'מערכת שגרירים', 'כל שגריר עם לינק ייעודי ולוח התקדמות אישי.' ),
						array( 'מעקב אישי', 'כל משתתף רואה את התרומה שלו ליעד.' ),
						array( 'התקדמות בזמן אמת', 'מד גדול, ספירת פרקים וספרים מתעדכנת מיד.' ),
						array( 'שיתוף בוואטסאפ', 'כפתור אחד והקמפיין מגיע לכל קבוצה.' ),
						array( 'השתתפות קבוצתית', 'משפחות, כיתות, קהילות — כולם תורמים יחד.' ),
						array( 'תגי הישג', 'ספר ראשון, 10 ספרים, שגריר מצטיין ועוד.' ),
						array( 'סטטיסטיקות חיות', 'מי תרם, מתי, וכמה — הכל שקוף.' ),
					) as $feature ) : ?>
						<article class="rounded-xl border border-border/60 bg-card p-6 transition-all hover:shadow-md">
							<div class="grid h-10 w-10 place-items-center rounded-xl bg-primary-soft text-primary">✦</div>
							<h3 class="mt-4 font-semibold"><?php echo esc_html( $feature[0] ); ?></h3>
							<p class="mt-1.5 text-sm text-muted-foreground"><?php echo esc_html( $feature[1] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="relative overflow-hidden py-24">
			<div aria-hidden="true" class="pointer-events-none absolute inset-0">
				<div class="absolute -top-20 start-1/3 h-72 w-72 rounded-full bg-gold/15 blur-3xl"></div>
				<div class="absolute -bottom-24 end-1/4 h-80 w-80 rounded-full bg-primary/10 blur-3xl"></div>
			</div>
			<div class="relative mx-auto max-w-6xl px-4">
				<div class="mx-auto max-w-2xl text-center">
					<h2 class="font-display text-3xl font-bold text-balance md:text-4xl">סיפורים מקהילות שהתאחדו סביב תהילים</h2>
				</div>
				<div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
					<?php foreach ( array(
						array( 'name' => 'משפחת לוי', 'role' => 'תל אביב', 'text' => 'תוך שבוע השלמנו 100 ספרי תהילים לרפואת אבא. לא האמנו כמה אנשים הצטרפו.' ),
						array( 'name' => 'ביה״כ ׳אור החיים׳', 'role' => 'ירושלים', 'text' => 'הקמפיין איחד את הקהילה כמו שלא ראינו שנים. הילדים והנכדים השתתפו ביחד.' ),
						array( 'name' => 'שרה מ.', 'role' => 'בני ברק', 'text' => 'פתחתי קמפיין לישועה והקהל הגיב מעבר לכל דמיון. כלי פשוט שמשנה הכל.' ),
						array( 'name' => 'משפחת כהן', 'role' => 'פתח תקווה', 'text' => 'ביום אחד גייסנו 80 משתתפים. הצפיה במד מתמלא היתה מרגשת עד דמעות.' ),
						array( 'name' => 'קהילת ׳נר תמיד׳', 'role' => 'חיפה', 'text' => 'פתחנו קמפיין לעילוי נשמה והצלחנו לאחד דורות שלמים במשפחה סביב מטרה אחת.' ),
						array( 'name' => 'רחל ב.', 'role' => 'אשדוד', 'text' => 'השגרירים הניעו את הקמפיין יותר מכל דבר שדמיינתי. כל אחת הביאה עוד חברות.' ),
					) as $tm ) : ?>
						<article class="relative h-full overflow-hidden rounded-xl border border-border/60 bg-card/80 p-8 backdrop-blur-sm">
							<div aria-hidden="true" class="absolute -top-2 start-5 font-display text-7xl leading-none text-gold/30 select-none">&rdquo;</div>
							<p class="relative font-display text-lg leading-relaxed text-balance text-foreground/90"><?php echo esc_html( $tm['text'] ); ?></p>
							<div class="mt-6 flex items-center gap-3">
								<div class="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-primary to-primary/60 font-display text-sm font-bold text-primary-foreground"><?php echo esc_html( mb_substr( $tm['name'], 0, 1 ) ); ?></div>
								<div>
									<div class="text-sm font-semibold text-foreground"><?php echo esc_html( $tm['name'] ); ?></div>
									<div class="text-xs text-muted-foreground"><?php echo esc_html( $tm['role'] ); ?></div>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section id="faq" class="bg-white py-24">
			<div class="mx-auto grid max-w-6xl gap-12 px-4 md:grid-cols-12">
				<aside class="md:col-span-5 lg:col-span-4">
					<div class="md:sticky md:top-24">
						<div class="inline-flex items-center gap-2 rounded-full border border-gold/40 bg-gold/10 px-3 py-1 text-xs font-medium text-foreground/80">שאלות ותשובות</div>
						<h2 class="mt-4 font-display text-3xl font-bold text-balance md:text-4xl">שאלות נפוצות</h2>
						<p class="mt-3 text-muted-foreground">כל מה שצריך לדעת לפני שפותחים קמפיין. עוד שאלה? אנחנו כאן.</p>
					</div>
				</aside>
				<div class="md:col-span-7 lg:col-span-8">
					<div class="space-y-3">
						<?php
						$faqs = array(
							array( 'האם השימוש בפלטפורמה כרוך בתשלום?', 'לא. השימוש חינמי לחלוטין. בלי הגבלות, בלי כרטיס אשראי.' ),
							array( 'האם זה אתר לקריאת תהילים?', 'לא. זו פלטפורמת קמפיינים — מסביב למטרה משותפת. את התהילים אומרים בכל ספר או אפליקציה שתבחרו, וכאן מסמנים את הפרקים שאמרתם.' ),
							array( 'איך נספרים הפרקים?', 'כל משתתף מסמן בלחיצה אחת איזה פרק או ספר השלים. הספירה מתעדכנת מיידית בקמפיין.' ),
							array( 'מהו תפקיד השגריר?', 'שגריר מקבל לינק אישי, מגייס משתתפים מהקהילה שלו, ומופיע בלוח השגרירים של הקמפיין.' ),
							array( 'האם ניתן לשתף את הקמפיין?', 'בוודאי — וואטסאפ, פייסבוק, מייל, או העתקת לינק. ככל שמשתפים יותר, מגיעים ליעד מהר יותר.' ),
							array( 'כמה זמן לוקח לפתוח קמפיין?', 'בערך שתי דקות. שם, מטרה, יעד — והקמפיין באוויר.' ),
							array( 'האם הנתונים שלי מאובטחים?', 'כן. אנחנו אוספים רק את המינימום הדרוש להפעלת הקמפיין, ולא משתפים אותו עם אף גורם.' ),
						);
						foreach ( $faqs as $i => $faq ) :
							?>
							<details class="psalms-faq rounded-xl border border-border/60 bg-background px-5">
								<summary class="flex items-start gap-3 py-4 font-medium">
									<span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary-soft text-xs font-bold text-primary tabular-nums"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
									<span><?php echo esc_html( $faq[0] ); ?></span>
								</summary>
								<div class="ps-9 pb-4 text-muted-foreground"><?php echo esc_html( $faq[1] ); ?></div>
							</details>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>

		<section class="mx-auto max-w-4xl px-4 py-20 text-center">
			<div class="rounded-xl border border-primary/20 bg-primary p-12 text-primary-foreground">
				<h2 class="font-display text-3xl font-bold md:text-4xl">מוכנים לפתוח קמפיין?</h2>
				<p class="mx-auto mt-3 max-w-xl text-balance opacity-90">צרו קמפיין, הזמינו משתתפים, חלקו פרקים ועקבו אחרי ההתקדמות בזמן אמת.</p>
				<a class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-background px-6 py-3 text-sm font-medium text-foreground hover:opacity-90" href="<?php echo esc_url( home_url( '/create/' ) ); ?>">יצירת קמפיין</a>
			</div>
		</section>
	<?php endif; ?>
</main>

<footer class="border-t border-border/60 bg-card/40">
	<div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-8 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between">
		<p>קמפייני תהילים</p>
		<a class="hover:text-foreground" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">אודות הפרויקט</a>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
