<?php
/**
 * Header Template - Tehilim
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> dir="rtl">
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="header-wrapper">
		<!-- Logo & Brand -->
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo">
			<div class="logo-icon">
				<svg width="23" height="23" viewBox="0 0 24 24" fill="none">
					<path d="M12 6.4C10.2 5 7.4 4.5 4.6 5v12.3c2.8-.5 5.6 0 7.4 1.4 1.8-1.4 4.6-1.9 7.4-1.4V5c-2.8-.5-5.6 0-7.4 1.4Z" stroke="#FFF3E4" stroke-width="1.7" stroke-linejoin="round"></path>
					<path d="M12 6.4v12.3" stroke="#F0CE7E" stroke-width="1.7" stroke-linecap="round"></path>
				</svg>
			</div>
			<span><?php bloginfo( 'name' ); ?></span>
		</a>

		<!-- Navigation Links -->
		<nav class="header-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'tehilim' ); ?>">
			<a href="#how-it-works"><?php esc_html_e( 'איך זה עובד', 'tehilim' ); ?></a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>"><?php esc_html_e( 'קמפיינים', 'tehilim' ); ?></a>
			<a href="#about"><?php esc_html_e( 'אודות', 'tehilim' ); ?></a>
		</nav>

		<!-- Action Buttons -->
		<div class="header-actions">
			<?php if ( is_user_logged_in() ) : ?>
				<a class="btn-login" href="<?php echo esc_url( tehilim_account_page_url() ); ?>" title="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>"><?php esc_html_e( 'האזור האישי', 'tehilim' ); ?></a>
				<a class="btn-login btn-logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'התנתקות', 'tehilim' ); ?></a>
			<?php else : ?>
				<a class="btn-login" href="<?php echo esc_url( tehilim_login_page_url( home_url( '/create/' ) ) ); ?>"><?php esc_html_e( 'התחברות', 'tehilim' ); ?></a>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/create/' ) ); ?>" class="btn-create-primary"><?php esc_html_e( 'צור קמפיין', 'tehilim' ); ?></a>
		</div>
	</div>
</header>
