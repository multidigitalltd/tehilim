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
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path        = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
	$slug        = '' === $path ? 'home' : sanitize_title( basename( $path ) );
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
	'campaigns' => array(
		'label' => 'קמפיינים',
		'url'   => home_url( '/campaigns/' ),
	),
	'about'     => array(
		'label' => 'אודות',
		'url'   => home_url( '/about/' ),
	),
	'create'    => array(
		'label' => 'יצירת קמפיין',
		'url'   => home_url( '/create/' ),
	),
);

$button_class  = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full bg-primary px-6 py-3 text-sm font-medium text-primary-foreground shadow-lg shadow-primary/20 transition-all hover:shadow-xl hover:shadow-primary/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';
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
<a class="psalms-skip" href="#content"><?php esc_html_e( 'דלג לתוכן', 'psalms-unite' ); ?></a>

<header class="sticky top-0 z-50 w-full border-b border-border/60 bg-background/80 backdrop-blur-md">
	<div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
		<?php if ( has_custom_logo() ) : ?>
			<span class="psalms-site-logo"><?php the_custom_logo(); ?></span>
		<?php else : ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2 font-display text-xl font-bold">
				<span class="grid h-9 w-9 place-items-center rounded-xl bg-primary text-primary-foreground" aria-hidden="true">♥</span>
				<span><?php echo esc_html( psalms_unite_text( 'brand' ) ); ?></span>
			</a>
		<?php endif; ?>
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
			<h1 class="sr-only"><?php echo esc_html( get_the_title( get_queried_object_id() ) ); ?></h1>
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
				<h1 class="font-display text-4xl font-bold"><?php echo esc_html( psalms_unite_text( 'campaigns_title' ) ); ?></h1>
				<p class="mt-2 text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'campaigns_sub' ) ); ?></p>
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
				<h1 class="font-display text-4xl font-bold"><?php echo esc_html( psalms_unite_text( 'create_title' ) ); ?></h1>
				<p class="mt-2 text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'create_sub' ) ); ?></p>
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
			<div class="mb-8 text-center"><h1 class="font-display text-4xl font-bold"><?php echo esc_html( psalms_unite_text( 'dashboard_title' ) ); ?></h1><p class="mt-2 text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'dashboard_sub' ) ); ?></p></div>
			<div class="psalms-unite-shortcode grid gap-8">
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
				<h1 class="font-display text-3xl font-bold"><?php echo esc_html( psalms_unite_text( 'auth_title' ) ); ?></h1>
				<p class="mt-1 text-sm text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'auth_sub' ) ); ?></p>
				<div class="psalms-unite-shortcode mt-6">
					<?php wp_login_form( array( 'redirect' => home_url( '/dashboard/' ) ) ); ?>
				</div>
			</div>
		</section>
	<?php elseif ( 'subscribe' === $slug ) : ?>
		<section class="mx-auto max-w-3xl px-4 py-12">
			<div class="mb-8 text-center">
				<h1 class="font-display text-4xl font-bold"><?php echo esc_html( psalms_unite_text( 'subscribe_title' ) ); ?></h1>
				<p class="mt-2 text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'subscribe_sub' ) ); ?></p>
			</div>
			<div class="psalms-unite-shortcode">
				<?php echo psalms_unite_shortcode_slot( 'subscribe', '<div class="rounded-xl border border-dashed border-border/60 bg-card p-12 text-center text-muted-foreground">יש להפעיל את רכיב ההרשמה בתוסף.</div>' ); ?>
			</div>
		</section>
	<?php elseif ( 'about' === $slug ) : ?>
		<section class="gradient-warm relative overflow-hidden">
			<div aria-hidden="true" class="pointer-events-none absolute inset-0">
				<div class="absolute -top-24 start-1/4 h-72 w-72 rounded-full bg-gold/25 blur-3xl"></div>
				<div class="absolute -bottom-32 end-1/4 h-80 w-80 rounded-full bg-primary/20 blur-3xl"></div>
			</div>
			<div class="relative mx-auto max-w-3xl px-4 py-24 text-center md:py-28">
				<span class="inline-flex items-center gap-2 rounded-full border border-gold/40 bg-gold/10 px-3 py-1 text-xs font-medium text-foreground shadow-sm backdrop-blur-sm">&#10022; <?php echo esc_html( psalms_unite_text( 'about_eyebrow' ) ); ?></span>
				<h1 class="mt-6 font-display text-4xl font-bold text-balance md:text-5xl"><?php echo esc_html( psalms_unite_text( 'about_title' ) ); ?></h1>
				<p class="mx-auto mt-4 max-w-xl text-balance text-muted-foreground md:text-lg"><?php echo esc_html( psalms_unite_text( 'about_sub' ) ); ?></p>
			</div>
		</section>

		<section class="mx-auto max-w-5xl px-4 py-20">
			<div class="grid gap-10 md:grid-cols-2">
				<article class="relative overflow-hidden rounded-xl border border-border/60 bg-card/80 p-8 backdrop-blur-sm">
					<div aria-hidden="true" class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold/40 to-transparent"></div>
					<div class="grid h-11 w-11 place-items-center rounded-xl bg-primary-soft text-primary">&#10022;</div>
					<h2 class="mt-4 font-display text-2xl font-bold"><?php echo esc_html( psalms_unite_text( 'about_mission_title' ) ); ?></h2>
					<p class="mt-3 leading-relaxed text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'about_mission_text' ) ); ?></p>
				</article>
				<article class="relative overflow-hidden rounded-xl border border-border/60 bg-card/80 p-8 backdrop-blur-sm">
					<div aria-hidden="true" class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-gold/40 to-transparent"></div>
					<div class="grid h-11 w-11 place-items-center rounded-xl bg-gold/15 text-gold">&#10022;</div>
					<h2 class="mt-4 font-display text-2xl font-bold"><?php echo esc_html( psalms_unite_text( 'about_story_title' ) ); ?></h2>
					<p class="mt-3 leading-relaxed text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'about_story_text' ) ); ?></p>
				</article>
			</div>
		</section>

		<section class="bg-card/40 py-20">
			<div class="mx-auto max-w-6xl px-4">
				<div class="mx-auto max-w-2xl text-center">
					<h2 class="font-display text-3xl font-bold md:text-4xl"><?php echo esc_html( psalms_unite_text( 'about_values_title' ) ); ?></h2>
				</div>
				<div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
					<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
						<article class="rounded-xl border border-border/60 bg-card p-6 transition-all hover:-translate-y-0.5 hover:border-gold/40 hover:shadow-md">
							<div class="grid h-10 w-10 place-items-center rounded-xl bg-primary-soft text-primary">&#10022;</div>
							<h3 class="mt-4 font-semibold"><?php echo esc_html( psalms_unite_text( 'val_' . $i . '_title' ) ); ?></h3>
							<p class="mt-1.5 text-sm text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'val_' . $i . '_text' ) ); ?></p>
						</article>
					<?php endfor; ?>
				</div>
			</div>
		</section>

		<section class="mx-auto max-w-4xl px-4 py-20 text-center">
			<div class="rounded-xl border border-primary/20 bg-primary p-12 text-primary-foreground">
				<h2 class="font-display text-3xl font-bold md:text-4xl"><?php echo esc_html( psalms_unite_text( 'about_cta_title' ) ); ?></h2>
				<p class="mx-auto mt-3 max-w-xl text-balance opacity-90"><?php echo esc_html( psalms_unite_text( 'about_cta_sub' ) ); ?></p>
				<a class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-background px-6 py-3 text-sm font-medium text-foreground hover:opacity-90" href="<?php echo esc_url( home_url( '/create/' ) ); ?>"><?php echo esc_html( psalms_unite_text( 'about_cta_button' ) ); ?></a>
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
					<h1 class="mt-6 font-display text-4xl font-bold text-balance md:text-6xl"><?php echo esc_html( psalms_unite_text( 'hero_title' ) ); ?></h1>
					<p class="mx-auto mt-5 max-w-2xl text-balance text-base text-muted-foreground md:text-lg"><?php echo esc_html( psalms_unite_text( 'hero_sub' ) ); ?></p>
					<div class="mt-8 flex flex-wrap justify-center gap-3">
						<a class="<?php echo esc_attr( $button_class ); ?>" href="<?php echo esc_url( home_url( '/create/' ) ); ?>"><?php echo esc_html( psalms_unite_text( 'hero_cta_primary' ) ); ?></a>
						<a class="<?php echo esc_attr( $outline_class ); ?>" href="<?php echo esc_url( home_url( '/campaigns/' ) ); ?>"><?php echo esc_html( psalms_unite_text( 'hero_cta_secondary' ) ); ?></a>
					</div>
				</div>
			</div>
		</section>

		<div class="psalms-unite-shortcode">
			<?php echo psalms_unite_shortcode_slot( 'stats' ); ?>
		</div>

		<section class="mx-auto max-w-6xl px-4 py-20">
			<div class="mx-auto max-w-2xl text-center">
				<h2 class="font-display text-3xl font-bold md:text-4xl"><?php echo esc_html( psalms_unite_text( 'how_title' ) ); ?></h2>
				<p class="mt-3 text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'how_sub' ) ); ?></p>
			</div>
			<div class="mt-12 grid gap-6 md:grid-cols-3">
				<?php
				$psalms_steps = array(
					array( '01', psalms_unite_text( 'how_s1_title' ), psalms_unite_text( 'how_s1_text' ) ),
					array( '02', psalms_unite_text( 'how_s2_title' ), psalms_unite_text( 'how_s2_text' ) ),
					array( '03', psalms_unite_text( 'how_s3_title' ), psalms_unite_text( 'how_s3_text' ) ),
				);
				foreach ( $psalms_steps as $step ) :
					?>
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
					<h2 class="font-display text-3xl font-bold md:text-4xl"><?php echo esc_html( psalms_unite_text( 'recent_title' ) ); ?></h2>
					<p class="mt-2 text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'recent_sub' ) ); ?></p>
				</div>
				<a class="rounded-full px-4 py-2 text-sm font-medium hover:bg-accent" href="<?php echo esc_url( home_url( '/campaigns/' ) ); ?>"><?php echo esc_html( psalms_unite_text( 'recent_viewall' ) ); ?></a>
			</div>
			<div class="psalms-unite-shortcode mt-10">
				<?php echo psalms_unite_shortcode_slot( 'campaigns', '<div class="rounded-xl border border-dashed border-border/60 bg-card p-12 text-center text-muted-foreground">חבר את shortcode הקמפיינים של התוסף כדי להציג כאן נתונים חיים.</div>' ); ?>
			</div>
		</section>

		<section class="bg-card/40 py-20">
			<div class="mx-auto max-w-6xl px-4">
				<h2 class="text-center font-display text-3xl font-bold md:text-4xl"><?php echo esc_html( psalms_unite_text( 'features_title' ) ); ?></h2>
				<div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
					<?php for ( $i = 1; $i <= 8; $i++ ) : ?>
						<article class="rounded-xl border border-border/60 bg-card p-6 transition-all hover:shadow-md">
							<div class="grid h-10 w-10 place-items-center rounded-xl bg-primary-soft text-primary">&#10022;</div>
							<h3 class="mt-4 font-semibold"><?php echo esc_html( psalms_unite_text( 'feat_' . $i . '_title' ) ); ?></h3>
							<p class="mt-1.5 text-sm text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'feat_' . $i . '_text' ) ); ?></p>
						</article>
					<?php endfor; ?>
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
					<h2 class="font-display text-3xl font-bold text-balance md:text-4xl"><?php echo esc_html( psalms_unite_text( 'tst_title' ) ); ?></h2>
				</div>
				<div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
					<?php
					for ( $i = 1; $i <= 6; $i++ ) :
						$psalms_name = psalms_unite_text( 'tst_' . $i . '_name' );
						?>
						<article class="relative h-full overflow-hidden rounded-xl border border-border/60 bg-card/80 p-8 backdrop-blur-sm">
							<div aria-hidden="true" class="absolute -top-2 start-5 font-display text-7xl leading-none text-gold/30 select-none">&rdquo;</div>
							<p class="relative font-display text-lg leading-relaxed text-balance text-foreground/90"><?php echo esc_html( psalms_unite_text( 'tst_' . $i . '_text' ) ); ?></p>
							<div class="mt-6 flex items-center gap-3">
								<div class="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-primary to-primary/60 font-display text-sm font-bold text-primary-foreground"><?php echo esc_html( mb_substr( $psalms_name, 0, 1 ) ); ?></div>
								<div>
									<div class="text-sm font-semibold text-foreground"><?php echo esc_html( $psalms_name ); ?></div>
									<div class="text-xs text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'tst_' . $i . '_role' ) ); ?></div>
								</div>
							</div>
						</article>
					<?php endfor; ?>
				</div>
			</div>
		</section>

		<section id="faq" class="bg-white py-24">
			<div class="mx-auto grid max-w-6xl gap-12 px-4 md:grid-cols-12">
				<aside class="md:col-span-5 lg:col-span-4">
					<div class="md:sticky md:top-24">
						<div class="inline-flex items-center gap-2 rounded-full border border-gold/40 bg-gold/10 px-3 py-1 text-xs font-medium text-foreground/80"><?php echo esc_html( psalms_unite_text( 'faq_eyebrow' ) ); ?></div>
						<h2 class="mt-4 font-display text-3xl font-bold text-balance md:text-4xl"><?php echo esc_html( psalms_unite_text( 'faq_title' ) ); ?></h2>
						<p class="mt-3 text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'faq_sub' ) ); ?></p>
					</div>
				</aside>
				<div class="md:col-span-7 lg:col-span-8">
					<div class="space-y-3">
						<?php for ( $i = 1; $i <= 7; $i++ ) : ?>
							<details class="psalms-faq rounded-xl border border-border/60 bg-background px-5">
								<summary class="flex items-start gap-3 py-4 font-medium">
									<span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary-soft text-xs font-bold text-primary tabular-nums"><?php echo esc_html( str_pad( (string) $i, 2, '0', STR_PAD_LEFT ) ); ?></span>
									<span><?php echo esc_html( psalms_unite_text( 'faq_' . $i . '_q' ) ); ?></span>
								</summary>
								<div class="ps-9 pb-4 text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'faq_' . $i . '_a' ) ); ?></div>
							</details>
						<?php endfor; ?>
					</div>
				</div>
			</div>
		</section>

		<section class="mx-auto max-w-4xl px-4 py-20 text-center">
			<div class="rounded-xl border border-primary/20 bg-primary p-12 text-primary-foreground">
				<h2 class="font-display text-3xl font-bold md:text-4xl"><?php echo esc_html( psalms_unite_text( 'cta_title' ) ); ?></h2>
				<p class="mx-auto mt-3 max-w-xl text-balance opacity-90"><?php echo esc_html( psalms_unite_text( 'cta_sub' ) ); ?></p>
				<a class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-background px-6 py-3 text-sm font-medium text-foreground hover:opacity-90" href="<?php echo esc_url( home_url( '/create/' ) ); ?>"><?php echo esc_html( psalms_unite_text( 'cta_button' ) ); ?></a>
			</div>
		</section>
	<?php endif; ?>
