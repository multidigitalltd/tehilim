<?php
/**
 * "ניהול תפילות" — a convenient admin dashboard for entering prayers.
 *
 * Gives the site owner a single, simple screen to add / edit / delete
 * prayers without touching the block editor: pick a category, type a
 * title, an optional short description and intro, and the prayer text.
 * Line breaks are preserved automatically.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the "ניהול תפילות" submenu under the Tehilim menu.
 */
function tehilim_prayers_admin_menu() {
	add_submenu_page(
		'tehilim-settings',
		'ניהול תפילות',
		'ניהול תפילות',
		'manage_options',
		'tehilim-prayers',
		'tehilim_prayers_admin_page'
	);
}
add_action( 'admin_menu', 'tehilim_prayers_admin_menu', 20 );

/**
 * Build the content HTML from intro + body (preserving line breaks).
 */
function tehilim_prayers_admin_build_content( $intro, $body ) {
	$content = '';
	$intro   = trim( (string) $intro );
	$body    = trim( (string) $body );
	if ( '' !== $intro ) {
		$content .= '<p class="prayer-intro-text">' . esc_html( $intro ) . "</p>\n\n";
	}
	if ( '' !== $body ) {
		$content .= '<div class="prayer-body">' . nl2br( esc_html( $body ) ) . '</div>';
	}
	return $content;
}

/**
 * Turn stored prayer content back into plain intro + body for editing.
 *
 * @return array{intro:string,body:string}
 */
function tehilim_prayers_admin_parse_content( $html ) {
	$intro = '';
	$body  = '';

	if ( preg_match( '/<p class="prayer-intro-text">(.*?)<\/p>/s', $html, $m ) ) {
		$intro = trim( wp_specialchars_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES ) );
	}
	if ( preg_match( '/<div class="prayer-body">(.*?)<\/div>/s', $html, $m ) ) {
		$raw  = preg_replace( '/<br\s*\/?>/i', "\n", $m[1] );
		$body = trim( wp_specialchars_decode( wp_strip_all_tags( $raw ), ENT_QUOTES ) );
	}
	// Fallback: no known markup — treat everything as the body.
	if ( '' === $intro && '' === $body ) {
		$raw  = preg_replace( '/<br\s*\/?>/i', "\n", $html );
		$body = trim( wp_specialchars_decode( wp_strip_all_tags( $raw ), ENT_QUOTES ) );
	}
	return array( 'intro' => $intro, 'body' => $body );
}

/**
 * Render + handle the prayers dashboard.
 */
