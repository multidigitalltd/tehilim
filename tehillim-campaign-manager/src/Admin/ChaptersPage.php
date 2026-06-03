<?php
/**
 * Tehillim chapters editor.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\ChapterTextService;
use TCM\Support\Hebrew;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screen to enter the readable text of the 150 chapters, saved to the
 * `tcm_chapters` option. Submission is capability- and nonce-checked and each
 * chapter is sanitised with wp_kses_post.
 */
final class ChaptersPage implements Registerable {

	const NONCE = 'tcm_save_chapters';

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_tcm_save_chapters', array( $this, 'save' ) );
	}

	/**
	 * Register the page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'edit.php?post_type=' . CampaignPostType::POST_TYPE,
			__( 'Tehillim chapters', 'tehillim-campaign-manager' ),
			__( 'Tehillim chapters', 'tehillim-campaign-manager' ),
			'manage_options',
			'tcm-chapters',
			array( $this, 'render' )
		);
	}

	/**
	 * Render the editor.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$chapters = get_option( ChapterTextService::OPTION, array() );
		$chapters = is_array( $chapters ) ? $chapters : array();
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'Tehillim chapters', 'tehillim-campaign-manager' ); ?></h1>
			<?php if ( ! empty( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Saved.', 'tehillim-campaign-manager' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="tcm_save_chapters">
				<?php wp_nonce_field( self::NONCE ); ?>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save all chapters', 'tehillim-campaign-manager' ); ?></button></p>
				<?php for ( $i = 1; $i <= ChapterTextService::MAX; $i++ ) : ?>
					<details style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:10px;margin:8px 0">
						<summary><strong>
						<?php
						/* translators: %s: Hebrew chapter label. */
						printf( esc_html__( 'Tehillim chapter %s', 'tehillim-campaign-manager' ), esc_html( Hebrew::chapter_label( $i ) ) );
						?>
						</strong></summary>
						<textarea name="tcm_chapters[<?php echo esc_attr( (string) $i ); ?>]" rows="8" class="large-text" style="direction:rtl;margin-top:10px"><?php echo esc_textarea( $chapters[ $i ] ?? '' ); ?></textarea>
					</details>
				<?php endfor; ?>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save all chapters', 'tehillim-campaign-manager' ); ?></button></p>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the save.
	 *
	 * @return void
	 */
	public function save() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tehillim-campaign-manager' ) );
		}

		$raw   = isset( $_POST['tcm_chapters'] ) && is_array( $_POST['tcm_chapters'] ) ? wp_unslash( $_POST['tcm_chapters'] ) : array();
		$clean = array();
		for ( $i = 1; $i <= ChapterTextService::MAX; $i++ ) {
			if ( ! empty( $raw[ $i ] ) ) {
				$clean[ $i ] = wp_kses_post( $raw[ $i ] );
			}
		}
		update_option( ChapterTextService::OPTION, $clean, false );

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . CampaignPostType::POST_TYPE . '&page=tcm-chapters&updated=1' ) );
		exit;
	}
}
