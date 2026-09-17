<?php
/**
 * Login Page — themed front-end sign-in (matches the design system)
 */

// Already signed in? Go where the visitor was headed.
if ( is_user_logged_in() ) {
	$dest = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), home_url( '/' ) ) : home_url( '/' );
	wp_safe_redirect( $dest );
	exit;
}

get_header();

$redirect_to = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), home_url( '/create/' ) ) : home_url( '/create/' );
$flag        = isset( $_GET['login'] ) ? sanitize_key( $_GET['login'] ) : '';

$messages = array(
	'failed'       => array( 'error', 'שם המשתמש או הסיסמה שגויים. נסו שוב.' ),
	'google_failed' => array( 'error', 'ההתחברות עם Google נכשלה. נסו שוב או התחברו עם סיסמה.' ),
	'google_off'   => array( 'error', 'התחברות Google אינה מוגדרת עדיין באתר.' ),
	'loggedout'    => array( 'ok', 'התנתקתם בהצלחה. להתראות!' ),
	'registered'   => array( 'ok', 'ההרשמה הושלמה! בדקו את האימייל שלכם וקבעו סיסמה.' ),
);
?>

<div class="login-page page-anim">
	<div class="login-card">
		<div class="login-icon">
			<svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M12 6.4C10.2 5 7.4 4.5 4.6 5v12.3c2.8-.5 5.6 0 7.4 1.4 1.8-1.4 4.6-1.9 7.4-1.4V5c-2.8-.5-5.6 0-7.4 1.4Z" stroke="#FFF3E4" stroke-width="1.7" stroke-linejoin="round"></path><path d="M12 6.4v12.3" stroke="#F0CE7E" stroke-width="1.7" stroke-linecap="round"></path></svg>
		</div>
		<h1 class="login-title"><?php esc_html_e( 'התחברות לתהילים', 'tehilim' ); ?></h1>
		<p class="login-subtitle"><?php esc_html_e( 'התחברו כדי לפתוח קבוצת תהילים ולנהל אותו.', 'tehilim' ); ?></p>

		<?php if ( $flag && isset( $messages[ $flag ] ) ) : ?>
			<div class="login-msg <?php echo esc_attr( $messages[ $flag ][0] ); ?>" role="alert"><?php echo esc_html( $messages[ $flag ][1] ); ?></div>
		<?php endif; ?>

		<?php
		// Social sign-in from plugins (e.g. Login Me Now / Nextend): render the
		// first available shortcode, so their Google button shows on this page.
		$social_html = '';
		foreach ( array( 'login_me_now_social_login', 'lmn_social_login', 'login-me-now-social-login', 'nextend_social_login' ) as $social_shortcode ) {
			if ( shortcode_exists( $social_shortcode ) ) {
				$social_html = do_shortcode( '[' . $social_shortcode . ']' );
				break;
			}
		}
		?>

		<?php if ( $social_html ) : ?>
			<div class="login-plugin-slot"><?php echo $social_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="login-divider"><span><?php esc_html_e( 'או עם סיסמה', 'tehilim' ); ?></span></div>
		<?php elseif ( tehilim_google_enabled() ) : ?>
			<a class="btn-google" href="<?php echo esc_url( admin_url( 'admin-post.php?action=tehilim_google_login&redirect_to=' . rawurlencode( $redirect_to ) ) ); ?>">
				<svg width="19" height="19" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.3 6.1 29.4 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.6-.4-3.9z"></path><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.3 6.1 29.4 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"></path><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"></path><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C41 35.2 44 30 44 24c0-1.3-.1-2.6-.4-3.9z"></path></svg>
				<?php esc_html_e( 'התחברות עם Google', 'tehilim' ); ?>
			</a>
			<div class="login-divider"><span><?php esc_html_e( 'או עם סיסמה', 'tehilim' ); ?></span></div>
		<?php endif; ?>

		<form class="login-form" method="post" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>">
			<label class="create-label first" for="user_login"><?php esc_html_e( 'אימייל או שם משתמש', 'tehilim' ); ?></label>
			<input class="create-input" type="text" id="user_login" name="log" autocomplete="username" required>

			<label class="create-label" for="user_pass"><?php esc_html_e( 'סיסמה', 'tehilim' ); ?></label>
			<input class="create-input" type="password" id="user_pass" name="pwd" autocomplete="current-password" required>

			<div class="login-row">
				<label class="login-remember">
					<input type="checkbox" name="rememberme" value="forever" checked>
					<?php esc_html_e( 'זכרו אותי', 'tehilim' ); ?>
				</label>
				<a class="login-forgot" href="<?php echo esc_url( wp_lostpassword_url( $redirect_to ) ); ?>"><?php esc_html_e( 'שכחתם סיסמה?', 'tehilim' ); ?></a>
			</div>

			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">

			<?php do_action( 'login_form' ); // standard hook — social-login plugins inject their buttons here ?>

			<button type="submit" class="btn-create-submit"><?php esc_html_e( 'התחברות', 'tehilim' ); ?></button>
		</form>

		<?php if ( get_option( 'users_can_register' ) ) : ?>
			<p class="login-register">
				<?php esc_html_e( 'אין לכם חשבון עדיין?', 'tehilim' ); ?>
				<a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'הרשמה', 'tehilim' ); ?></a>
			</p>
		<?php endif; ?>
	</div>
</div>

<script>
( function() {
	// Keep only password + Google sign-in: hide plugin "Email Link" buttons
	function hideEmailLink() {
		document.querySelectorAll( '.login-card a, .login-card button' ).forEach( function( el ) {
			if ( /email\s*link/i.test( el.textContent || '' ) ) {
				el.style.display = 'none';
			}
		} );
	}
	hideEmailLink();
	// Plugins may inject after load — watch briefly for late buttons
	var observer = new MutationObserver( hideEmailLink );
	var card = document.querySelector( '.login-card' );
	if ( card ) {
		observer.observe( card, { childList: true, subtree: true } );
		setTimeout( function() { observer.disconnect(); }, 4000 );
	}
} )();
</script>

<?php
get_footer();
