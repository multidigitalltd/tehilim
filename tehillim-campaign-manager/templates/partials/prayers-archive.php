<?php
/**
 * Prayers / segulot archive built from regular posts (designed cards).
 *
 * @package Tehillim_Campaign_Manager
 *
 * @var array<int,array{title:string,permalink:string,excerpt:string,thumb:string,date:string}> $posts Posts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tcm-wrap tcm-prayers">
	<?php if ( empty( $posts ) ) : ?>
		<div class="tcm-card"><p class="tcm-muted"><?php esc_html_e( 'No prayers or segulot have been published yet.', 'tehillim-campaign-manager' ); ?></p></div>
	<?php else : ?>
		<div class="tcm-prayers-grid">
			<?php foreach ( $posts as $post ) : ?>
				<a class="tcm-card tcm-prayer-card" href="<?php echo esc_url( $post['permalink'] ); ?>">
					<?php if ( '' !== $post['thumb'] ) : ?>
						<span class="tcm-prayer-thumb" style="background-image:url('<?php echo esc_url( $post['thumb'] ); ?>')" aria-hidden="true"></span>
					<?php else : ?>
						<span class="tcm-prayer-thumb tcm-prayer-thumb--ph" aria-hidden="true">✦</span>
					<?php endif; ?>
					<span class="tcm-prayer-body">
						<span class="tcm-prayer-title"><?php echo esc_html( $post['title'] ); ?></span>
						<?php if ( '' !== $post['excerpt'] ) : ?>
							<span class="tcm-prayer-excerpt"><?php echo esc_html( $post['excerpt'] ); ?></span>
						<?php endif; ?>
						<span class="tcm-prayer-date"><?php echo esc_html( $post['date'] ); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