function tehilim_prayers_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized' );
	}

	$notice   = '';
	$edit_id  = 0;
	$form      = array( 'title' => '', 'cat' => '', 'excerpt' => '', 'intro' => '', 'body' => '' );

	// --- Handle delete (nonce-protected link) ---
	if ( isset( $_GET['delete'] ) ) {
		$del = absint( $_GET['delete'] );
		check_admin_referer( 'tehilim_delete_prayer_' . $del );
		if ( $del && get_post_type( $del ) === 'prayer' ) {
			wp_delete_post( $del, true );
			$notice = array( 'success', 'התפילה נמחקה.' );
		}
	}

	// --- Handle save (add or update) ---
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['tehilim_prayer_save'] ) ) {
		check_admin_referer( 'tehilim_prayer_save' );

		$prayer_id = absint( $_POST['prayer_id'] ?? 0 );
		$title     = sanitize_text_field( wp_unslash( $_POST['prayer_title'] ?? '' ) );
		$cat_slug  = sanitize_text_field( wp_unslash( $_POST['prayer_cat'] ?? '' ) );
		$excerpt   = sanitize_text_field( wp_unslash( $_POST['prayer_excerpt'] ?? '' ) );
		$intro     = sanitize_textarea_field( wp_unslash( $_POST['prayer_intro'] ?? '' ) );
		$body      = sanitize_textarea_field( wp_unslash( $_POST['prayer_body'] ?? '' ) );

		if ( '' === $title || '' === trim( $body ) ) {
			$notice = array( 'error', 'יש למלא לפחות כותרת ונוסח תפילה.' );
			$form   = compact( 'title', 'cat', 'excerpt', 'intro', 'body' );
			$form['cat'] = $cat_slug;
			$edit_id = $prayer_id;
		} else {
			$content = tehilim_prayers_admin_build_content( $intro, $body );
			$args    = array(
				'post_type'    => 'prayer',
				'post_title'   => $title,
				'post_status'  => 'publish',
				'post_content' => $content,
				'post_excerpt' => $excerpt,
			);
			if ( $prayer_id && get_post_type( $prayer_id ) === 'prayer' ) {
				$args['ID'] = $prayer_id;
				$saved_id   = wp_update_post( $args, true );
			} else {
				$saved_id = wp_insert_post( $args, true );
			}

			if ( ! is_wp_error( $saved_id ) && $saved_id ) {
				$term = $cat_slug ? get_term_by( 'slug', $cat_slug, 'prayer_cat' ) : false;
				wp_set_object_terms( $saved_id, $term ? array( (int) $term->term_id ) : array(), 'prayer_cat' );
				update_post_meta( $saved_id, '_tehilim_manual_prayer', 1 );
				$notice = array( 'success', $prayer_id ? 'התפילה עודכנה בהצלחה.' : 'התפילה נוספה בהצלחה!' );
				// After a successful save the form resets (unless we were editing).
			} else {
				$notice = array( 'error', 'שמירת התפילה נכשלה. נסו שוב.' );
				$form   = compact( 'title', 'cat', 'excerpt', 'intro', 'body' );
				$form['cat'] = $cat_slug;
				$edit_id = $prayer_id;
			}
		}
	}

	// --- Prefill for edit (GET) ---
	if ( ! $edit_id && isset( $_GET['edit'] ) ) {
		$eid = absint( $_GET['edit'] );
		if ( $eid && get_post_type( $eid ) === 'prayer' ) {
			$post    = get_post( $eid );
			$parsed  = tehilim_prayers_admin_parse_content( $post->post_content );
			$terms   = wp_get_object_terms( $eid, 'prayer_cat' );
			$edit_id = $eid;
			$form    = array(
				'title'   => $post->post_title,
				'cat'     => ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '',
				'excerpt' => $post->post_excerpt,
				'intro'   => $parsed['intro'],
				'body'    => $parsed['body'],
			);
		}
	}

	$cats     = get_terms( array( 'taxonomy' => 'prayer_cat', 'hide_empty' => false ) );
	$base_url = admin_url( 'admin.php?page=tehilim-prayers' );
	?>
	<div class="wrap tehilim-prayers-admin" dir="rtl">
		<h1><?php echo $edit_id ? 'עריכת תפילה' : 'ניהול תפילות'; ?></h1>
		<p class="description" style="font-size:14px;">כאן מוסיפים ומנהלים את התפילות שיוצגו באתר. בחרו קטגוריה, כתבו כותרת ונוסח — ותוכלו לערוך או למחוק בכל עת.</p>

		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice[0] ); ?> is-dismissible" style="margin:16px 0;"><p><?php echo esc_html( $notice[1] ); ?></p></div>
		<?php endif; ?>

		<div class="tehilim-prayers-admin-grid">

			<div class="tehilim-prayers-admin-form-wrap">
				<h2><?php echo $edit_id ? 'עריכת התפילה' : 'הוספת תפילה חדשה'; ?></h2>
				<form method="post" action="<?php echo esc_url( $base_url ); ?>">
					<?php wp_nonce_field( 'tehilim_prayer_save' ); ?>
					<input type="hidden" name="prayer_id" value="<?php echo esc_attr( $edit_id ); ?>">

					<p>
						<label for="prayer_cat"><strong>קטגוריה</strong></label><br>
						<select name="prayer_cat" id="prayer_cat" style="width:100%;max-width:420px;">
							<option value="">— בחרו קטגוריה —</option>
							<?php foreach ( $cats as $c ) : ?>
								<option value="<?php echo esc_attr( $c->slug ); ?>" <?php selected( $form['cat'], $c->slug ); ?>><?php echo esc_html( $c->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label for="prayer_title"><strong>כותרת התפילה</strong></label><br>
						<input type="text" name="prayer_title" id="prayer_title" value="<?php echo esc_attr( $form['title'] ); ?>" style="width:100%;max-width:420px;" required placeholder="לדוגמה: תפילה לרפואה שלמה">
					</p>

					<p>
						<label for="prayer_excerpt"><strong>תיאור קצר</strong> <span style="color:#888;font-weight:normal;">(לא חובה — מוצג בכרטיסיות ובחיפוש)</span></label><br>
						<input type="text" name="prayer_excerpt" id="prayer_excerpt" value="<?php echo esc_attr( $form['excerpt'] ); ?>" style="width:100%;max-width:420px;" maxlength="160" placeholder="שורה אחת שמתארת את התפילה">
					</p>

					<p>
						<label for="prayer_intro"><strong>הקדמה</strong> <span style="color:#888;font-weight:normal;">(לא חובה — מתי אומרים / רקע)</span></label><br>
						<textarea name="prayer_intro" id="prayer_intro" rows="3" style="width:100%;max-width:600px;" placeholder="למשל: תפילה זו נאמרת על החולה ומזכירים בה את שמו ושם אמו."><?php echo esc_textarea( $form['intro'] ); ?></textarea>
					</p>

					<p>
						<label for="prayer_body"><strong>נוסח התפילה</strong></label><br>
						<textarea name="prayer_body" id="prayer_body" rows="12" style="width:100%;max-width:600px;font-size:17px;line-height:1.9;" required placeholder="הדביקו או כתבו כאן את נוסח התפילה. כל שורה חדשה תישמר כפי שהיא."><?php echo esc_textarea( $form['body'] ); ?></textarea>
						<br><span class="description">מעברי שורה נשמרים אוטומטית — אין צורך בעיצוב.</span>
					</p>

					<p style="margin-top:18px;">
						<button type="submit" name="tehilim_prayer_save" value="1" class="button button-primary button-hero"><?php echo $edit_id ? 'שמירת השינויים' : 'הוספת התפילה'; ?></button>
						<?php if ( $edit_id ) : ?>
							<a href="<?php echo esc_url( $base_url ); ?>" class="button" style="margin-right:8px;">ביטול / תפילה חדשה</a>
						<?php endif; ?>
					</p>
				</form>
			</div>

			<div class="tehilim-prayers-admin-list-wrap">
				<h2>התפילות הקיימות</h2>
				<?php
				$all = get_posts( array(
					'post_type'      => 'prayer',
					'post_status'    => array( 'publish', 'draft', 'pending' ),
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
				) );

				if ( ! $all ) {
					echo '<p style="color:#666;">עדיין לא הוספתם תפילות. מלאו את הטופס כדי להוסיף את התפילה הראשונה.</p>';
				} else {
					// Group by category name.
					$groups = array();
					foreach ( $all as $p ) {
						$terms = wp_get_object_terms( $p->ID, 'prayer_cat' );
						$label = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'ללא קטגוריה';
						$groups[ $label ][] = $p;
					}
					ksort( $groups );
					echo '<p class="description">סה״כ ' . esc_html( count( $all ) ) . ' תפילות.</p>';
					foreach ( $groups as $label => $items ) {
						echo '<h3 style="margin:18px 0 6px;color:#A94B2E;">' . esc_html( $label ) . ' <span style="color:#999;font-weight:normal;">(' . esc_html( count( $items ) ) . ')</span></h3>';
						echo '<ul class="tehilim-prayers-admin-list">';
						foreach ( $items as $p ) {
							$edit_link = esc_url( add_query_arg( 'edit', $p->ID, $base_url ) );
							$del_link  = esc_url( wp_nonce_url( add_query_arg( 'delete', $p->ID, $base_url ), 'tehilim_delete_prayer_' . $p->ID ) );
							$view_link = esc_url( get_permalink( $p->ID ) );
							echo '<li>';
							echo '<span class="tp-title">' . esc_html( $p->post_title ? $p->post_title : '(ללא כותרת)' ) . '</span>';
							echo '<span class="tp-actions">';
							echo '<a href="' . $edit_link . '">עריכה</a> · ';
							echo '<a href="' . $view_link . '" target="_blank">צפייה</a> · ';
							echo '<a href="' . $del_link . '" class="tp-del" onclick="return confirm(\'למחוק את התפילה לצמיתות?\');">מחיקה</a>';
							echo '</span>';
							echo '</li>';
						}
						echo '</ul>';
					}
				}
				?>
			</div>

		</div>
	</div>

	<style>
		.tehilim-prayers-admin-grid { display:grid; grid-template-columns: 1.1fr 0.9fr; gap:32px; margin-top:20px; align-items:start; }
		.tehilim-prayers-admin-form-wrap, .tehilim-prayers-admin-list-wrap { background:#fff; border:1px solid #e2d7c4; border-radius:12px; padding:22px 26px; box-shadow:0 4px 14px rgba(70,50,25,0.05); }
		.tehilim-prayers-admin-form-wrap h2, .tehilim-prayers-admin-list-wrap h2 { margin-top:0; color:#C05A3A; }
		.tehilim-prayers-admin-form-wrap label { display:inline-block; margin-bottom:4px; }
		.tehilim-prayers-admin-list { margin:0 0 8px; }
		.tehilim-prayers-admin-list li { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:8px 10px; border-bottom:1px solid #f0e7d6; }
		.tehilim-prayers-admin-list li:hover { background:#faf4ea; }
		.tehilim-prayers-admin-list .tp-title { font-weight:600; }
		.tehilim-prayers-admin-list .tp-actions { white-space:nowrap; font-size:13px; }
		.tehilim-prayers-admin-list .tp-del { color:#b32d0f; }
		@media (max-width:1100px){ .tehilim-prayers-admin-grid { grid-template-columns:1fr; } }
	</style>
	<?php
}
