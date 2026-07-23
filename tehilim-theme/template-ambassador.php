<?php
/**
 * Template Name: Ambassador Referral
 * Description: Personalized ambassador referral page — exact design match (ambassador-public.html)
 */
get_header();

$ambassador_name = get_query_var( 'ambassador' );
$campaign_name   = get_query_var( 'name' );

$campaign   = null;
$ambassador = null;

if ( $campaign_name && $ambassador_name ) {
	$campaign_posts = get_posts( array(
		'post_type'   => 'campaign',
		'name'        => $campaign_name,
		'numberposts' => 1,
	) );
	$ambassador_posts = get_posts( array(
		'post_type'   => 'ambassador',
		'name'        => $ambassador_name,
		'numberposts' => 1,
	) );
	if ( $campaign_posts && $ambassador_posts ) {
		$campaign   = $campaign_posts[0];
		$ambassador = $ambassador_posts[0];
	}
}

if ( $campaign && $ambassador ) :

	$campaign_id  = $campaign->ID;
	$amb_id       = $ambassador->ID;
	$amb_title    = $ambassador->post_title;
	$progress     = tehilim_get_campaign_progress( $campaign_id );

	$chapters_per_book = defined( 'TEHILIM_CHAPTERS_PER_BOOK' ) ? TEHILIM_CHAPTERS_PER_BOOK : 150;
	$in_book           = intval( $progress['chapters_done'] );
	$remaining_in_book = max( 0, $chapters_per_book - $in_book );
	$goal_chapters     = max( 1, intval( $progress['goal_books'] ) * $chapters_per_book );

	// Occasion
	$occasions     = get_the_terms( $campaign_id, 'occasion' );
	$occasion_name = ( $occasions && ! is_wp_error( $occasions ) ) ? $occasions[0]->name : '';

	// Ambassador metrics
	global $wpdb;
	$rec_table       = $wpdb->prefix . 'tehilim_recitations';
	$amb_chapters    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `%i` WHERE campaign_id = %d AND ambassador_id = %d", $rec_table, $campaign_id, $amb_id ) );
	$amb_reciters    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT reciter_name) FROM `%i` WHERE campaign_id = %d AND ambassador_id = %d AND reciter_name IS NOT NULL AND reciter_name <> ''", $rec_table, $campaign_id, $amb_id ) );
	$participants    = tehilim_get_campaign_participants( $campaign_id );
	$amb_ring        = min( 100, round( $amb_chapters / $goal_chapters * 100 ) );

	// Ambassadors + rank
	$ambassadors       = tehilim_get_top_ambassadors( $campaign_id, 20 );
	$ambassadors_count = count( $ambassadors );
	$max_amb_count     = 0;
	$amb_rank          = '—';
	foreach ( $ambassadors as $i => $amb ) {
		if ( $amb['count'] > $max_amb_count ) {
			$max_amb_count = $amb['count'];
		}
		if ( $amb['id'] === $amb_id ) {
			$amb_rank = $i + 1;
		}
	}

	$initial   = function_exists( 'mb_substr' ) ? mb_substr( $amb_title, 0, 1, 'UTF-8' ) : substr( $amb_title, 0, 1 );
	$amb_url   = home_url( '/c/' . $campaign->post_name . '/' . $ambassador->post_name );
	$campaign_url = get_permalink( $campaign_id );
	?>

