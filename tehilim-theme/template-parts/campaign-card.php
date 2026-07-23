<?php
/**
 * Campaign Card Component
 */

$campaign_id = get_the_ID();
$goal_books = intval( get_post_meta( $campaign_id, 'goal_books', true ) ?: 1 );
?>

<article class="campaign-card">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="card-image">
			<?php the_post_thumbnail( 'medium' ); ?>
		</div>
	<?php endif; ?>

	<div class="card-content">
		<h3 class="card-title">
			<a href="<?php the_permalink(); ?>">
				<?php the_title(); ?>
			</a>
		</h3>

		<?php
		$occasions = get_the_terms( $campaign_id, 'occasion' );
		if ( $occasions ) {
			?>
			<div class="card-occasions">
				<?php
				foreach ( $occasions as $occasion ) {
					?>
					<span class="occasion-tag"><?php echo esc_html( $occasion->name ); ?></span>
					<?php
				}
				?>
			</div>
			<?php
		}
		?>

		<div class="card-progress">
			<div class="progress-bar">
				<div class="progress-fill" style="width: 30%;"></div>
			</div>
			<p class="progress-text">
				<?php
				printf(
					esc_html_x( '%d מתוך %d ספרים', 'progress counter', 'tehilim' ),
					1, // Replace with actual progress
					$goal_books
				);
				?>
			</p>
		</div>

		<a href="<?php the_permalink(); ?>" class="btn btn-secondary">
			צפו בקמפיין
		</a>
	</div>
</article>
