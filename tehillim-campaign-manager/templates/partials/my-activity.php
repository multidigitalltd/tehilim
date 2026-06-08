<?php
/**
 * My reading activity.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var bool  $logged_in Whether the user is logged in.
 * @var array $rows       Activity rows.
 * @var int   $done       Chapters completed by this participant.
 * @var array $badge      Earned participant badge (slug/label/icon) or null.
 * @var array $next_tier  Next tier (label/icon/remaining) or null.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$done      = isset( $done ) ? (int) $done : 0;
$badge     = isset( $badge ) ? $badge : null;
$next_tier = isset( $next_tier ) ? $next_tier : null;

$labels = array(
	'free'  => __( 'Free', 'tehillim-campaign-manager' ),
	'taken' => __( 'Awaiting completion', 'tehillim-campaign-manager' ),
	'done'  => __( 'Completed', 'tehillim-campaign-manager' ),
);
?>
<div class="tcm-wrap tcm-my-activity">
	<div class="tcm-card">
		<h3><?php esc_html_e( 'My Tehillim activity', 'tehillim-campaign-manager' ); ?></h3>

		<?php if ( ! empty( $logged_in ) && $done > 0 ) : ?>
			<div class="tcm-my-badge">
				<?php if ( $badge ) : ?>
					<span class="tcm-badge-chip is-lg" title="<?php echo esc_attr( $badge['label'] ); ?>"><span aria-hidden="true"><?php echo esc_html( $badge['icon'] ); ?></span> <?php echo esc_html( $badge['label'] ); ?></span>
				<?php endif; ?>
				<span class="tcm-my-badge-count">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of chapters completed. */
							_n( '%d chapter completed', '%d chapters completed', $done, 'tehillim-campaign-manager' ),
							$done
						)
					);
					?>
				</span>
				<?php if ( $next_tier ) : ?>
					<span class="tcm-my-badge-next">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: chapters remaining, 2: next badge icon, 3: next badge label. */
								_n( '%1$d more to earn %2$s %3$s', '%1$d more to earn %2$s %3$s', $next_tier['remaining'], 'tehillim-campaign-manager' ),
								(int) $next_tier['remaining'],
								$next_tier['icon'],
								$next_tier['label']
							)
						);
						?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( empty( $logged_in ) ) : ?>
			<p><?php esc_html_e( 'Please log in to view your activity.', 'tehillim-campaign-manager' ); ?></p>
		<?php elseif ( empty( $rows ) ) : ?>
			<p><?php esc_html_e( 'No activity yet.', 'tehillim-campaign-manager' ); ?></p>
		<?php else : ?>
			<table class="tcm-admin-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Campaign', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Chapter', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'tehillim-campaign-manager' ); ?></th>
						<th><?php esc_html_e( 'Action', 'tehillim-campaign-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['campaign_title'] ); ?></td>
							<td><?php echo esc_html( $row['chapter'] ); ?></td>
							<td><?php echo esc_html( $labels[ $row['status'] ] ?? $row['status'] ); ?></td>
							<td>
								<?php if ( $row['read_url'] ) : ?>
									<a class="tcm-btn is-secondary" href="<?php echo esc_url( $row['read_url'] ); ?>"><?php esc_html_e( 'Read / finish', 'tehillim-campaign-manager' ); ?></a>
								<?php else : ?>
									<a class="tcm-btn" href="<?php echo esc_url( $row['permalink'] ); ?>"><?php esc_html_e( 'Open', 'tehillim-campaign-manager' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