<div class="campaign-detail">

	<!-- Hero -->
	<section class="amb-hero">
		<div class="amb-hero-inner">
			<a class="back-link" href="<?php echo esc_url( $campaign_url ); ?>">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#8A6B4A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"></path></svg>
				<?php esc_html_e( 'לעמוד הקמפיין המלא', 'tehilim' ); ?>
			</a>
			<div class="amb-hero-row">
				<div class="amb-hero-text">
					<div class="campaign-badge">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#B9822B" stroke-width="1.9"><circle cx="9" cy="8" r="3"></circle><path d="M3 19c0-3 2.7-5.5 6-5.5S15 16 15 19M17 5a3 3 0 0 1 0 6M21 19c0-2.3-1.4-4.3-3.5-5"></path></svg>
						<?php esc_html_e( 'הוזמנתם על ידי שגריר/ה בקמפיין', 'tehilim' ); ?>
					</div>
					<div class="amb-hero-name-row">
						<div class="amb-hero-avatar"><?php echo esc_html( $initial ); ?></div>
						<div>
							<h1 class="amb-hero-name"><?php echo esc_html( $amb_title ); ?></h1>
							<div class="amb-hero-sub"><?php esc_html_e( 'מזמין/ה אתכם להצטרף', 'tehilim' ); ?></div>
						</div>
					</div>
					<p class="amb-hero-lead">
						<?php
						printf(
							/* translators: 1: campaign name, 2: occasion */
							esc_html__( 'לקמפיין התהילים למען %1$s%2$s.', 'tehilim' ),
							'<b>' . esc_html( $campaign->post_title ) . '</b>',
							$occasion_name ? ' — ' . esc_html( $occasion_name ) : ''
						);
						?>
					</p>
					<p class="amb-hero-desc">
						<?php printf( esc_html__( 'כל פרק שתאמרו כאן נזקף לזכות היעד של %s בקמפיין. יחד מגיעים רחוק יותר.', 'tehilim' ), esc_html( $amb_title ) ); ?>
					</p>
					<div class="amb-hero-actions">
						<button class="btn-amb-join"><?php esc_html_e( 'הצטרפו ואמרו תהילים', 'tehilim' ); ?></button>
						<button class="btn-amb-share btn-share" data-share-type="whatsapp" data-share-url="<?php echo esc_url( $amb_url ); ?>" data-share-text="<?php echo esc_attr( $amb_title . ' · ' . $campaign->post_title ); ?>">
							<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#EFC978" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20a8 8 0 1 0-6.9-4L4 20l4-1.1A8 8 0 0 0 12 20z"></path></svg>
							<?php esc_html_e( 'שתפו הלאה', 'tehilim' ); ?>
						</button>
					</div>
				</div>
				<div class="amb-goal-card">
					<div class="amb-goal-label"><?php printf( esc_html__( 'היעד האישי של %s', 'tehilim' ), esc_html( $amb_title ) ); ?></div>
					<div class="amb-ring" style="background:conic-gradient(#D9A441 0deg,#C05A3A <?php echo esc_attr( $amb_ring * 3.6 ); ?>deg,#EFE3CF <?php echo esc_attr( $amb_ring * 3.6 ); ?>deg)">
						<div class="amb-ring-inner">
							<div class="amb-ring-percent"><?php echo esc_html( $amb_ring ); ?>%</div>
							<div class="amb-ring-label"><?php esc_html_e( 'מהיעד', 'tehilim' ); ?></div>
						</div>
					</div>
					<div class="amb-goal-count"><?php printf( esc_html__( '%1$d / %2$d פרקים', 'tehilim' ), $amb_chapters, $goal_chapters ); ?></div>
					<div class="amb-goal-note"><?php printf( esc_html__( 'גויסו על ידי %s', 'tehilim' ), esc_html( $amb_title ) ); ?></div>
				</div>
			</div>
		</div>
	</section>

	<div class="campaign-layout">
		<div class="campaign-main">

			<!-- Stats row -->
			<div class="amb-stats-row">
				<div class="amb-stat">
					<div class="amb-stat-num"><?php echo esc_html( $amb_chapters ); ?></div>
					<div class="amb-stat-label"><?php esc_html_e( 'פרקים גויסו', 'tehilim' ); ?></div>
				</div>
				<div class="amb-stat">
					<div class="amb-stat-num"><?php echo esc_html( $amb_reciters ); ?></div>
					<div class="amb-stat-label"><?php esc_html_e( 'אמרו דרכי', 'tehilim' ); ?></div>
				</div>
				<div class="amb-stat">
					<div class="amb-stat-num"><?php echo esc_html( $amb_rank ); ?><small>/<?php echo esc_html( max( 1, $ambassadors_count ) ); ?></small></div>
					<div class="amb-stat-label"><?php esc_html_e( 'מקום בדירוג', 'tehilim' ); ?></div>
				</div>
			</div>

			<!-- Reader -->
			<div class="reader-card">
				<div class="reader-note">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="#C05A3A"><path d="M12 21s-7.5-4.7-10-9.3C.4 8.6 2 5 5.5 5c2 0 3.4 1.1 4.5 2.6C11 6.1 12.5 5 14.5 5 18 5 19.6 8.6 22 11.7 19.5 16.3 12 21 12 21z"></path></svg>
					<span><?php printf( esc_html__( 'כל פרק שתסמנו כאן נזקף לזכות %s בקמפיין.', 'tehilim' ), esc_html( $amb_title ) ); ?></span>
				</div>
				<div class="reader-head">
					<div class="reader-head-info">
						<div class="reader-icon">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FFF7F2" stroke-width="1.8"><path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5M12 6v13"></path></svg>
						</div>
						<div>
							<div class="reader-head-title"><?php esc_html_e( 'אמירת תהילים', 'tehilim' ); ?></div>
							<div class="reader-head-sub"><?php printf( esc_html__( 'פרק מוצע · %1$d/%2$d · נותרו %3$d פרקים', 'tehilim' ), $in_book, $chapters_per_book, $remaining_in_book ); ?></div>
						</div>
					</div>
					<button class="btn-reader-secondary btn-next">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"></path></svg>
						<?php esc_html_e( 'פרק אחר', 'tehilim' ); ?>
					</button>
				</div>
				<div class="reader-body">
					<div class="reader-body-head">
						<div class="reader-chapter-title"><?php esc_html_e( 'תהילים · פרק ק׳', 'tehilim' ); ?></div>
						<div class="reader-chapter-badge"><?php esc_html_e( 'פרק מוצע עבורכם', 'tehilim' ); ?></div>
					</div>
					<div class="chapter-text">
						<div class="chapter-verse"><span class="chapter-verse-num">א</span>מִזְמוֹר לְתוֹדָה, הָרִיעוּ לַה׳ כָּל הָאָרֶץ.</div>
						<div class="chapter-verse"><span class="chapter-verse-num">ב</span>עִבְדוּ אֶת ה׳ בְּשִׂמְחָה, בֹּאוּ לְפָנָיו בִּרְנָנָה.</div>
						<div class="chapter-verse"><span class="chapter-verse-num">ג</span>דְּעוּ כִּי ה׳ הוּא אֱלֹהִים, הוּא עָשָׂנוּ וְלוֹ אֲנַחְנוּ, עַמּוֹ וְצֹאן מַרְעִיתוֹ.</div>
						<div class="chapter-verse"><span class="chapter-verse-num">ד</span>בֹּאוּ שְׁעָרָיו בְּתוֹדָה חֲצֵרֹתָיו בִּתְהִלָּה, הוֹדוּ לוֹ בָּרְכוּ שְׁמוֹ.</div>
						<div class="chapter-verse"><span class="chapter-verse-num">ה</span>כִּי טוֹב ה׳ לְעוֹלָם חַסְדּוֹ, וְעַד דֹּר וָדֹר אֱמוּנָתוֹ.</div>
					</div>
				</div>
				<div class="reader-footer">
					<input class="reader-name-input" type="text" value="" placeholder="<?php esc_attr_e( 'שמכם (לא חובה)', 'tehilim' ); ?>">
					<button class="btn-reader-said btn-say-chapter" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>" data-ambassador-id="<?php echo esc_attr( $amb_id ); ?>" data-chapter-number="100">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFF7F2" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"></path></svg>
						<?php esc_html_e( 'סימנתי שאמרתי', 'tehilim' ); ?>
					</button>
				</div>
			</div>

			<!-- Campaign overview -->
			<div class="amb-overview">
				<div class="amb-overview-head">
					<div class="amb-overview-title"><?php esc_html_e( 'התמונה הגדולה · הקמפיין כולו', 'tehilim' ); ?></div>
					<a class="amb-overview-link" href="<?php echo esc_url( $campaign_url ); ?>"><?php esc_html_e( 'לעמוד הקמפיין ←', 'tehilim' ); ?></a>
				</div>
				<div class="amb-overview-progresshead">
					<div class="amb-overview-percent"><?php echo esc_html( $progress['progress_percent'] ); ?>%</div>
					<div class="amb-overview-books"><?php printf( esc_html__( '%1$d מתוך %2$d ספרים', 'tehilim' ), (int) $progress['books_done'], (int) $progress['goal_books'] ); ?></div>
				</div>
				<div class="amb-overview-track"><div class="amb-overview-track-fill" style="width:<?php echo esc_attr( $progress['progress_percent'] ); ?>%"></div></div>
				<div class="amb-overview-stats">
					<div class="amb-overview-stat"><div class="amb-overview-stat-num"><?php echo esc_html( $participants ); ?></div><div class="amb-overview-stat-label"><?php esc_html_e( 'משתתפים', 'tehilim' ); ?></div></div>
					<div class="amb-overview-stat"><div class="amb-overview-stat-num"><?php echo esc_html( $progress['total_chapters'] ); ?></div><div class="amb-overview-stat-label"><?php esc_html_e( 'פרקים', 'tehilim' ); ?></div></div>
					<div class="amb-overview-stat"><div class="amb-overview-stat-num"><?php echo esc_html( $ambassadors_count ); ?></div><div class="amb-overview-stat-label"><?php esc_html_e( 'שגרירים', 'tehilim' ); ?></div></div>
				</div>
			</div>
		</div>

		<!-- Sidebar -->
		<aside class="campaign-sidebar">
			<div class="share-card">
				<div class="share-card-title">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9"><circle cx="18" cy="5" r="2.6"></circle><circle cx="6" cy="12" r="2.6"></circle><circle cx="18" cy="19" r="2.6"></circle><path d="M8.3 10.7l7.4-4.4M8.3 13.3l7.4 4.4"></path></svg>
					<?php printf( esc_html__( 'שתפו את הקישור של %s', 'tehilim' ), esc_html( $amb_title ) ); ?>
				</div>
				<div class="share-card-desc"><?php printf( esc_html__( 'הפיצו את העמוד הזה — כל מי שייכנס דרכו יתרום ליעד של %s.', 'tehilim' ), esc_html( $amb_title ) ); ?></div>
				<div class="share-link-box">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#B9822B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"></path><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"></path></svg>
					<span><?php echo esc_html( urldecode( $amb_url ) ); ?></span>
				</div>
				<button class="btn-share-whatsapp btn-share" data-share-type="whatsapp" data-share-url="<?php echo esc_url( $amb_url ); ?>" data-share-text="<?php echo esc_attr( $amb_title . ' · ' . $campaign->post_title ); ?>">
					<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#EFC978" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20a8 8 0 1 0-6.9-4L4 20l4-1.1A8 8 0 0 0 12 20z"></path></svg>
					<?php esc_html_e( 'שיתוף ב-WhatsApp', 'tehilim' ); ?>
				</button>
				<button class="btn-share-copy btn-share" data-share-type="copy" data-share-url="<?php echo esc_url( $amb_url ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2.5"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
					<?php esc_html_e( 'העתקת לינק', 'tehilim' ); ?>
				</button>
			</div>

			<div class="leaderboard-card">
				<div class="leaderboard-card-title">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="#D9A441"><path d="M6 3h12v4a4 4 0 0 1-3 3.9V13h2v2H7v-2h2v-2.1A4 4 0 0 1 6 7z"></path><path d="M7 17h10v3H7z"></path></svg>
					<?php esc_html_e( 'לוח שגרירים', 'tehilim' ); ?>
				</div>
				<div class="leaderboard-list">
					<?php if ( $ambassadors ) : ?>
						<?php foreach ( $ambassadors as $i => $amb ) :
							$rank     = $i + 1;
							$bar      = $max_amb_count > 0 ? round( $amb['count'] / $max_amb_count * 100 ) : 0;
							$active   = ( $amb['id'] === $amb_id ) ? ' active' : '';
							$row_link = home_url( '/c/' . $campaign->post_name . '/' . get_post_field( 'post_name', $amb['id'] ) );
							?>
							<a class="leaderboard-btn<?php echo esc_attr( $active ); ?>" href="<?php echo esc_url( $row_link ); ?>">
								<div class="leaderboard-row-top">
									<div class="leaderboard-row-info">
										<span class="leaderboard-rank"><?php echo esc_html( $rank ); ?></span>
										<span class="leaderboard-name"><?php echo esc_html( $amb['name'] ); ?></span>
									</div>
									<div class="leaderboard-count"><?php echo esc_html( $amb['count'] ); ?></div>
								</div>
								<div class="leaderboard-bar"><div class="leaderboard-bar-fill" style="width:<?php echo esc_attr( $bar ); ?>%"></div></div>
							</a>
						<?php endforeach; ?>
					<?php else : ?>
						<div class="share-card-desc"><?php esc_html_e( 'אין שגרירים עדיין.', 'tehilim' ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</aside>
	</div>
</div>

	<?php
else :
	?>
	<div class="create-page" style="text-align:center">
		<h1 class="create-title"><?php esc_html_e( 'הקישור אינו תקין', 'tehilim' ); ?></h1>
		<p class="create-subtitle"><?php esc_html_e( 'קמפיין או שגריר לא נמצאו.', 'tehilim' ); ?></p>
	</div>
	<?php
endif;

get_footer();
