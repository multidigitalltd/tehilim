<?php
/**
 * Header Template - Tehilim
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="<?php echo is_user_logged_in() ? 'tehilim-in' : ''; ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<script>
	/* Cached pages are identical for everyone — correct the header's login
	   state from the JS-readable cookie before first paint. */
	( function() {
		try {
			var li = document.cookie.indexOf( 'tehilim_li=1' ) !== -1;
			document.documentElement.classList.toggle( 'tehilim-in', li );
		} catch ( e ) {}
	} )();
	</script>
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
			<span><?php esc_html_e( 'תהילים', 'tehilim' ); ?></span>
		</a>

		<!-- Navigation Links -->
		<nav class="header-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'tehilim' ); ?>">
			<a href="<?php echo esc_url( home_url( '/#how-it-works' ) ); ?>"><?php esc_html_e( 'איך זה עובד', 'tehilim' ); ?></a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>"><?php esc_html_e( 'קמפיינים', 'tehilim' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'אודות', 'tehilim' ); ?></a>
		</nav>

		<!-- Action Buttons: both variants rendered; CSS + cookie pick one -->
		<div class="header-actions">
			<span class="auth-out">
				<?php
				// Send the visitor back to the page they are on right now
				$tehilim_current = home_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
				?>
				<a class="btn-login" href="<?php echo esc_url( tehilim_login_page_url( $tehilim_current ) ); ?>"><?php esc_html_e( 'התחברות', 'tehilim' ); ?></a>
			</span>
			<span class="auth-in">
				<a class="btn-login" href="<?php echo esc_url( tehilim_account_page_url() ); ?>"><?php esc_html_e( 'האזור האישי', 'tehilim' ); ?></a>
				<a class="btn-login btn-logout" href="<?php echo esc_url( admin_url( 'admin-post.php?action=tehilim_logout' ) ); ?>"><?php esc_html_e( 'התנתקות', 'tehilim' ); ?></a>
			</span>
			<a href="<?php echo esc_url( home_url( '/create/' ) ); ?>" class="btn-create-primary"><?php esc_html_e( 'צור קמפיין', 'tehilim' ); ?></a>
		</div>
	</div>
</header>
