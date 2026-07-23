<?php
/**
 * Campaign Card Component — exact design match (home.html / archive.html)
 */

$campaign_id = get_the_ID();

$progress = tehilim_get_campaign_progress( $campaign_id );
$percent  = intval( $progress['progress_percent'] );
$books    = intval( $progress['books_done'] );
$goal     = intval( $progress['goal_books'] );

// Badge state (matches design thresholds)
if ( $percent >= 100 ) {
	$state = __( 'הושלם', 'tehilim' );
	$done  = true;
} elseif ( $percent >= 80 ) {
	$state = __( 'כמעט הושלם', 'tehilim' );
	$done  = true;
} else {
	$state = __( 'בעיצומו', 'tehilim' );
	$done  = false;
}

// Dedication preposition by occasion
$occasions     = get_the_terms( $campaign_id, 'occasion' );
$occasion_name = ( $occasions && ! is_wp_error( $occasions ) ) ? $occasions[0]->name : '';
$dedication    = __( 'מוקדש', 'tehilim' );
$ded_map       = array(
	'רפואה' => 'לרפואת',
	'עילוי' => 'לעילוי נשמת',
	'זיווג' => 'לזיווג',
	'פרנסה' => 'לפרנסת',
	'זכות'  => 'לזכות',
	'שמחה'  => 'לרגל',
	'אירוע' => 'לרגל',
);
foreach ( $ded_map as $needle => $prep ) {
	if ( $occasion_name && false !== mb_strpos( $occasion_name, $needle ) ) {
		$dedication = 'מוקדש · ' . $prep;
		break;
	}
}

// Varied header gradient (matches design's pastel rotation)
$gradients = array(
	'linear-gradient(160deg,#E7ECF6,#EFEAE0 55%,#F3EAD9)',
	'linear-gradient(160deg,#F6E7E7,#F0E7DE 55%,#F3EAD9)',
	'linear-gradient(160deg,#E7F1EA,#EFEADF 55%,#F3EAD9)',
	'linear-gradient(160deg,#ECE7F6,#EFEADF 55%,#F3EAD9)',
	'linear-gradient(160deg,#F6EFDD,#F1E9DC 55%,#F3EAD9)',
	'linear-gradient(160deg,#E4F0F1,#EFEADF 55%,#F3EAD9)',
);
$gradient = $gradients[ $campaign_id % count( $gradients ) ];
?>

<div class="campaign-card">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="campaign-card-header has-image">
			<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
		</div>
	<?php else : ?>
		<div class="campaign-card-header" style="background: <?php echo esc_attr( $gradient ); ?>"></div>
	<?php endif; ?>

	<div class="campaign-card-body">
		<div>
			<div class="campaign-card-title"><?php the_title(); ?></div>
			<div class="campaign-card-occasion"><?php echo esc_html( $dedication ); ?></div>
			<?php if ( $occasion_name ) : ?>
				<div class="campaign-card-category"><?php echo esc_html( $occasion_name ); ?></div>
			<?php endif; ?>
		</div>

		<div class="campaign-card-meta">
			<div class="book-count">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5"></path>
				</svg>
				<span style="direction: ltr;"><?php echo absint( $books ); ?> / <?php echo absint( $goal ); ?></span>
			</div>
			<div class="progress-badge<?php echo $done ? ' done' : ''; ?>">
				<span><?php echo esc_html( $state ); ?> · <?php echo absint( $percent ); ?>%</span>
				<?php if ( $done ) : ?>
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
						<path d="M5 13l4 4L19 7"></path>
					</svg>
				<?php else : ?>
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
						<path d="M12 3c1 3-1 4-1 6a3 3 0 0 0 6 0c0 4-2 5-2 7a3 3 0 0 1-6 0c0-3 3-4 3-8 0-1 .5-2 0-2z"></path>
					</svg>
				<?php endif; ?>
			</div>
		</div>

		<div>
			<div class="progress-bar-container">
				<div class="progress-bar-fill" style="width: <?php echo absint( $percent ); ?>%;"></div>
			</div>
			<div class="progress-text">
				<?php
				printf(
					esc_html__( 'ספרים הושלמו · נאמרו %s פרקים', 'tehilim' ),
					esc_html( number_format_i18n( intval( $progress['total_chapters'] ) ) )
				);
				?>
			</div>
		</div>

		<a href="<?php the_permalink(); ?>" class="btn-card-action">
			<span><?php esc_html_e( 'כניסה לקמפיין', 'tehilim' ); ?></span>
			<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M19 12H5M11 18l-6-6 6-6"></path>
			</svg>
		</a>
	</div>
</div>
