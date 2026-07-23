<?php
/**
 * Campaign Card Component - Exact design match from HTML
 */

$campaign_id = get_the_ID();
$goal_books = intval( get_post_meta( $campaign_id, 'goal_books', true ) ?: 10 );
$progress_percent = rand( 10, 90 ); // Placeholder - replace with actual calculation
$books_completed = floor( ( $progress_percent / 100 ) * $goal_books );
?>

<div class="campaign-card">
	<!-- Card Header (Gradient Background) -->
	<div class="campaign-card-header"></div>

	<!-- Card Body -->
	<div class="campaign-card-body">
		<div>
			<div class="campaign-card-title"><?php the_title(); ?></div>
			<div class="campaign-card-occasion"><?php esc_html_e( 'מוקדש · לרפואת', 'tehilim' ); ?></div>
			<div class="campaign-card-category">
				<?php
				$occasions = get_the_terms( $campaign_id, 'occasion' );
				if ( $occasions && ! is_wp_error( $occasions ) ) {
					echo esc_html( $occasions[0]->name );
				}
				?>
			</div>
		</div>

		<div class="campaign-card-meta">
			<div class="book-count">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 6C10 4.5 7 4 4 4.5v13C7 17 10 17.5 12 19M12 6c2-1.5 5-2 8-1.5v13c-3-.5-6 0-8 1.5"></path>
				</svg>
				<span style="direction: ltr;"><?php echo absint( $books_completed ); ?> / <?php echo absint( $goal_books ); ?></span>
			</div>
			<div class="progress-badge">
				<span><?php echo absint( $progress_percent ); ?>%</span>
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 3c1 3-1 4-1 6a3 3 0 0 0 6 0c0 4-2 5-2 7a3 3 0 0 1-6 0c0-3 3-4 3-8 0-1 .5-2 0-2z"></path>
				</svg>
			</div>
		</div>

		<div>
			<div class="progress-bar-container">
				<div class="progress-bar-fill" style="width: <?php echo absint( $progress_percent ); ?>%;"></div>
			</div>
			<div class="progress-text"><?php esc_html_e( 'ספרים הושלמו', 'tehilim' ); ?></div>
		</div>

		<a href="<?php the_permalink(); ?>" class="btn-card-action">
			<span><?php esc_html_e( 'כניסה לקמפיין', 'tehilim' ); ?></span>
			<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#A94B2E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M19 12H5M11 18l-6-6 6-6"></path>
			</svg>
		</a>
	</div>
</div>
