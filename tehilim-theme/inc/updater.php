<?php
/**
 * Theme Self-Updater — upload a new theme ZIP from within wp-admin and it
 * replaces the active theme in place (no FTP, no re-activation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the updater page under the Tehilim menu.
 */
function tehilim_updater_menu() {
	add_submenu_page(
		'tehilim-settings',
		'עדכון תבנית',
		'עדכון תבנית',
		'update_themes',
		'tehilim-update',
		'tehilim_updater_page'
	);
}
add_action( 'admin_menu', 'tehilim_updater_menu', 20 );

/**
 * Render + handle the updater page.
 */
function tehilim_updater_page() {
	if ( ! current_user_can( 'update_themes' ) ) {
		wp_die( 'Unauthorized' );
	}

	$notice = '';
	$error  = '';

	if ( isset( $_POST['tehilim_do_update'] ) ) {
		check_admin_referer( 'tehilim_update_nonce' );
		$result = tehilim_process_uploaded_theme();
		if ( is_wp_error( $result ) ) {
			$error = $result->get_error_message();
		} else {
			$notice = $result;
		}
	}

	$current = defined( 'TEHILIM_VERSION' ) ? TEHILIM_VERSION : wp_get_theme()->get( 'Version' );
	?>
	<div class="wrap">
		<h1>עדכון תבנית תהילים</h1>

		<?php if ( $notice ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>
		<?php if ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<div style="max-width: 620px; background: #fff; border: 1px solid #EADCC6; border-radius: 8px; padding: 22px 26px; margin-top: 16px;">
			<p style="font-size: 14px;">
				<strong>גרסה מותקנת:</strong> <?php echo esc_html( $current ); ?>
			</p>
			<p>
				כדי לעדכן את התבנית לגרסה חדשה, העלו כאן את קובץ ה-ZIP של הגרסה החדשה.
				התבנית תוחלף במקום, ללא צורך ב-FTP וללא הפעלה מחדש — כל הקמפיינים, השגרירים והנתונים יישמרו.
			</p>
			<form method="POST" enctype="multipart/form-data" style="margin-top: 16px;">
				<?php wp_nonce_field( 'tehilim_update_nonce' ); ?>
				<input type="file" name="tehilim_theme_zip" accept=".zip" required>
				<p style="margin-top: 14px;">
					<button type="submit" name="tehilim_do_update" value="1" class="button button-primary">התקנת העדכון</button>
				</p>
			</form>
			<p class="description" style="margin-top: 8px;">
				מומלץ לגבות את האתר לפני עדכון. הקובץ חייב להיות ה-ZIP הרשמי של תבנית תהילים.
			</p>
		</div>
	</div>
	<?php
}

/**
 * Validate the uploaded ZIP and copy it over the active theme.
 *
 * @return string|WP_Error Success message or error.
 */
function tehilim_process_uploaded_theme() {
	if ( empty( $_FILES['tehilim_theme_zip'] ) || ! isset( $_FILES['tehilim_theme_zip']['tmp_name'] ) ) {
		return new WP_Error( 'no_file', 'לא נבחר קובץ.' );
	}

	$file = $_FILES['tehilim_theme_zip'];

	if ( ! empty( $file['error'] ) ) {
		return new WP_Error( 'upload_error', 'העלאת הקובץ נכשלה (קוד ' . intval( $file['error'] ) . ').' );
	}

	$check = wp_check_filetype( $file['name'], array( 'zip' => 'application/zip' ) );
	if ( 'zip' !== $check['ext'] ) {
		return new WP_Error( 'not_zip', 'יש להעלות קובץ ZIP בלבד.' );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	// Move the upload out of the transient tmp location
	$moved = wp_handle_upload( $file, array(
		'test_form' => false,
		'mimes'     => array( 'zip' => 'application/zip' ),
	) );

	if ( ! $moved || ! empty( $moved['error'] ) ) {
		return new WP_Error( 'move_failed', 'שמירת הקובץ נכשלה: ' . ( $moved['error'] ?? 'שגיאה לא ידועה' ) );
	}

	$zip_path = $moved['file'];

	// Init the filesystem
	if ( ! WP_Filesystem() ) {
		@unlink( $zip_path );
		return new WP_Error( 'fs_failed', 'לא ניתן לגשת למערכת הקבצים. ודאו הרשאות כתיבה.' );
	}

	global $wp_filesystem;

	// Unzip to a temporary working directory
	$work_dir = trailingslashit( get_temp_dir() ) . 'tehilim-update-' . wp_generate_password( 8, false );
	$unzipped = unzip_file( $zip_path, $work_dir );
	@unlink( $zip_path );

	if ( is_wp_error( $unzipped ) ) {
		$wp_filesystem->delete( $work_dir, true );
		return new WP_Error( 'unzip_failed', 'פתיחת ה-ZIP נכשלה: ' . $unzipped->get_error_message() );
	}

	// Locate the folder that actually contains style.css (handles both a
	// wrapping folder and a flat zip)
	$source = tehilim_find_theme_root( $work_dir );
	if ( ! $source ) {
		$wp_filesystem->delete( $work_dir, true );
		return new WP_Error( 'no_style', 'לא נמצא style.css בקובץ. ודאו שזהו קובץ התבנית התקין.' );
	}

	// Confirm it's really the Tehilim theme (never let an arbitrary zip
	// overwrite the active theme)
	$style = $wp_filesystem->get_contents( trailingslashit( $source ) . 'style.css' );
	if ( false === $style || false === strpos( $style, 'Theme Name: Tehilim' ) ) {
		$wp_filesystem->delete( $work_dir, true );
		return new WP_Error( 'wrong_theme', 'הקובץ אינו תבנית תהילים תקינה.' );
	}

	// Read the incoming version for the success message
	$new_version = '';
	if ( preg_match( '/Version:\s*([0-9.]+)/', $style, $m ) ) {
		$new_version = $m[1];
	}

	// Copy over the active theme directory
	$dest = get_template_directory();

	// Remove old files first so deletions in the new version take effect,
	// then copy the new tree in.
	$copied = tehilim_overwrite_directory( $source, $dest );

	$wp_filesystem->delete( $work_dir, true );

	if ( is_wp_error( $copied ) ) {
		return $copied;
	}

	// Bust caches so the new assets load
	delete_transient( 'tehilim_site_stats' );

	return $new_version
		? sprintf( 'התבנית עודכנה בהצלחה לגרסה %s! רעננו את העמוד.', $new_version )
		: 'התבנית עודכנה בהצלחה! רעננו את העמוד.';
}

/**
 * Find the directory inside $base that contains style.css (depth ≤ 2).
 */
function tehilim_find_theme_root( $base ) {
	global $wp_filesystem;

	if ( $wp_filesystem->exists( trailingslashit( $base ) . 'style.css' ) ) {
		return untrailingslashit( $base );
	}

	$list = $wp_filesystem->dirlist( $base );
	if ( ! $list ) {
		return '';
	}

	foreach ( $list as $entry ) {
		if ( 'd' === $entry['type'] ) {
			$sub = trailingslashit( $base ) . $entry['name'];
			if ( $wp_filesystem->exists( trailingslashit( $sub ) . 'style.css' ) ) {
				return untrailingslashit( $sub );
			}
		}
	}

	return '';
}

/**
 * Overwrite $dest with the contents of $source: clear the destination
 * first (so removed files disappear), then copy the new tree in.
 */
function tehilim_overwrite_directory( $source, $dest ) {
	global $wp_filesystem;

	$source = trailingslashit( $source );
	$dest   = trailingslashit( $dest );

	// Clear existing theme files (keep the directory itself)
	$existing = $wp_filesystem->dirlist( $dest );
	if ( $existing ) {
		foreach ( $existing as $entry ) {
			$wp_filesystem->delete( $dest . $entry['name'], true );
		}
	}

	// Recursive copy
	$result = copy_dir( $source, $dest );
	if ( is_wp_error( $result ) ) {
		return new WP_Error( 'copy_failed', 'העתקת הקבצים נכשלה: ' . $result->get_error_message() );
	}

	return true;
}
