<?php
/**
 * Single Campaign Template — exact design match (campaign.html)
 */
get_header();

if ( have_posts() ) :
	while ( have_posts() ) : the_post();

		$campaign_id = get_the_ID();
		$progress    = tehilim_get_campaign_progress( $campaign_id );

		$chapters_per_book = defined( 'TEHILIM_CHAPTERS_PER_BOOK' ) ? TEHILIM_CHAPTERS_PER_BOOK : 150;
		$current_book      = intval( $progress['books_done'] ) + 1;
		$in_book           = intval( $progress['chapters_done'] );
		$remaining_in_book = max( 0, $chapters_per_book - $in_book );

		// Occasion (taxonomy)
		$occasions     = get_the_terms( $campaign_id, 'occasion' );
		$occasion_name = ( $occasions && ! is_wp_error( $occasions ) ) ? $occasions[0]->name : '';

		// Ambassadors + counts
		$ambassadors       = tehilim_get_top_ambassadors( $campaign_id, 20 );
		$ambassadors_count = count( $ambassadors );
		$max_amb_count     = 0;
		foreach ( $ambassadors as $amb ) {
			if ( $amb['count'] > $max_amb_count ) {
				$max_amb_count = $amb['count'];
			}
		}

		// Participants (distinct reciters, cached helper)
		$participants = tehilim_get_campaign_participants( $campaign_id );

		// Avatar palette (matches design rank order)
		$avatar_bg = array(
			'linear-gradient(135deg,#C05A3A,#A94B2E)',
			'linear-gradient(135deg,#D9A441,#B9822B)',
			'#8A6B4A',
			'#B08968',
		);

		$share_url     = get_permalink();
		$share_text    = get_the_title();
		$campaign_slug = get_post_field( 'post_name', $campaign_id );
		?>

