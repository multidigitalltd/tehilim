<?php
/**
 * One-click site setup.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the site's pages (campaigns, personal area, ambassadors, segulot,
 * subscribe) and a navigation menu in one click, so the plugin behaves like a
 * ready-made multi-page site. Idempotent: existing pages are kept.
 */
final class SetupWizard implements Registerable {

	const ACTION = 'tcm_setup_pages';
	const OPTION = 'tcm_pages';
	const MENU   = 'תפריט תהילים';

	/**
	 * The pages to create: key => [title, slug, shortcode].
	 *
	 * @return array<string,array{title:string,slug:string,shortcode:string}>
	 */
	private function definitions() {
		return array(
			'home'        => array(
				'title'     => 'קמפיינים',
				'slug'      => 'campaigns',
				'shortcode' => '[tehillim_campaigns]',
			),
			'segulot'     => array(
				'title'     => 'סגולות ותפילות',
				'slug'      => 'prayers',
				'shortcode' => '[tehillim_segulot]',
			),
			'create'      => array(
				'title'     => 'פתיחת קמפיין',
				'slug'      => 'create-campaign',
				'shortcode' => '[tehillim_create_campaign_form]',
			),
			'my'          => array(
				'title'     => 'האזור האישי שלי',
				'slug'      => 'my-campaigns',
				'shortcode' => '[tehillim_my_campaigns]',
			),
			'activity'    => array(
				'title'     => 'הפעילות שלי',
				'slug'      => 'my-activity',
				'shortcode' => '[tehillim_my_activity]',
			),
			'ambassadors' => array(
				'title'     => 'שגרירים',
				'slug'      => 'ambassadors',
				'shortcode' => '[tehillim_ambassador_dashboard]',
			),
			'subscribe'   => array(
				'title'     => 'תהילים יומי',
				'slug'      => 'daily-tehillim',
				'shortcode' => '[tehillim_subscribe]',
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Register the submenu page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'edit.php?post_type=' . CampaignPostType::POST_TYPE,
			__( 'Set up site', 'tehillim-campaign-manager' ),
			__( 'Set up site', 'tehillim-campaign-manager' ),
			'manage_options',
			'tcm-setup',
			array( $this, 'render' )
		);
	}

	/**
	 * Render the setup screen.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$pages = get_option( self::OPTION, array() );
		$pages = is_array( $pages ) ? $pages : array();
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'Set up the site pages', 'tehillim-campaign-manager' ); ?></h1>
			<?php if ( ! empty( $_GET['tcm_setup'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Done — pages and menu are ready.', 'tehillim-campaign-manager' ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Create the campaign, personal-area, ambassadors and segulot pages — plus a navigation menu — in one click. Existing pages are kept.', 'tehillim-campaign-manager' ); ?></p>
			<table class="widefat striped" style="max-width:640px">
				<tbody>
					<?php foreach ( $this->definitions() as $key => $def ) : ?>
						<?php
						$id     = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
						$exists = $id && get_post( $id ) && 'trash' !== get_post_status( $id );
						?>
						<tr>
							<td><strong><?php echo esc_html( $def['title'] ); ?></strong><br><code><?php echo esc_html( $def['shortcode'] ); ?></code></td>
							<td>
								<?php if ( $exists ) : ?>
									<a href="<?php echo esc_url( (string) get_permalink( $id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'tehillim-campaign-manager' ); ?></a>
								<?php else : ?>
									<span class="description"><?php esc_html_e( 'Not created yet', 'tehillim-campaign-manager' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Create pages and menu', 'tehillim-campaign-manager' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Create the pages, front page and menu.
	 *
	 * @return void
	 */
	public function handle() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::ACTION ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tehillim-campaign-manager' ) );
		}

		$pages = get_option( self::OPTION, array() );
		$pages = is_array( $pages ) ? $pages : array();

		foreach ( $this->definitions() as $key => $def ) {
			$existing = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
			if ( $existing && get_post( $existing ) && 'trash' !== get_post_status( $existing ) ) {
				// Fix the slug of a page created before (English, not Hebrew).
				if ( get_post_field( 'post_name', $existing ) !== $def['slug'] ) {
					wp_update_post(
						array(
							'ID'        => $existing,
							'post_name' => $def['slug'],
						)
					);
				}
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $def['title'],
					'post_name'    => $def['slug'],
					'post_content' => $def['shortcode'],
				),
				true
			);
			if ( ! is_wp_error( $id ) ) {
				$pages[ $key ] = (int) $id;
			}
		}
		update_option( self::OPTION, $pages );

		// Show the campaigns page on the home URL when no static front page is set.
		if ( 'page' !== get_option( 'show_on_front' ) && ! empty( $pages['home'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $pages['home'] );
		}

		$this->build_menu( $pages );

		wp_safe_redirect(
			add_query_arg(
				'tcm_setup',
				'1',
				admin_url( 'edit.php?post_type=' . CampaignPostType::POST_TYPE . '&page=tcm-setup' )
			)
		);
		exit;
	}

	/**
	 * Build a classic navigation menu and assign it to the first theme location.
	 * (Block themes that use a Page List in the header pick up the pages on
	 * their own.)
	 *
	 * @param array<string,int> $pages Created page ids.
	 * @return void
	 */
	private function build_menu( array $pages ) {
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

		$menu = wp_get_nav_menu_object( self::MENU );
		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( self::MENU );
			if ( is_wp_error( $menu_id ) ) {
				return;
			}
			foreach ( $this->definitions() as $key => $def ) {
				if ( empty( $pages[ $key ] ) ) {
					continue;
				}
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $def['title'],
						'menu-item-object'    => 'page',
						'menu-item-object-id' => (int) $pages[ $key ],
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}
		} else {
			$menu_id = (int) $menu->term_id;
		}

		$locations = get_registered_nav_menus();
		if ( $locations ) {
			$assigned = get_theme_mod( 'nav_menu_locations', array() );
			$assigned = is_array( $assigned ) ? $assigned : array();
			$assigned[ (string) array_key_first( $locations ) ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $assigned );
		}
	}
}
