<?php
/**
 * Admin Settings & Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seed 30 lively demo campaigns: ambassadors, chapters, books, activity history.
 * Everything is tagged with _tehilim_demo meta so it can be removed cleanly.
 */
function tehilim_seed_demo_content() {
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 180 );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'tehilim_recitations';
	tehilim_create_recitations_table( true );

	$first_names_m = array( 'משה', 'דוד', 'יוסף', 'אברהם', 'יעקב', 'שלמה', 'חיים', 'מאיר', 'אליהו', 'ישראל', 'נתן', 'עקיבא', 'רפאל', 'שמעון', 'בנימין' );
	$first_names_f = array( 'שרה', 'רבקה', 'רחל', 'לאה', 'מרים', 'חנה', 'אסתר', 'חיה', 'דבורה', 'תמר', 'יעל', 'נעמי', 'ברכה', 'שושנה', 'אילנה' );
	$mothers       = array( 'שרה', 'רבקה', 'רחל', 'לאה', 'מרים', 'חנה', 'אסתר', 'חיה', 'פנינה', 'גילה', 'ברכה', 'שושנה' );
	$organizers    = array( 'משפחת כהן', 'משפחת לוי', 'קהילת אהבת ישראל', 'בית הכנסת המרכזי', 'סמינר בנות חיל', 'ישיבת אור התורה', 'משפחת אזולאי', 'קהילת שערי תפילה', 'משפחת פרץ', 'חברות תהילים שכונתי', 'משפחת ביטון', 'כולל זכרון משה' );
	$amb_names     = array( 'ריקי לוי', 'שרה כהן', 'דוד פרץ', 'מיכל אזולאי', 'יוסי ביטון', 'רחלי מזרחי', 'אבי דהן', 'נעמה שלום', 'שמעון עמר', 'טליה ברק', 'אליהו חדד', 'אפרת גבאי', 'מוישי קליין', 'חני רוזן', 'יעקב אוחיון' );
	$reciters      = array( 'שירה', 'יעל', 'משה', 'רבקה', 'דוד', 'אסתר', 'חיים', 'תמר', 'יוסף', 'נעמי', 'אילה', 'בני', 'רות', 'עדי', 'מלכה', 'צבי', 'הדס', 'איתן' );

	$occasion_config = array(
		'refua'   => array( 'ded' => 'לרפואה שלמה בתוך שאר חולי ישראל', 'prefix' => true ),
		'iluy'    => array( 'ded' => 'לעילוי נשמתו הטהורה', 'prefix' => true ),
		'zivug'   => array( 'ded' => 'לזיווג הגון במהרה', 'prefix' => true ),
		'parnasa' => array( 'ded' => 'לפרנסה טובה בשפע וברווח', 'prefix' => true ),
		'zchut'   => array( 'ded' => 'לזכות ולהצלחה בכל מעשי ידיו', 'prefix' => true ),
		'event'   => array( 'ded' => 'לרגל השמחה הקרובה בשעה טובה', 'prefix' => false ),
	);
	$occasion_slugs = array_keys( $occasion_config );

	$author_id = get_current_user_id();
	$created   = 0;

	for ( $i = 0; $i < 30; $i++ ) {
		$is_f   = (bool) wp_rand( 0, 1 );
		$first  = $is_f ? $first_names_f[ wp_rand( 0, count( $first_names_f ) - 1 ) ] : $first_names_m[ wp_rand( 0, count( $first_names_m ) - 1 ) ];
		$mother = $mothers[ wp_rand( 0, count( $mothers ) - 1 ) ];
		$title  = $first . ( $is_f ? ' בת ' : ' בן ' ) . $mother;

		$slug_key = $occasion_slugs[ wp_rand( 0, count( $occasion_slugs ) - 1 ) ];

		$campaign_id = wp_insert_post( array(
			'post_type'   => 'campaign',
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_author' => $author_id,
			'post_date'   => gmdate( 'Y-m-d H:i:s', time() - wp_rand( 5, 60 ) * DAY_IN_SECONDS ),
		) );

		if ( is_wp_error( $campaign_id ) || ! $campaign_id ) {
			continue;
		}
		$created++;

		$term = get_term_by( 'slug', $slug_key, 'occasion' );
		if ( $term ) {
			wp_set_object_terms( $campaign_id, $term->term_id, 'occasion' );
		}

		update_post_meta( $campaign_id, 'goal_books', wp_rand( 5, 36 ) );
		update_post_meta( $campaign_id, 'organizer_name', $organizers[ wp_rand( 0, count( $organizers ) - 1 ) ] );
		update_post_meta( $campaign_id, 'dedication_text', $occasion_config[ $slug_key ]['ded'] );
		update_post_meta( $campaign_id, '_tehilim_demo', 1 );

		// 2-5 approved ambassadors, each with a personal goal
		$amb_ids   = array();
		$amb_count = wp_rand( 2, 5 );
		$pool      = $amb_names;
		shuffle( $pool );
		$palette = array( '#C05A3A', '#D9A441', '#8A6B4A', '#B08968' );

		for ( $a = 0; $a < $amb_count; $a++ ) {
			$amb_id = wp_insert_post( array(
				'post_type'   => 'ambassador',
				'post_title'  => $pool[ $a ],
				'post_status' => 'publish',
				'post_parent' => $campaign_id,
				'post_author' => $author_id,
			) );
			if ( $amb_id && ! is_wp_error( $amb_id ) ) {
				update_post_meta( $amb_id, 'campaign_id', $campaign_id );
				update_post_meta( $amb_id, 'email', 'demo' . $amb_id . '@example.com' );
				update_post_meta( $amb_id, 'avatar_color', $palette[ $amb_id % 4 ] );
				update_post_meta( $amb_id, 'goal_books', wp_rand( 1, 4 ) );
				update_post_meta( $amb_id, '_tehilim_demo', 1 );
				$amb_ids[] = $amb_id;
			}
		}

		// Progress: 0-2 complete books + a partial book (varied so the archive looks alive)
		$books_done = wp_rand( 0, 100 ) < 40 ? wp_rand( 1, 2 ) : 0;
		$partial    = wp_rand( 12, 140 );

		$rows = array();
		for ( $b = 0; $b < $books_done; $b++ ) {
			for ( $ch = 1; $ch <= TEHILIM_CHAPTERS_PER_BOOK; $ch++ ) {
				$rows[] = $ch;
			}
		}
		$partial_chapters = array_slice( range( 1, TEHILIM_CHAPTERS_PER_BOOK ), 0, $partial );
		shuffle( $partial_chapters );
		$rows = array_merge( $rows, $partial_chapters );

		// Batched inserts (200 per statement)
		$values = array();
		foreach ( $rows as $ch ) {
			$amb   = ( $amb_ids && wp_rand( 0, 100 ) < 65 ) ? $amb_ids[ wp_rand( 0, count( $amb_ids ) - 1 ) ] : 'NULL';
			$named = wp_rand( 0, 100 ) < 55;
			$name  = $named ? $reciters[ wp_rand( 0, count( $reciters ) - 1 ) ] : '';
			$vk    = $named ? '' : 'demo' . wp_rand( 100000, 999999 ) . wp_rand( 100000, 999999 );
			$when  = gmdate( 'Y-m-d H:i:s', time() - wp_rand( 0, 45 * DAY_IN_SECONDS ) );

			$values[] = $wpdb->prepare(
				'(%d, ' . ( 'NULL' === $amb ? 'NULL' : '%d' ) . ', %d, %s, %s, %s)',
				...( 'NULL' === $amb
					? array( $campaign_id, $ch, $name, $vk, $when )
					: array( $campaign_id, $amb, $ch, $name, $vk, $when ) )
			);

			if ( count( $values ) >= 200 ) {
				$wpdb->query( "INSERT INTO `{$table}` (campaign_id, ambassador_id, chapter_number, reciter_name, visitor_key, created_at) VALUES " . implode( ',', $values ) );
				$values = array();
			}
		}
		if ( $values ) {
			$wpdb->query( "INSERT INTO `{$table}` (campaign_id, ambassador_id, chapter_number, reciter_name, visitor_key, created_at) VALUES " . implode( ',', $values ) );
		}

		tehilim_clear_campaign_caches( $campaign_id );
	}

	delete_transient( 'tehilim_site_stats' );

	return $created;
}

