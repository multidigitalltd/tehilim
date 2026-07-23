<?php
/**
 * Template Name: Ambassador Dashboard
 * Description: Private ambassador dashboard — exact design match (ambassador-dashboard.html)
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();

$current_user = wp_get_current_user();

// Locate the ambassador record tied to this user (matched by email).
$ambassador = null;
$amb_posts  = get_posts( array(
	'post_type'   => 'ambassador',
	'numberposts' => 1,
	'meta_query'  => array(
		array(
			'key'   => 'email',
			'value' => $current_user->user_email,
		),
	),
) );
if ( $amb_posts ) {
	$ambassador = $amb_posts[0];
}
?>

<div class="dashboard-page">

	<?php if ( $ambassador ) :

		$amb_id       = $ambassador->ID;
		$amb_title    = $ambassador->post_title;
		$campaign_id  = intval( get_post_meta( $amb_id, 'campaign_id', true ) );
		$campaign     = $campaign_id ? get_post( $campaign_id ) : null;
		$campaign_url = $campaign ? get_permalink( $campaign_id ) : home_url( '/' );

		$chapters_per_book = defined( 'TEHILIM_CHAPTERS_PER_BOOK' ) ? TEHILIM_CHAPTERS_PER_BOOK : 150;

		$occasion_name = '';
		$progress      = null;
		$goal_chapters = $chapters_per_book;
		if ( $campaign ) {
			$occasions     = get_the_terms( $campaign_id, 'occasion' );
			$occasion_name = ( $occasions && ! is_wp_error( $occasions ) ) ? $occasions[0]->name : '';
			$progress      = tehilim_get_campaign_progress( $campaign_id );
			$goal_chapters = max( 1, intval( $progress['goal_books'] ) * $chapters_per_book );
		}

		global $wpdb;
		$rec_table    = $wpdb->prefix . 'tehilim_recitations';
		$amb_chapters = $campaign_id ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `%i` WHERE campaign_id = %d AND ambassador_id = %d", $rec_table, $campaign_id, $amb_id ) ) : 0;
		$amb_reciters = $campaign_id ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT reciter_name) FROM `%i` WHERE campaign_id = %d AND ambassador_id = %d AND reciter_name IS NOT NULL AND reciter_name <> ''", $rec_table, $campaign_id, $amb_id ) ) : 0;
		$amb_ring     = min( 100, round( $amb_chapters / $goal_chapters * 100 ) );

		$amb_rank          = '—';
		$ambassadors_count = 0;
		if ( $campaign_id ) {
			$ambassadors       = tehilim_get_top_ambassadors( $campaign_id, 20 );
			$ambassadors_count = count( $ambassadors );
			foreach ( $ambassadors as $i => $amb ) {
				if ( $amb['id'] === $amb_id ) {
					$amb_rank = $i + 1;
				}
			}
		}

		$initial = function_exists( 'mb_substr' ) ? mb_substr( $amb_title, 0, 1, 'UTF-8' ) : substr( $amb_title, 0, 1 );
		$amb_url = get_permalink( $amb_id );
		?>

		<a class="back-link" href="<?php echo esc_url( $campaign_url ); ?>">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8A6B4A" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"></path></svg>
			<?php esc_html_e( 'חזרה לקמפיין', 'tehilim' ); ?>
		</a>

		<div class="dashboard-card">
			<div class="dashboard-ring" style="background:conic-gradient(#D9A441 0deg,#C05A3A <?php echo esc_attr( $amb_ring * 3.6 ); ?>deg,#EFE3CF <?php echo esc_attr( $amb_ring * 3.6 ); ?>deg)">
				<div class="dashboard-ring-inner">
					<div class="dashboard-ring-percent"><?php echo esc_html( $amb_ring ); ?>%</div>
					<div class="dashboard-ring-label"><?php esc_html_e( 'מהיעד', 'tehilim' ); ?></div>
				</div>
			</div>
			<div class="dashboard-name"><?php echo esc_html( $amb_title ); ?></div>
			<div class="dashboard-sub">
				<?php
				if ( $campaign ) {
					printf(
						esc_html__( 'שגריר/ה בקמפיין · %1$s%2$s', 'tehilim' ),
						esc_html( $campaign->post_title ),
						$occasion_name ? ' (' . esc_html( $occasion_name ) . ')' : ''
					);
				} else {
					esc_html_e( 'שגריר/ה', 'tehilim' );
				}
				?>
			</div>

			<div class="dashboard-stats">
				<div class="dashboard-stat">
					<div class="dashboard-stat-num"><?php echo esc_html( $amb_chapters ); ?></div>
					<div class="dashboard-stat-label"><?php esc_html_e( 'פרקים גויסו', 'tehilim' ); ?></div>
				</div>
				<div class="dashboard-stat">
					<div class="dashboard-stat-num"><?php echo esc_html( $amb_reciters ); ?></div>
					<div class="dashboard-stat-label"><?php esc_html_e( 'אמרו דרכי', 'tehilim' ); ?></div>
				</div>
				<div class="dashboard-stat">
					<div class="dashboard-stat-num"><?php echo esc_html( $amb_rank ); ?><small>/<?php echo esc_html( max( 1, $ambassadors_count ) ); ?></small></div>
					<div class="dashboard-stat-label"><?php esc_html_e( 'מקום בדירוג', 'tehilim' ); ?></div>
				</div>
			</div>

			<div class="dashboard-linkbox">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#B9822B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"></path><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"></path></svg>
				<span><?php echo esc_html( $amb_url ); ?></span>
			</div>
			<button class="btn-dashboard-copy btn-share" data-share-type="copy" data-share-url="<?php echo esc_url( $amb_url ); ?>"><?php esc_html_e( 'העתקת לינק', 'tehilim' ); ?></button>
			<a class="btn-dashboard-secondary" href="<?php echo esc_url( $amb_url ); ?>"><?php esc_html_e( 'צפו בעמוד ההפצה שלכם ←', 'tehilim' ); ?></a>
		</div>

	<?php else : ?>

		<div class="create-header">
			<h1 class="create-title"><?php esc_html_e( 'אין עדיין אזור שגריר', 'tehilim' ); ?></h1>
			<p class="create-subtitle"><?php esc_html_e( 'לא נמצא רישום שגריר המשויך לחשבון שלכם.', 'tehilim' ); ?></p>
		</div>
		<div class="dashboard-card">
			<div class="dashboard-name"><?php echo esc_html( $current_user->display_name ); ?></div>
			<div class="dashboard-sub"><?php esc_html_e( 'הצטרפו כשגריר לאחד הקמפיינים כדי לקבל אזור אישי.', 'tehilim' ); ?></div>
			<a class="btn-dashboard-secondary" href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>"><?php esc_html_e( 'גלו קמפיינים ←', 'tehilim' ); ?></a>
		</div>

	<?php endif; ?>
</div>

<?php
get_footer();
