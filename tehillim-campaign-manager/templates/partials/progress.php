<?php
/**
 * Campaign hero header + progress card (Psalms Unite layout):
 * a two-column hero (text + image card) followed by a progress card with a
 * percentage, bar and a four-stat grid.
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var string $title        Campaign title (dedication).
 * @var string $dedicated_to Optional "dedicated to" line.
 * @var string $image        Optional featured image URL.
 * @var string $description  Campaign description HTML (already sanitised).
 * @var array  $stats        Stats array from StatsService.
 * @var int    $participants Distinct participants.
 * @var int    $ambassadors  Registered ambassadors.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$completed    = $stats['percent'] >= 100;
$dedicated_to = isset( $dedicated_to ) ? $dedicated_to : '';
$image        = isset( $image ) ? $image : '';
$participants = isset( $participants ) ? (int) $participants : 0;
$ambassadors  = isset( $ambassadors ) ? (int) $ambassadors : 0;
$tiles        = array(
	array(
		'label' => __( 'Books', 'tehillim-campaign-manager' ),
		'value' => (int) $stats['completed_books'],
		'icon'  => '📖',
	),
	array(
		'label' => __( 'Chapters', 'tehillim-campaign-manager' ),
		'value' => (int) $stats['total_done'],
		'icon'  => '📜',
	),
	array(
		'label' => __( 'Participants', 'tehillim-campaign-manager' ),
		'value' => $participants,
		'icon'  => '👥',
	),
	array(
		'label' => __( 'Ambassadors', 'tehillim-campaign-manager' ),
		'value' => $ambassadors,
		'icon'  => '🏆',
	),
);
?>
<section class="tcm-card tcm-campaign-hero tcm-gradient-warm" aria-label="<?php esc_attr_e( 'Campaign', 'tehillim-campaign-manager' ); ?>">
	<span class="tcm-orb tcm-orb--gold" aria-hidden="true"></span>
	<span class="tcm-orb tcm-orb--indigo" aria-hidden="true"></span>

	<div class="tcm-hero-grid">
		<div class="tcm-hero-main">
			<p class="tcm-eyebrow">✦ <?php esc_html_e( 'Tehillim campaign', 'tehillim-campaign-manager' ); ?></p>
			<h2 class="tcm-title tcm-hero-title"><?php echo esc_html( $title ); ?></h2>

			<?php if ( ! empty( $description ) ) : ?>
				<div class="tcm-description"><?php echo wp_kses_post( $description ); ?></div>
			<?php endif; ?>
		</div>

		<?php if ( $image ) : ?>
			<div class="tcm-hero-media">
				<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
			</div>
		<?php endif; ?>
	</div>
</section>

<section class="tcm-card tcm-progress-card" aria-label="<?php esc_attr_e( 'Campaign progress', 'tehillim-campaign-manager' ); ?>">
	<div class="tcm-progress-head">
		<h3><?php esc_html_e( 'Progress', 'tehillim-campaign-manager' ); ?></h3>
		<span class="tcm-pct"><?php echo esc_html( $stats['percent'] ); ?>%</span>
	</div>

	<div class="tcm-progress" role="progressbar"
		aria-valuenow="<?php echo esc_attr( $stats['percent'] ); ?>" aria-valuemin="0" aria-valuemax="100">
		<span style="width:<?php echo esc_attr( max( 2, $stats['percent'] ) ); ?>%"></span>
	</div>

	<div class="tcm-progress-meta">
		<span>
			<?php
			printf(
				/* translators: 1: completed books, 2: target books. */
				esc_html__( '%1$s / %2$s books', 'tehillim-campaign-manager' ),
				esc_html( $stats['base_completed'] ),
				esc_html( $stats['target'] )
			);
			?>
		</span>
		<span class="tcm-pill <?php echo $completed ? 'is-done' : ''; ?>">
			<?php echo $completed ? esc_html__( 'Completed', 'tehillim-campaign-manager' ) : esc_html__( 'In progress', 'tehillim-campaign-manager' ); ?>
		</span>
	</div>

	<ul class="tcm-stat-grid">
		<?php foreach ( $tiles as $tile ) : ?>
			<li class="tcm-stat-tile">
				<span class="tcm-stat-tile__label"><span aria-hidden="true"><?php echo esc_html( $tile['icon'] ); ?></span> <?php echo esc_html( $tile['label'] ); ?></span>
				<span class="tcm-stat-tile__value"><?php echo esc_html( number_format_i18n( $tile['value'] ) ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