/**
 * Remove everything the seeder created (campaigns, ambassadors, recitations).
 */
function tehilim_delete_demo_content() {
	global $wpdb;
	$table = $wpdb->prefix . 'tehilim_recitations';

	$demo_campaigns = get_posts( array(
		'post_type'      => 'campaign',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'meta_key'       => '_tehilim_demo',
		'fields'         => 'ids',
	) );

	foreach ( $demo_campaigns as $cid ) {
		$wpdb->delete( $table, array( 'campaign_id' => $cid ), array( '%d' ) );
		tehilim_clear_campaign_caches( $cid );
		wp_delete_post( $cid, true );
	}

	$demo_ambs = get_posts( array(
		'post_type'      => 'ambassador',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'meta_key'       => '_tehilim_demo',
		'fields'         => 'ids',
	) );
	foreach ( $demo_ambs as $aid ) {
		wp_delete_post( $aid, true );
	}

	delete_transient( 'tehilim_site_stats' );

	return count( $demo_campaigns );
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

	// Rename the auto-added first submenu to "הגדרות"; the campaign/ambassador
	// screens nest here automatically via each CPT's show_in_menu.
	add_submenu_page(
		'tehilim-settings',
		'הגדרות',
		'הגדרות',
		'manage_options',
		'tehilim-settings',
		'tehilim_settings_page'
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

	// Demo content actions (separate form, own nonce)
	if ( isset( $_POST['tehilim_demo_action'] ) ) {
		check_admin_referer( 'tehilim_demo_nonce' );
		if ( 'seed' === $_POST['tehilim_demo_action'] ) {
			$n = tehilim_seed_demo_content();
			echo '<div class="notice notice-success"><p>נוצרו ' . esc_html( $n ) . ' קמפיינים פעילים עם שגרירים והתקדמות! <a href="' . esc_url( get_post_type_archive_link( 'campaign' ) ) . '" target="_blank">צפו בארכיון</a></p></div>';
		} elseif ( 'delete' === $_POST['tehilim_demo_action'] ) {
			$n = tehilim_delete_demo_content();
			echo '<div class="notice notice-success"><p>נמחקו ' . esc_html( $n ) . ' קמפיינים של תוכן דמו על כל הנתונים שלהם.</p></div>';
		}
	}

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! isset( $_POST['tehilim_demo_action'] ) ) {
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

		<div style="max-width: 600px; background: #fff; border: 1px solid #EADCC6; border-radius: 8px; padding: 18px 22px;">
			<h2 style="margin-top: 0;">תוכן דמו — אתר פעיל</h2>
			<?php
			$demo_count = count( get_posts( array(
				'post_type'      => 'campaign',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_key'       => '_tehilim_demo',
				'fields'         => 'ids',
			) ) );
			?>
			<p>
				יצירת <strong>30 קמפיינים פעילים</strong> עם שגרירים מאושרים, פרקים שנאמרו, ספרים שהושלמו והיסטוריית פעילות —
				כדי שהאתר ייראה חי ופעיל מהרגע הראשון. אפשר למחוק את הכל בלחיצה בכל שלב.
			</p>
			<p><strong>מצב נוכחי:</strong> <?php echo esc_html( $demo_count ); ?> קמפיינים של תוכן דמו באתר.</p>
			<form method="POST" style="display: flex; gap: 10px;" onsubmit="var f=this;setTimeout(function(){f.querySelectorAll('button').forEach(function(b){b.disabled=true;});f.insertAdjacentHTML('beforeend','<em style=\'align-self:center\'>יוצרים… זה יכול לקחת עד דקה</em>');},0);">
				<?php wp_nonce_field( 'tehilim_demo_nonce' ); ?>
				<button type="submit" name="tehilim_demo_action" value="seed" class="button button-primary">יצירת 30 קמפיינים פעילים</button>
				<?php if ( $demo_count ) : ?>
					<button type="submit" name="tehilim_demo_action" value="delete" class="button" onclick="return confirm('למחוק את כל תוכן הדמו? הפעולה אינה הפיכה.');">מחיקת כל תוכן הדמו</button>
				<?php endif; ?>
			</form>
		</div>

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
