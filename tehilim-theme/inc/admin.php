<?php
/**
 * Admin Settings & Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register admin menu
 */
function tehilim_register_admin_menu() {
	add_menu_page(
		'הגדרות תהילים',
		'תהילים',
		'manage_options',
		'tehilim-settings',
		'tehilim_settings_page',
		'dashicons-book',
		50
	);

	add_submenu_page(
		'tehilim-settings',
		'הגדרות',
		'הגדרות',
		'manage_options',
		'tehilim-settings',
		'tehilim_settings_page'
	);

	add_submenu_page(
		'tehilim-settings',
		'מנהל קמפיינים',
		'קמפיינים',
		'manage_options',
		'edit.php?post_type=campaign'
	);

	add_submenu_page(
		'tehilim-settings',
		'מנהל שגרירים',
		'שגרירים',
		'manage_options',
		'edit.php?post_type=ambassador'
	);
}
add_action( 'admin_menu', 'tehilim_register_admin_menu' );

/**
 * Render settings page
 */
function tehilim_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized' );
	}

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		check_admin_referer( 'tehilim_settings_nonce' );

		update_option( 'tehilim_site_description', sanitize_text_field( $_POST['site_description'] ?? '' ) );
		update_option( 'tehilim_enable_turnstile', isset( $_POST['enable_turnstile'] ) ? 1 : 0 );

		// Google credentials: never wipe on an empty field — an admin saving an
		// unrelated setting must not disable Google sign-in. Explicit disconnect only.
		if ( ! empty( $_POST['google_disconnect'] ) ) {
			delete_option( 'tehilim_google_client_id' );
			delete_option( 'tehilim_google_client_secret' );
		} else {
			if ( ! empty( $_POST['google_client_id'] ) ) {
				update_option( 'tehilim_google_client_id', sanitize_text_field( $_POST['google_client_id'] ) );
			}
			if ( ! empty( $_POST['google_client_secret'] ) ) {
				update_option( 'tehilim_google_client_secret', sanitize_text_field( $_POST['google_client_secret'] ) );
			}
		}

		echo '<div class="notice notice-success"><p>ההגדרות נשמרו!</p></div>';
	}

	$description = get_option( 'tehilim_site_description', '' );
	$enable_turnstile = get_option( 'tehilim_enable_turnstile', 0 );
	$google_client_id = get_option( 'tehilim_google_client_id', '' );
	?>
	<div class="wrap">
		<h1>הגדרות תהילים</h1>

		<div style="max-width: 800px; margin: 20px 0;">
			<div style="background: #f0f4f8; border-left: 4px solid #C05A3A; padding: 15px; border-radius: 4px;">
				<h3 style="margin-top: 0; color: #C05A3A;">סטטיסטיקה מהירה</h3>
				<?php
				$total_campaigns = wp_count_posts( 'campaign' )->publish;
				$total_ambassadors = wp_count_posts( 'ambassador' )->publish;

				global $wpdb;
				$table = $wpdb->prefix . 'tehilim_recitations';
				$total_recitations = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tehilim_recitations" ) );
				$total_chapters = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT chapter_number) FROM {$wpdb->prefix}tehilim_recitations" ) );
				?>
				<p>
					<strong><?php echo esc_html( $total_campaigns ); ?></strong> קמפיינים פעילים<br>
					<strong><?php echo esc_html( $total_ambassadors ); ?></strong> שגרירים<br>
					<strong><?php echo esc_html( $total_recitations ); ?></strong> אמירות<br>
					<strong><?php echo esc_html( intdiv( $total_chapters, TEHILIM_CHAPTERS_PER_BOOK ) ); ?></strong> ספרים שהושלמו
				</p>
			</div>
		</div>

		<form method="POST" style="max-width: 600px;">
			<?php wp_nonce_field( 'tehilim_settings_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="site_description">תיאור אתר</label>
					</th>
					<td>
						<textarea
							name="site_description"
							id="site_description"
							rows="4"
							style="width: 100%; max-width: 400px;"
							class="widefat"
						><?php echo esc_textarea( $description ); ?></textarea>
						<p class="description">מוצג בעמוד הבית</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="enable_turnstile">הפעלת Turnstile CAPTCHA</label>
					</th>
					<td>
						<input type="checkbox" name="enable_turnstile" id="enable_turnstile" value="1" <?php checked( $enable_turnstile, 1 ); ?> />
						<p class="description">
							דורש <code>TURNSTILE_SITE_KEY</code> ו<code>TURNSTILE_SECRET_KEY</code> ב<code>wp-config.php</code>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">CAPTCHA מוגדר</th>
					<td>
						<?php if ( defined( 'TURNSTILE_SITE_KEY' ) && defined( 'TURNSTILE_SECRET_KEY' ) ) : ?>
							<span style="color: green;">✓ מופעל</span>
						<?php else : ?>
							<span style="color: orange;">✗ לא מוגדר (הוסיפו ל wp-config.php)</span>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="google_client_id">התחברות עם Google</label>
					</th>
					<td>
						<input type="text" name="google_client_id" id="google_client_id" class="widefat" style="max-width:400px" value="<?php echo esc_attr( $google_client_id ); ?>" placeholder="Client ID (....apps.googleusercontent.com)" dir="ltr">
						<br><br>
						<input type="password" name="google_client_secret" id="google_client_secret" class="widefat" style="max-width:400px" value="" placeholder="Client Secret (<?php echo get_option( 'tehilim_google_client_secret' ) ? 'מוגדר — השאירו ריק כדי לא לשנות' : 'לא מוגדר'; ?>" dir="ltr" autocomplete="new-password">
						<p class="description">
							צרו OAuth Client ב-<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>
							והגדירו Redirect URI:<br>
							<code dir="ltr"><?php echo esc_html( admin_url( 'admin-post.php?action=tehilim_google_callback' ) ); ?></code><br>
							סטטוס:
							<?php if ( function_exists( 'tehilim_google_enabled' ) && tehilim_google_enabled() ) : ?>
								<span style="color: green;">✓ פעיל — כפתור Google מוצג בעמוד ההתחברות</span>
							<?php else : ?>
								<span style="color: orange;">✗ לא מוגדר — הכפתור מוסתר</span>
							<?php endif; ?>
						</p>
						<?php if ( get_option( 'tehilim_google_client_id' ) || get_option( 'tehilim_google_client_secret' ) ) : ?>
							<label style="display:inline-flex;align-items:center;gap:6px;margin-top:6px">
								<input type="checkbox" name="google_disconnect" value="1">
								ניתוק חשבון Google (מחיקת המפתחות)
							</label>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row">מצב מסד הנתונים</th>
					<td>
						<?php
						$db_table = $wpdb->prefix . 'tehilim_recitations';
						if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $db_table ) ) === $db_table ) {
							echo '<span style="color: green;">✓ טבלת האמירות קיימת</span>';
						} else {
							echo '<span style="color: red;">✗ טבלה חסרה (הפעילו את ערכת הנושא כדי ליצור)</span>';
						}
						?>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<hr style="margin: 40px 0;">

		<div style="max-width: 600px;">
			<h2>תיעוד</h2>
			<ul>
				<li><a href="https://github.com/multidigitalltd/tehilim/blob/claude/tehilim-v2-clean/README.md" target="_blank">📖 מדריך משתמש</a></li>
				<li><a href="https://github.com/multidigitalltd/tehilim/blob/claude/tehilim-v2-clean/CLAUDE.md" target="_blank">👨‍💻 תיעוד מפתחים</a></li>
				<li><a href="https://github.com/multidigitalltd/tehilim" target="_blank">🔗 GitHub Repository</a></li>
			</ul>
		</div>
	</div>
	<?php
}