<div class="campaign-detail">

	<!-- Hero -->
	<section class="campaign-hero">
		<div class="campaign-hero-inner">
			<div class="campaign-hero-text">
				<?php if ( $occasion_name ) : ?>
					<div class="campaign-badge">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="#D9A441"><path d="M12 2l2.4 5.6L20 8l-4.4 4 1.3 6L12 15l-4.9 3 1.3-6L4 8l5.6-.4z"></path></svg>
						<?php echo esc_html( $occasion_name ); ?>
					</div>
				<?php endif; ?>
				<h1 class="campaign-hero-title"><?php the_title(); ?></h1>
				<?php $dedication_text = get_post_meta( $campaign_id, 'dedication_text', true ); ?>
				<?php if ( $dedication_text ) : ?>
					<div class="campaign-dedication">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C05A3A" stroke-width="1.9"><path d="M12 21s-7.5-4.7-10-9.3C.4 8.6 2 5 5.5 5c2 0 3.4 1.1 4.5 2.6C11 6.1 12.5 5 14.5 5 18 5 19.6 8.6 22 11.7 19.5 16.3 12 21 12 21z"></path></svg>
						<?php echo esc_html( $dedication_text ); ?>
					</div>
				<?php endif; ?>
				<?php if ( get_the_content() ) : ?>
					<p class="campaign-hero-desc"><?php echo esc_html( wp_strip_all_tags( get_the_content() ) ); ?></p>
				<?php endif; ?>

				<div class="amb-hero-actions campaign-hero-actions">
					<button class="btn-amb-join"><?php esc_html_e( 'הצטרפו ואמרו תהילים', 'tehilim' ); ?></button>
					<button class="btn-amb-share btn-share-modal" data-share-url="<?php echo esc_url( $share_url ); ?>" data-share-text="<?php echo esc_attr( $share_text ); ?>">
						<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#EFC978" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20a8 8 0 1 0-6.9-4L4 20l4-1.1A8 8 0 0 0 12 20z"></path></svg>
						<?php esc_html_e( 'שתפו הלאה', 'tehilim' ); ?>
					</button>
				</div>
			</div>
			<div class="campaign-hero-image<?php echo has_post_thumbnail() ? '' : ' is-verses'; ?>">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large' ); ?>
				<?php else : ?>
					<?php
					$praise_verses = tehilim_get_praise_verses();
					?>
					<div class="hero-verses" role="group" aria-label="<?php esc_attr_e( 'פסוקים בשבח אמירת תהילים', 'tehilim' ); ?>">
						<div class="hero-verses-head">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#B9822B" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5M12 6v13"></path></svg>
							<span><?php esc_html_e( 'בשבח אמירת תהילים', 'tehilim' ); ?></span>
						</div>
						<div class="hero-verses-stage">
							<?php foreach ( $praise_verses as $vi => $verse ) : ?>
								<figure class="hero-verse<?php echo 0 === $vi ? ' active' : ''; ?>">
									<blockquote><?php echo esc_html( $verse['text'] ); ?></blockquote>
									<figcaption><?php echo esc_html( $verse['source'] ); ?></figcaption>
								</figure>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<div class="campaign-layout">

		<!-- Main column -->
		<div class="campaign-main">

			<!-- Reader -->
			<div class="reader-card">
				<div class="reader-head">
					<div class="reader-head-info">
						<div class="reader-icon">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FFF7F2" stroke-width="1.8"><path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5M12 6v13"></path></svg>
						</div>
						<div>
							<div class="reader-head-title"><?php esc_html_e( 'אמירת תהילים', 'tehilim' ); ?></div>
							<div class="reader-head-sub"><?php printf( esc_html__( 'ספר #%1$d פעיל · %2$d/%3$d · נותרו %4$d פרקים', 'tehilim' ), $current_book, $in_book, $chapters_per_book, $remaining_in_book ); ?></div>
						</div>
					</div>
					<div class="reader-head-actions">
						<button class="btn-reader-secondary btn-random">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"></path></svg>
							<?php esc_html_e( 'פרק רנדומלי', 'tehilim' ); ?>
						</button>
						<button class="btn-reader-secondary btn-pick"><?php esc_html_e( 'בחירת פרק אחר', 'tehilim' ); ?></button>
					</div>
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
					<input class="reader-name-input" type="text" value="" placeholder="<?php esc_attr_e( 'שמך (לא חובה)', 'tehilim' ); ?>">
					<button class="btn-reader-other btn-next">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6"></path></svg>
						<?php esc_html_e( 'פרק אחר', 'tehilim' ); ?>
					</button>
					<button class="btn-reader-said btn-say-chapter" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>" data-chapter-number="100">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#FFF7F2" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"></path></svg>
						<?php esc_html_e( 'סמן שקראתי', 'tehilim' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress overview -->
			<div class="progress-overview" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>">
				<div class="progress-overview-head">
					<div class="progress-overview-percent"><?php echo esc_html( $progress['progress_percent'] ); ?>%</div>
					<div class="progress-overview-label"><?php esc_html_e( 'התקדמות', 'tehilim' ); ?></div>
				</div>
				<div class="progress-track">
					<div class="progress-track-fill" style="width:<?php echo esc_attr( $progress['progress_percent'] ); ?>%"></div>
				</div>
				<div class="progress-overview-meta">
					<span><?php printf( esc_html__( '%d ספרים הושלמו', 'tehilim' ), (int) $progress['books_done'] ); ?></span>
					<span><?php printf( esc_html__( '%d מתוך היעד', 'tehilim' ), (int) $progress['goal_books'] ); ?></span>
				</div>
				<div class="progress-stats-grid campaign-stats">
					<div class="progress-stat">
						<div class="progress-stat-num"><?php echo esc_html( $progress['books_done'] ); ?></div>
						<div class="progress-stat-label"><?php esc_html_e( 'ספרים הושלמו', 'tehilim' ); ?></div>
					</div>
					<div class="progress-stat">
						<div class="progress-stat-num"><?php echo esc_html( $progress['total_chapters'] ); ?></div>
						<div class="progress-stat-label"><?php esc_html_e( 'פרקים הושלמו', 'tehilim' ); ?></div>
					</div>
					<div class="progress-stat">
						<div class="progress-stat-num"><?php echo esc_html( $participants ); ?></div>
						<div class="progress-stat-label"><?php esc_html_e( 'משתתפים', 'tehilim' ); ?></div>
					</div>
					<div class="progress-stat">
						<div class="progress-stat-num"><?php echo esc_html( $ambassadors_count ); ?></div>
						<div class="progress-stat-label"><?php esc_html_e( 'שגרירים', 'tehilim' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Ambassador CTA -->
			<div class="ambassador-cta">
				<div>
					<div class="ambassador-cta-title">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#EFC978" stroke-width="1.9"><circle cx="9" cy="8" r="3"></circle><path d="M3 19c0-3 2.7-5.5 6-5.5S15 16 15 19M17 5a3 3 0 0 1 0 6M21 19c0-2.3-1.4-4.3-3.5-5"></path></svg>
						<?php esc_html_e( 'הצטרפו כשגריר/ה לקבוצה', 'tehilim' ); ?>
					</div>
					<div class="ambassador-cta-desc"><?php esc_html_e( 'רוצים לזכות ולגייס עוד פרקי תהילים? קחו יעד וקבלו קישור אישי להפצה.', 'tehilim' ); ?></div>
				</div>
				<button class="btn-ambassador-cta"><?php esc_html_e( 'לפתיחת שגריר', 'tehilim' ); ?></button>
			</div>

			<!-- Ambassadors list -->
			<div class="ambassadors-card">
				<div class="ambassadors-card-head">
					<div class="ambassadors-card-title">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="#D9A441"><path d="M6 3h12v4a4 4 0 0 1-3 3.9V13h2v2H7v-2h2v-2.1A4 4 0 0 1 6 7z"></path><path d="M7 17h10v3H7z"></path></svg>
						<?php esc_html_e( 'השגרירים שלנו', 'tehilim' ); ?>
					</div>
					<div class="ambassadors-card-count"><?php printf( esc_html__( '%d שגרירים', 'tehilim' ), $ambassadors_count ); ?></div>
				</div>
				<div class="ambassadors-card-desc"><?php esc_html_e( 'כל שגריר מגייס את החוג שלו לאמירת תהילים. לחצו על שגריר לצפייה בעמוד האישי שלו.', 'tehilim' ); ?></div>
				<div class="ambassadors-list">
					<?php if ( $ambassadors ) : ?>
						<?php foreach ( $ambassadors as $i => $amb ) :
							$rank      = $i + 1;
							$bar       = $max_amb_count > 0 ? round( $amb['count'] / $max_amb_count * 100 ) : 0;
							$avatar    = $avatar_bg[ $i % count( $avatar_bg ) ];
							$initial   = function_exists( 'mb_substr' ) ? mb_substr( $amb['name'], 0, 1, 'UTF-8' ) : substr( $amb['name'], 0, 1 );
							$amb_link  = home_url( '/c/' . $campaign_slug . '/' . get_post_field( 'post_name', $amb['id'] ) );
							?>
							<a class="ambassador-row" href="<?php echo esc_url( $amb_link ); ?>">
								<div class="ambassador-rank"><?php echo esc_html( $rank ); ?></div>
								<div class="ambassador-avatar" style="background:<?php echo esc_attr( $avatar ); ?>"><?php echo esc_html( $initial ); ?></div>
								<div class="ambassador-row-body">
									<div class="ambassador-row-top">
										<div class="ambassador-row-name"><?php echo esc_html( $amb['name'] ); ?></div>
										<div class="ambassador-row-count"><?php printf( esc_html__( '%d פרקים', 'tehilim' ), $amb['count'] ); ?></div>
									</div>
									<div class="ambassador-bar"><div class="ambassador-bar-fill" style="width:<?php echo esc_attr( $bar ); ?>%"></div></div>
								</div>
							</a>
						<?php endforeach; ?>
					<?php else : ?>
						<div class="ambassadors-card-desc"><?php esc_html_e( 'עדיין אין שגרירים. היו הראשונים להצטרף!', 'tehilim' ); ?></div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Activity feed -->
			<div class="activity-card">
				<div class="activity-card-title"><?php esc_html_e( 'פעילות אחרונה', 'tehilim' ); ?></div>
				<div class="activity-feed activity-list">
					<?php
					$recitations = tehilim_get_recent_recitations( $campaign_id, 10 );
					if ( $recitations ) :
						foreach ( $recitations as $index => $rec ) :
							$name    = $rec->reciter_name ? $rec->reciter_name : __( 'משתתף אנונימי', 'tehilim' );
							$chapter = tehilim_hebrew_numeral( intval( $rec->chapter_number ) );
							// created_at is stored in GMT — get_date_from_gmt() returns it
							// in the site's configured timezone (Settings → General)
							$when    = get_date_from_gmt( $rec->created_at, 'j.n.Y, H:i' );
							$dot     = ( 0 === $index ) ? 'gold' : 'primary';
							?>
							<div class="activity-item">
								<div class="activity-dot <?php echo esc_attr( $dot ); ?>"></div>
								<div class="activity-text"><?php printf( esc_html__( '%1$s אמר/ה את פרק %2$s', 'tehilim' ), '<b>' . esc_html( $name ) . '</b>', esc_html( $chapter ) ); ?></div>
								<div class="activity-time"><?php echo esc_html( $when ); ?></div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<div class="activity-item">
							<div class="activity-dot primary"></div>
							<div class="activity-text"><?php esc_html_e( 'אין פעילות עדיין. היו הראשונים לומר פרק!', 'tehilim' ); ?></div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Sidebar -->
		<aside class="campaign-sidebar">

			<!-- Share -->
			<div class="share-card">
				<div class="share-card-title">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9"><circle cx="18" cy="5" r="2.6"></circle><circle cx="6" cy="12" r="2.6"></circle><circle cx="18" cy="19" r="2.6"></circle><path d="M8.3 10.7l7.4-4.4M8.3 13.3l7.4 4.4"></path></svg>
					<?php esc_html_e( 'שתפו את הקבוצה', 'tehilim' ); ?>
				</div>
				<button class="btn-share-whatsapp btn-share" data-share-type="whatsapp" data-share-url="<?php echo esc_url( $share_url ); ?>" data-share-text="<?php echo esc_attr( $share_text ); ?>">
					<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#EFC978" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20a8 8 0 1 0-6.9-4L4 20l4-1.1A8 8 0 0 0 12 20z"></path></svg>
					<?php esc_html_e( 'שיתוף ב-WhatsApp', 'tehilim' ); ?>
				</button>
				<button class="btn-share-copy btn-share" data-share-type="copy" data-share-url="<?php echo esc_url( $share_url ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2.5"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
					<?php esc_html_e( 'העתקת לינק', 'tehilim' ); ?>
				</button>
			</div>

			<!-- Leaderboard -->
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
							$amb_link = home_url( '/c/' . $campaign_slug . '/' . get_post_field( 'post_name', $amb['id'] ) );
							?>
							<a class="leaderboard-btn" href="<?php echo esc_url( $amb_link ); ?>">
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
	endwhile;
endif;

get_footer();