</main>

<footer class="relative overflow-hidden border-t border-border/60 bg-gradient-to-b from-card/40 via-card/60 to-card">
	<div aria-hidden="true" class="pointer-events-none absolute inset-0">
		<div class="absolute -top-24 start-1/4 h-64 w-64 rounded-full bg-gold/10 blur-3xl"></div>
		<div class="absolute -bottom-32 end-1/4 h-80 w-80 rounded-full bg-primary/10 blur-3xl"></div>
	</div>
	<div class="relative mx-auto max-w-6xl px-4 pt-16 pb-8">
		<div class="grid gap-10 md:grid-cols-12">
			<div class="md:col-span-5">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex items-center gap-2 font-display text-lg font-semibold">
					<span class="grid h-10 w-10 place-items-center rounded-xl bg-primary text-primary-foreground shadow-lg shadow-primary/20" aria-hidden="true">♥</span>
					<span><?php echo esc_html( psalms_unite_text( 'brand' ) ); ?></span>
				</a>
				<p class="mt-4 max-w-sm text-sm leading-relaxed text-muted-foreground"><?php echo esc_html( psalms_unite_text( 'footer_tagline' ) ); ?></p>
				<div class="mt-5 inline-flex items-center gap-2 rounded-full border border-gold/40 bg-gold/10 px-3 py-1 text-xs font-medium text-foreground/80">
					<span aria-hidden="true" class="text-gold">♥</span>
					<?php echo esc_html( psalms_unite_text( 'footer_made' ) ); ?>
				</div>
			</div>
			<div class="md:col-span-7 grid grid-cols-2 gap-8 sm:grid-cols-3">
				<div>
					<h4 class="text-xs font-semibold uppercase tracking-widest text-foreground/70"><?php echo esc_html( psalms_unite_text( 'footer_product' ) ); ?></h4>
					<ul class="mt-4 space-y-2.5 text-sm">
						<li><a href="<?php echo esc_url( home_url( '/campaigns/' ) ); ?>" class="text-muted-foreground transition-colors hover:text-foreground"><?php echo esc_html( psalms_unite_text( 'footer_link_explore' ) ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/create/' ) ); ?>" class="text-muted-foreground transition-colors hover:text-foreground"><?php echo esc_html( psalms_unite_text( 'footer_link_create' ) ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="text-muted-foreground transition-colors hover:text-foreground"><?php echo esc_html( psalms_unite_text( 'footer_link_dashboard' ) ); ?></a></li>
					</ul>
				</div>
				<div>
					<h4 class="text-xs font-semibold uppercase tracking-widest text-foreground/70"><?php echo esc_html( psalms_unite_text( 'footer_company' ) ); ?></h4>
					<ul class="mt-4 space-y-2.5 text-sm">
						<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="text-muted-foreground transition-colors hover:text-foreground"><?php echo esc_html( psalms_unite_text( 'footer_link_about' ) ); ?></a></li>
					</ul>
				</div>
				<div>
					<h4 class="text-xs font-semibold uppercase tracking-widest text-foreground/70"><?php echo esc_html( psalms_unite_text( 'footer_resources' ) ); ?></h4>
					<ul class="mt-4 space-y-2.5 text-sm">
						<li><a href="<?php echo esc_url( home_url( '/#faq' ) ); ?>" class="text-muted-foreground transition-colors hover:text-foreground"><?php echo esc_html( psalms_unite_text( 'footer_link_faq' ) ); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="relative mt-12 h-px overflow-hidden">
			<div class="absolute inset-0 bg-gradient-to-r from-transparent via-gold/40 to-transparent"></div>
		</div>
		<div class="mt-6 flex flex-col items-center justify-between gap-3 text-xs text-muted-foreground sm:flex-row">
			<div>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( psalms_unite_text( 'brand' ) ); ?> &middot; <?php echo esc_html( psalms_unite_text( 'footer_rights' ) ); ?></div>
			<div class="flex items-center gap-1.5">
				<span class="inline-block h-1.5 w-1.5 rounded-full bg-gold animate-pulse"></span>
				<span><?php echo esc_html( psalms_unite_text( 'footer_free' ) ); ?></span>
			</div>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
