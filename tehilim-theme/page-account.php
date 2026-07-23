<?php
/**
 * Personal Area — the user's campaigns, stats, and management
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( tehilim_login_page_url( get_permalink() ) );
	exit;
}

get_header();

$user      = wp_get_current_user();
$initial   = function_exists( 'mb_substr' ) ? mb_substr( $user->display_name, 0, 1, 'UTF-8' ) : substr( $user->display_name, 0, 1 );
$campaigns = get_posts( array(
	'post_type'      => 'campaign',
	'author'         => $user->ID,
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

$occasion_terms = get_terms( array( 'taxonomy' => 'occasion', 'hide_empty' => false ) );
if ( is_wp_error( $occasion_terms ) ) {
	$occasion_terms = array();
}

// Aggregate stats across the user's campaigns
$agg_chapters = 0;
$agg_books    = 0;
$agg_ambs     = 0;
$campaign_data = array();
foreach ( $campaigns as $c ) {
	$p    = tehilim_get_campaign_progress( $c->ID );
	$ambs = tehilim_get_top_ambassadors( $c->ID, 100 );
	$campaign_data[ $c->ID ] = array(
		'progress'    => $p,
		'ambassadors' => count( $ambs ),
		'amb_list'    => $ambs,
		'participants' => tehilim_get_campaign_participants( $c->ID ),
		'pending'     => tehilim_get_pending_ambassadors( $c->ID ),
	);
	$agg_chapters += intval( $p['total_chapters'] );
	$agg_books    += intval( $p['books_done'] );
	$agg_ambs     += count( $ambs );
}
?>

<div class="account-page page-anim">

	<!-- Hero -->
	<section class="account-hero">
		<div class="account-hero-inner">
			<div class="account-user">
				<div class="account-avatar"><?php echo esc_html( $initial ); ?></div>
				<div>
					<h1 class="account-title"><?php printf( esc_html__( 'שלום, %s', 'tehilim' ), esc_html( $user->display_name ) ); ?></h1>
					<div class="account-sub"><?php esc_html_e( 'האזור האישי · ניהול הקמפיינים שלכם', 'tehilim' ); ?></div>
				</div>
			</div>
			<div class="account-actions">
				<a class="btn-create-primary" href="<?php echo esc_url( home_url( '/create/' ) ); ?>"><?php esc_html_e( '+ קמפיין חדש', 'tehilim' ); ?></a>
				<a class="btn-account-secondary" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'התנתקות', 'tehilim' ); ?></a>
			</div>
		</div>

		<!-- Aggregate stats -->
		<div class="account-stats">
			<div class="account-stat">
				<div class="account-stat-num"><?php echo esc_html( number_format_i18n( count( $campaigns ) ) ); ?></div>
				<div class="account-stat-label"><?php esc_html_e( 'קמפיינים', 'tehilim' ); ?></div>
			</div>
			<div class="account-stat-divider"></div>
			<div class="account-stat">
				<div class="account-stat-num"><?php echo esc_html( number_format_i18n( $agg_chapters ) ); ?></div>
				<div class="account-stat-label"><?php esc_html_e( 'פרקים נאמרו', 'tehilim' ); ?></div>
			</div>
			<div class="account-stat-divider"></div>
			<div class="account-stat">
				<div class="account-stat-num"><?php echo esc_html( number_format_i18n( $agg_books ) ); ?></div>
				<div class="account-stat-label"><?php esc_html_e( 'ספרים הושלמו', 'tehilim' ); ?></div>
			</div>
			<div class="account-stat-divider"></div>
			<div class="account-stat">
				<div class="account-stat-num"><?php echo esc_html( number_format_i18n( $agg_ambs ) ); ?></div>
				<div class="account-stat-label"><?php esc_html_e( 'שגרירים', 'tehilim' ); ?></div>
			</div>
		</div>
	</section>

	<!-- Campaigns -->
	<section class="account-main">
		<h2 class="account-section-title"><?php printf( esc_html__( 'הקמפיינים שלי (%d)', 'tehilim' ), count( $campaigns ) ); ?></h2>

		<?php if ( ! $campaigns ) : ?>
			<div class="account-empty">
				<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#B9822B" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5M12 6v13"></path></svg>
				<div class="account-empty-title"><?php esc_html_e( 'עוד לא פתחתם קמפיין', 'tehilim' ); ?></div>
				<p><?php esc_html_e( 'פתחו קמפיין ראשון, הזמינו שגרירים ועקבו מכאן אחרי כל פרק שנאמר.', 'tehilim' ); ?></p>
				<a class="btn-create-primary" href="<?php echo esc_url( home_url( '/create/' ) ); ?>"><?php esc_html_e( 'פתיחת קמפיין ראשון', 'tehilim' ); ?></a>
			</div>
		<?php endif; ?>

		<div class="account-list">
			<?php foreach ( $campaigns as $c ) :
				$d         = $campaign_data[ $c->ID ];
				$p         = $d['progress'];
				$occ       = get_the_terms( $c->ID, 'occasion' );
				$occ_name  = ( $occ && ! is_wp_error( $occ ) ) ? $occ[0]->name : '';
				$occ_slug  = ( $occ && ! is_wp_error( $occ ) ) ? $occ[0]->slug : '';
				$c_url     = get_permalink( $c->ID );
				?>
				<div class="account-camp" data-campaign-id="<?php echo esc_attr( $c->ID ); ?>">
					<div class="account-camp-head">
						<div>
							<div class="account-camp-title"><?php echo esc_html( $c->post_title ); ?></div>
							<div class="account-camp-meta">
								<?php if ( $occ_name ) : ?><span class="account-camp-occ"><?php echo esc_html( $occ_name ); ?></span><?php endif; ?>
								<span><?php printf( esc_html__( 'נפתח ב-%s', 'tehilim' ), esc_html( date_i18n( 'j.n.Y', strtotime( $c->post_date ) ) ) ); ?></span>
							</div>
						</div>
						<div class="account-camp-percent"><?php echo esc_html( $p['progress_percent'] ); ?>%</div>
					</div>

					<div class="progress-track"><div class="progress-track-fill" style="width:<?php echo esc_attr( $p['progress_percent'] ); ?>%"></div></div>

					<div class="account-camp-stats">
						<span><b><?php echo esc_html( number_format_i18n( $p['books_done'] ) ); ?></b> / <?php echo esc_html( number_format_i18n( $p['goal_books'] ) ); ?> <?php esc_html_e( 'ספרים', 'tehilim' ); ?></span>
						<span><b><?php echo esc_html( number_format_i18n( $p['total_chapters'] ) ); ?></b> <?php esc_html_e( 'פרקים', 'tehilim' ); ?></span>
						<span><b><?php echo esc_html( number_format_i18n( $d['participants'] ) ); ?></b> <?php esc_html_e( 'משתתפים', 'tehilim' ); ?></span>
						<button type="button" class="account-camp-ambbtn" data-amblist-toggle>
							<b><?php echo esc_html( number_format_i18n( $d['ambassadors'] ) ); ?></b> <?php esc_html_e( 'שגרירים', 'tehilim' ); ?>
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
						</button>
					</div>

					<?php
					$camp_slug = get_post_field( 'post_name', $c->ID );
					?>
					<div class="account-amblist" hidden>
						<?php if ( $d['amb_list'] ) : ?>
							<?php foreach ( $d['amb_list'] as $ai => $amb ) :
								$amb_page = home_url( '/c/' . $camp_slug . '/' . get_post_field( 'post_name', $amb['id'] ) );
								?>
								<a class="account-amblist-row" href="<?php echo esc_url( $amb_page ); ?>">
									<span class="account-amblist-rank"><?php echo esc_html( $ai + 1 ); ?></span>
									<span class="account-amblist-name"><?php echo esc_html( $amb['name'] ); ?></span>
									<span class="account-amblist-count"><?php printf( esc_html__( '%s פרקים גויסו', 'tehilim' ), esc_html( number_format_i18n( $amb['count'] ) ) ); ?></span>
								</a>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="account-amblist-empty"><?php esc_html_e( 'עדיין אין שגרירים מאושרים בקמפיין הזה.', 'tehilim' ); ?></div>
						<?php endif; ?>
					</div>

					<div class="account-camp-actions">
						<a class="btn-account-view" href="<?php echo esc_url( $c_url ); ?>"><?php esc_html_e( 'לעמוד הקמפיין', 'tehilim' ); ?></a>
						<button type="button" class="btn-account-share btn-share" data-share-type="copy" data-share-url="<?php echo esc_url( $c_url ); ?>"><?php esc_html_e( 'העתקת קישור', 'tehilim' ); ?></button>
						<button type="button" class="btn-account-edit" data-edit-toggle><?php esc_html_e( 'ניהול ועריכה', 'tehilim' ); ?></button>
					</div>

					<?php if ( $d['pending'] ) : ?>
						<div class="account-pending">
							<div class="account-pending-title">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#B9822B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
								<?php printf( esc_html__( 'בקשות שגרירים ממתינות לאישור (%d)', 'tehilim' ), count( $d['pending'] ) ); ?>
							</div>
							<?php foreach ( $d['pending'] as $req ) : ?>
								<div class="account-pending-row">
									<div class="account-pending-info">
										<b><?php echo esc_html( $req['name'] ); ?></b>
										<span dir="ltr"><?php echo esc_html( $req['email'] ); ?></span>
										<span><?php echo esc_html( date_i18n( 'j.n.Y', strtotime( $req['date'] ) ) ); ?></span>
									</div>
									<div class="account-pending-actions">
										<button type="button" class="btn-pending-approve" data-amb-action="approve" data-ambassador-id="<?php echo esc_attr( $req['id'] ); ?>"><?php esc_html_e( 'אישור', 'tehilim' ); ?></button>
										<button type="button" class="btn-pending-reject" data-amb-action="reject" data-ambassador-id="<?php echo esc_attr( $req['id'] ); ?>"><?php esc_html_e( 'דחייה', 'tehilim' ); ?></button>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<!-- Inline edit panel -->
					<form class="form-campaign-edit account-edit" data-campaign-id="<?php echo esc_attr( $c->ID ); ?>" hidden>
						<div class="account-edit-grid">
							<div>
								<label class="create-label first" for="edit_title_<?php echo esc_attr( $c->ID ); ?>"><?php esc_html_e( 'שם ההקדשה', 'tehilim' ); ?></label>
								<input class="create-input" type="text" id="edit_title_<?php echo esc_attr( $c->ID ); ?>" name="dedication_name" value="<?php echo esc_attr( $c->post_title ); ?>" required>
							</div>
							<div>
								<label class="create-label first" for="edit_goal_<?php echo esc_attr( $c->ID ); ?>"><?php esc_html_e( 'יעד (ספרים)', 'tehilim' ); ?></label>
								<input class="create-input" type="number" id="edit_goal_<?php echo esc_attr( $c->ID ); ?>" name="goal_books" min="1" max="100" value="<?php echo esc_attr( $p['goal_books'] ); ?>">
							</div>
						</div>

						<label class="create-label first" for="edit_occ_<?php echo esc_attr( $c->ID ); ?>"><?php esc_html_e( 'מטרת הקריאה', 'tehilim' ); ?></label>
						<select class="create-input" id="edit_occ_<?php echo esc_attr( $c->ID ); ?>" name="occasion">
							<?php foreach ( $occasion_terms as $t ) : ?>
								<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $occ_slug, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
							<?php endforeach; ?>
						</select>

						<label class="create-label" for="edit_desc_<?php echo esc_attr( $c->ID ); ?>"><?php esc_html_e( 'תיאור (לא חובה)', 'tehilim' ); ?></label>
						<textarea class="create-input account-edit-desc" id="edit_desc_<?php echo esc_attr( $c->ID ); ?>" name="description" rows="3"><?php echo esc_textarea( $c->post_content ); ?></textarea>

						<div class="account-edit-imagerow">
							<label class="btn-account-view account-edit-imagebtn">
								<input type="file" class="edit-image-input" accept="image/jpeg,image/png,image/webp" hidden>
								<?php echo has_post_thumbnail( $c->ID ) ? esc_html__( 'החלפת תמונה', 'tehilim' ) : esc_html__( 'העלאת תמונה', 'tehilim' ); ?>
							</label>
							<span class="account-edit-imagename"></span>
							<?php if ( has_post_thumbnail( $c->ID ) ) : ?>
								<label class="account-edit-remove">
									<input type="checkbox" name="remove_image" value="1">
									<?php esc_html_e( 'הסרת התמונה (יוצגו פסוקי שבח)', 'tehilim' ); ?>
								</label>
							<?php endif; ?>
						</div>

						<button type="submit" class="btn-create-submit account-edit-save"><?php esc_html_e( 'שמירת שינויים', 'tehilim' ); ?></button>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
</div>

<?php
get_footer();