/**
 * Register options
 */
function tehilim_register_settings() {
	register_setting( 'tehilim_settings', 'tehilim_site_description' );
	register_setting( 'tehilim_settings', 'tehilim_enable_turnstile' );
	register_setting( 'tehilim_settings', 'tehilim_google_client_id' );
	register_setting( 'tehilim_settings', 'tehilim_google_client_secret' );
}
add_action( 'admin_init', 'tehilim_register_settings' );

/**
 * Add admin columns for campaigns
 */
function tehilim_campaign_columns( $columns ) {
	$columns['occasion'] = 'סיבה';
	$columns['progress'] = 'התקדמות';
	$columns['ambassadors'] = 'שגרירים';
	return $columns;
}
add_filter( 'manage_campaign_posts_columns', 'tehilim_campaign_columns' );

/**
 * Render campaign columns
 */
function tehilim_campaign_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'occasion':
			$occasions = get_the_terms( $post_id, 'occasion' );
			if ( $occasions ) {
				echo esc_html( $occasions[0]->name );
			}
			break;

		case 'progress':
			$progress = tehilim_get_campaign_progress( $post_id );
			echo esc_html( $progress['books_done'] . ' / ' . $progress['goal_books'] . ' books' );
			break;

		case 'ambassadors':
			$ambassadors = tehilim_get_top_ambassadors( $post_id, 100 );
			echo esc_html( count( $ambassadors ) . ' ambassadors' );
			break;
	}
}
add_action( 'manage_campaign_posts_custom_column', 'tehilim_campaign_column_content', 10, 2 );

/**
 * Add recitations export
 */
function tehilim_admin_footer() {
	$screen = get_current_screen();
	if ( 'campaign' === $screen->post_type && 'edit' === $screen->base ) {
		?>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			let adminBar = document.querySelector('#wp-admin-bar-root-default');
			if (adminBar) {
				let li = document.createElement('li');
				li.id = 'wp-admin-bar-tehilim-export';
				let link = document.createElement('a');
				link.className = 'ab-item';
				link.href = '?post_type=campaign&action=tehilim_export';
				link.target = '_blank';
				link.textContent = '📥 Export Recitations';
				li.appendChild(link);
				adminBar.appendChild(li);
			}
		});
		</script>
		<?php
	}
}
add_action( 'admin_footer', 'tehilim_admin_footer' );
