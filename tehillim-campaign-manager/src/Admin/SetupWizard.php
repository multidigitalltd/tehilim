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

	const ACTION       = 'tcm_setup_pages';
	const ACTION_DEMO  = 'tcm_import_demo';
	const ACTION_BUILD = 'tcm_build_site';
	const OPTION       = 'tcm_pages';
	const MENU         = 'תפריט תהילים';

	/**
	 * The pages to create. Each page is built from *editable blocks* - a heading,
	 * an intro paragraph and the matching dynamic block - so the whole site is
	 * editable in the block editor rather than a single opaque shortcode. The
	 * legacy `shortcode` is kept only to detect and upgrade pages created by an
	 * earlier version.
	 *
	 * @return array<string,array{title:string,slug:string,block:string,intro:string,shortcode:string}>
	 */
	private function definitions() {
		return array(
			'home'        => array(
				'title'     => 'קמפיינים',
				'slug'      => 'campaigns',
				'block'     => 'campaigns',
				'intro'     => 'בחרו קמפיין והצטרפו לאמירת תהילים משותפת.',
				'shortcode' => '[tehillim_campaigns]',
			),
			'segulot'     => array(
				'title'     => 'סגולות ותפילות',
				'slug'      => 'prayers',
				'block'     => 'segulot',
				'intro'     => 'אוסף תפילות וסגולות מיוחדות לכל עת.',
				'shortcode' => '[tehillim_segulot]',
			),
			'create'      => array(
				'title'     => 'פתיחת קמפיין',
				'slug'      => 'create-campaign',
				'block'     => 'create-campaign',
				'intro'     => 'פִתחו קמפיין תהילים בשתי דקות והזמינו את הקהילה.',
				'shortcode' => '[tehillim_create_campaign_form]',
			),
			'my'          => array(
				'title'     => 'האזור האישי שלי',
				'slug'      => 'my-campaigns',
				'block'     => 'my-campaigns',
				'intro'     => 'הקמפיינים שפתחתם וניהול שלהם במקום אחד.',
				'shortcode' => '[tehillim_my_campaigns]',
			),
			'activity'    => array(
				'title'     => 'הפעילות שלי',
				'slug'      => 'my-activity',
				'block'     => 'my-activity',
				'intro'     => 'הפרקים שלקחתם והשלמתם בכל הקמפיינים.',
				'shortcode' => '[tehillim_my_activity]',
			),
			'ambassadors' => array(
				'title'     => 'שגרירים',
				'slug'      => 'ambassadors',
				'block'     => 'ambassadors',
				'intro'     => 'גייסו את הקהילה שלכם וצפו בהתקדמות האישית.',
				'shortcode' => '[tehillim_ambassador_dashboard]',
			),
			'subscribe'   => array(
				'title'     => 'תהילים יומי',
				'slug'      => 'daily-tehillim',
				'block'     => 'subscribe',
				'intro'     => 'הירשמו לקבלת פרק תהילים יומי ותזכורות.',
				'shortcode' => '[tehillim_subscribe]',
			),
		);
	}

	/**
	 * Full designed block markup for a page. Uses the ready-made layout from
	 * {@see SiteContent}; falls back to a simple heading + intro + block.
	 *
	 * @param string                                        $key Page key.
	 * @param array{title:string,block:string,intro:string} $def Page definition.
	 * @return string
	 */
	private function page_content( $key, array $def ) {
		$rich = SiteContent::for_key( $key );
		if ( '' !== $rich ) {
			return $rich;
		}
		return '<!-- wp:heading {"textAlign":"center"} -->' . "\n"
			. '<h2 class="wp-block-heading has-text-align-center">' . esc_html( $def['title'] ) . '</h2>' . "\n"
			. '<!-- /wp:heading -->' . "\n\n"
			. '<!-- wp:paragraph {"align":"center"} -->' . "\n"
			. '<p class="has-text-align-center">' . esc_html( $def['intro'] ) . '</p>' . "\n"
			. '<!-- /wp:paragraph -->' . "\n\n"
			. '<!-- wp:tehillim/' . $def['block'] . ' /-->';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		// The page/demo/site builder targets the block companion theme. With any
		// other theme (e.g. the Psalms Unite design theme, which builds its own
		// pages), it is irrelevant, so the screen stays hidden for a clean admin.
		if ( ! $this->is_block_companion() ) {
			return;
		}
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_' . self::ACTION_DEMO, array( $this, 'handle_demo' ) );
		add_action( 'admin_post_' . self::ACTION_BUILD, array( $this, 'handle_build' ) );
	}

	/**
	 * Whether the block companion theme is the active theme.
	 *
	 * @return bool
	 */
	private function is_block_companion() {
		return in_array( 'tehillim-companion', array( get_template(), get_stylesheet() ), true );
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
		$demo  = new DemoContent();
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'Set up the site', 'tehillim-campaign-manager' ); ?></h1>
			<?php if ( ! empty( $_GET['tcm_setup'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Done - pages and menu are ready.', 'tehillim-campaign-manager' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $_GET['tcm_demo'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Demo content imported - sample campaigns, prayers and ad banners are live.', 'tehillim-campaign-manager' ); ?></p></div>
			<?php endif; ?>

			<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:22px;max-width:680px;margin:18px 0">
				<h2 style="margin-top:0"><?php esc_html_e( 'Build the whole site in one click', 'tehillim-campaign-manager' ); ?></h2>
				<p><?php esc_html_e( 'Creates every page with the full ready-made design built right into it (homepage hero, sections, FAQ and more - all editable blocks), sets the home page and menu, and imports sample content. Just like a premium theme demo import. Best on a fresh site.', 'tehillim-campaign-manager' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_BUILD ); ?>">
					<?php wp_nonce_field( self::ACTION_BUILD ); ?>
					<button type="submit" class="button button-primary button-hero"><?php esc_html_e( '✨ Build a complete site (design + content)', 'tehillim-campaign-manager' ); ?></button>
				</form>
			</div>

			<hr style="margin:28px 0">
			<p class="description" style="max-width:680px"><?php esc_html_e( 'Prefer to do it in steps? Use the options below.', 'tehillim-campaign-manager' ); ?></p>

			<h2><?php esc_html_e( 'Step 1 - Pages & menu', 'tehillim-campaign-manager' ); ?></h2>
			<p><?php esc_html_e( 'Create the campaign, personal-area, ambassadors and segulot pages - plus a navigation menu and home page - in one click. Each page is built from editable blocks (heading, intro and a dynamic block) so you can edit it freely. Re-running upgrades old single-shortcode pages to the editable block layout, without touching pages you have edited.', 'tehillim-campaign-manager' ); ?></p>
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

			<hr style="margin:28px 0">

			<h2><?php esc_html_e( 'Step 2 - Demo content', 'tehillim-campaign-manager' ); ?></h2>
			<p><?php esc_html_e( 'Fill the site with sample content so it looks exactly like the design preview: two live campaigns (with chapters, activity and a leaderboard), four prayers/segulot and demo ad banners. Use this on a fresh site only - it adds example data you can later delete.', 'tehillim-campaign-manager' ); ?></p>
			<?php if ( $demo->imported() ) : ?>
				<p><span class="dashicons dashicons-yes-alt" style="color:#46b450"></span> <?php esc_html_e( 'Demo content has already been imported.', 'tehillim-campaign-manager' ); ?></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_DEMO ); ?>">
					<?php wp_nonce_field( self::ACTION_DEMO ); ?>
					<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Import demo content', 'tehillim-campaign-manager' ); ?></button>
				</form>
			<?php endif; ?>
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
		$this->build_pages();
		$this->redirect( array( 'tcm_setup' => '1' ) );
	}

	/**
	 * One click: build every designed page + menu + front page, then import the
	 * demo content - a complete, ready-made site like a premium theme demo.
	 *
	 * @return void
	 */
	public function handle_build() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::ACTION_BUILD ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tehillim-campaign-manager' ) );
		}
		$this->build_pages();
		( new DemoContent() )->import();
		$this->redirect(
			array(
				'tcm_setup' => '1',
				'tcm_demo'  => '1',
			)
		);
	}

	/**
	 * Import the demo content (sample campaigns, prayers and ads).
	 *
	 * @return void
	 */
	public function handle_demo() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::ACTION_DEMO ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tehillim-campaign-manager' ) );
		}
		( new DemoContent() )->import();
		$this->redirect( array( 'tcm_demo' => '1' ) );
	}

	/**
	 * Create/refresh every page with its full designed block layout, set the
	 * static front page and build the navigation menu.
	 *
	 * @return void
	 */
	private function build_pages() {
		$pages = get_option( self::OPTION, array() );
		$pages = is_array( $pages ) ? $pages : array();

		foreach ( $this->definitions() as $key => $def ) {
			$content  = $this->page_content( $key, $def );
			$existing = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
			if ( $existing && get_post( $existing ) && 'trash' !== get_post_status( $existing ) ) {
				$this->upgrade_page( $existing, $def, $content );
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $def['title'],
					'post_name'    => $def['slug'],
					'post_content' => $content,
				),
				true
			);
			if ( ! is_wp_error( $id ) ) {
				update_post_meta( (int) $id, '_tcm_design_hash', md5( (string) get_post_field( 'post_content', $id ) ) );
				$pages[ $key ] = (int) $id;
			}
		}
		update_option( self::OPTION, $pages );

		// Show the campaigns/home page on the home URL when no static front page is set.
		if ( 'page' !== get_option( 'show_on_front' ) && ! empty( $pages['home'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $pages['home'] );
		}

		$this->build_menu( $pages );
	}

	/**
	 * Update an existing managed page: always fix the slug, and refresh the
	 * content only when it is still empty, the legacy shortcode, or our own
	 * last-written design - so manual edits are never overwritten.
	 *
	 * @param int                                 $id      Page id.
	 * @param array{slug:string,shortcode:string} $def     Page definition.
	 * @param string                              $content New designed content.
	 * @return void
	 */
	private function upgrade_page( $id, array $def, $content ) {
		$update = array( 'ID' => $id );
		if ( get_post_field( 'post_name', $id ) !== $def['slug'] ) {
			$update['post_name'] = $def['slug'];
		}
		$current = (string) get_post_field( 'post_content', $id );
		$hash    = (string) get_post_meta( $id, '_tcm_design_hash', true );
		$ours    = '' === trim( $current ) || trim( $current ) === $def['shortcode'] || ( '' !== $hash && md5( $current ) === $hash );
		if ( $ours ) {
			$update['post_content'] = $content;
		}
		if ( count( $update ) > 1 ) {
			wp_update_post( $update );
		}
		if ( isset( $update['post_content'] ) ) {
			update_post_meta( $id, '_tcm_design_hash', md5( (string) get_post_field( 'post_content', $id ) ) );
		}
	}

	/**
	 * Redirect back to the setup screen with success flags.
	 *
	 * @param array<string,string> $flags Query flags to set.
	 * @return void
	 */
	private function redirect( array $flags ) {
		$url = admin_url( 'edit.php?post_type=' . CampaignPostType::POST_TYPE . '&page=tcm-setup' );
		wp_safe_redirect( add_query_arg( $flags, $url ) );
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
