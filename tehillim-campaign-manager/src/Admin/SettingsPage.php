<?php
/**
 * Settings page.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the settings page (tabbed) and — crucially — a strict
 * sanitize callback that allowlists and sanitises every field on the way in.
 * Secret values are write-only: rendered as empty password fields and kept
 * unchanged when submitted blank, so they are never echoed back to the browser.
 */
final class SettingsPage implements Registerable {

	const GROUP = 'tcm_settings';

	/**
	 * Secret option keys (write-only).
	 *
	 * @var string[]
	 */
	private $secrets = array( 'webhook_secret', 'turnstile_secret_key' );

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Register the option with a sanitize callback.
	 *
	 * @return void
	 */
	public function register_setting() {
		register_setting(
			self::GROUP,
			Options::KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => Options::defaults(),
			)
		);
	}

	/**
	 * Add the submenu page.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'edit.php?post_type=' . CampaignPostType::POST_TYPE,
			__( 'Settings', 'tehillim-campaign-manager' ),
			__( 'Settings', 'tehillim-campaign-manager' ),
			'manage_options',
			'tcm-settings',
			array( $this, 'render' )
		);
	}

	/**
	 * Sanitize submitted options (allowlist + per-field cleaning).
	 *
	 * @param mixed $input Raw input.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$existing = Options::all();
		$clean    = array();

		$text = array( 'link_base', 'join_title', 'join_button_text', 'multi_chapter_options', 'email_subject' );
		foreach ( $text as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : ( $existing[ $key ] ?? '' );
		}
		$base               = preg_replace( '/[^a-z0-9\-]/', '', strtolower( $clean['link_base'] ) );
		$clean['link_base'] = $base ? $base : 'tehillim';

		// Checkboxes.
		$clean['allow_multi_chapters'] = empty( $input['allow_multi_chapters'] ) ? '0' : '1';
		$clean['allow_full_book']      = empty( $input['allow_full_book'] ) ? '0' : '1';
		$clean['reminders_enabled']    = empty( $input['reminders_enabled'] ) ? '0' : '1';

		// Reminder timings (integers with sensible minimums).
		$ints = array(
			'reminder_hours'        => 1,
			'reminder_max'          => 0,
			'release_warning_hours' => 1,
			'release_after_hours'   => 1,
		);
		foreach ( $ints as $key => $min ) {
			$clean[ $key ] = (string) max( $min, isset( $input[ $key ] ) ? absint( $input[ $key ] ) : (int) ( $existing[ $key ] ?? $min ) );
		}

		// Long text (placeholders + light HTML allowed).
		$clean['email_body'] = isset( $input['email_body'] ) ? wp_kses_post( $input['email_body'] ) : ( $existing['email_body'] ?? '' );

		// URLs.
		$clean['webhook_url']        = isset( $input['webhook_url'] ) ? esc_url_raw( $input['webhook_url'] ) : '';
		$clean['turnstile_site_key'] = isset( $input['turnstile_site_key'] ) ? sanitize_text_field( $input['turnstile_site_key'] ) : '';

		// Design tokens (hex).
		foreach ( array( 'design_primary_color', 'design_secondary_color', 'design_card_bg', 'design_text_color', 'design_muted_color', 'design_field_bg', 'design_field_border', 'design_button_text_color' ) as $key ) {
			if ( ! empty( $input[ $key ] ) && sanitize_hex_color( $input[ $key ] ) ) {
				$clean[ $key ] = sanitize_hex_color( $input[ $key ] );
			} elseif ( isset( $existing[ $key ] ) ) {
				$clean[ $key ] = $existing[ $key ];
			}
		}

		// Fonts: family names (strip characters that could break the declaration).
		foreach ( array( 'design_font_family', 'design_display_font' ) as $key ) {
			$value         = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
			$clean[ $key ] = (string) preg_replace( '/[;{}<>]/', '', $value );
		}
		$clean['design_disable_google_fonts'] = empty( $input['design_disable_google_fonts'] ) ? '0' : '1';

		// Custom CSS (e.g. self-hosted @font-face). Strip tags to prevent a
		// </style> break-out; CSS syntax itself is preserved.
		$clean['design_custom_css'] = isset( $input['design_custom_css'] ) ? wp_strip_all_tags( $input['design_custom_css'] ) : '';

		// Secrets: keep existing when the field is left blank.
		foreach ( $this->secrets as $key ) {
			$submitted     = isset( $input[ $key ] ) ? trim( (string) $input[ $key ] ) : '';
			$clean[ $key ] = ( '' !== $submitted ) ? $submitted : ( $existing[ $key ] ?? '' );
		}

		return $clean;
	}

	/**
	 * Render the settings form.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o    = Options::all();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = array(
			'general'   => __( 'General', 'tehillim-campaign-manager' ),
			'messaging' => __( 'Messaging', 'tehillim-campaign-manager' ),
			'reminders' => __( 'Reminders', 'tehillim-campaign-manager' ),
			'webhooks'  => __( 'Webhooks', 'tehillim-campaign-manager' ),
			'design'    => __( 'Design', 'tehillim-campaign-manager' ),
		);
		$base = admin_url( 'edit.php?post_type=' . CampaignPostType::POST_TYPE . '&page=tcm-settings' );
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'Tehillim Campaign Manager — Settings', 'tehillim-campaign-manager' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', $key, $base ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</h2>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<input type="hidden" name="tcm_options[__tab]" value="<?php echo esc_attr( $tab ); ?>">
				<table class="form-table" role="presentation">
					<?php $this->fields( $tab, $o ); ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Output the fields for a tab.
	 *
	 * @param string              $tab Tab key.
	 * @param array<string,mixed> $o   Options.
	 * @return void
	 */
	private function fields( $tab, array $o ) {
		if ( 'general' === $tab ) {
			$this->text_row( 'link_base', __( 'Campaign URL base', 'tehillim-campaign-manager' ), $o, __( 'English letters, digits and dashes. After changing: Settings → Permalinks → Save.', 'tehillim-campaign-manager' ) );
			$this->text_row( 'join_title', __( 'Join form heading', 'tehillim-campaign-manager' ), $o );
			$this->text_row( 'join_button_text', __( 'Join button text', 'tehillim-campaign-manager' ), $o );
			$this->checkbox_row( 'allow_multi_chapters', __( 'Allow taking several chapters', 'tehillim-campaign-manager' ), $o );
			$this->text_row( 'multi_chapter_options', __( 'Multi-chapter options', 'tehillim-campaign-manager' ), $o, __( 'Comma separated, e.g. 3,5,10', 'tehillim-campaign-manager' ) );
			$this->checkbox_row( 'allow_full_book', __( 'Allow taking a whole book', 'tehillim-campaign-manager' ), $o );
		} elseif ( 'messaging' === $tab ) {
			$this->text_row( 'email_subject', __( 'Email subject', 'tehillim-campaign-manager' ), $o );
			$this->textarea_row( 'email_body', __( 'Email body', 'tehillim-campaign-manager' ), $o, __( 'Placeholders: {name}, {campaign_title}, {chapter}, {read_url}', 'tehillim-campaign-manager' ) );
		} elseif ( 'reminders' === $tab ) {
			echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'Reminders are delivered as webhook events (chapter_reminder, chapter_release_warning, chapter_auto_released). Each payload includes participant_phone and a read_url to the specific chapter, so you can route it to WhatsApp via your automation.', 'tehillim-campaign-manager' ) . '</p></td></tr>';
			$this->checkbox_row( 'reminders_enabled', __( 'Enable reminders', 'tehillim-campaign-manager' ), $o );
			$this->int_row( 'reminder_hours', __( 'Remind after (hours)', 'tehillim-campaign-manager' ), $o, 1 );
			$this->int_row( 'reminder_max', __( 'Maximum reminders', 'tehillim-campaign-manager' ), $o, 0 );
			$this->int_row( 'release_warning_hours', __( 'Release warning after (hours)', 'tehillim-campaign-manager' ), $o, 1 );
			$this->int_row( 'release_after_hours', __( 'Auto-release after (hours)', 'tehillim-campaign-manager' ), $o, 1 );
		} elseif ( 'webhooks' === $tab ) {
			$this->url_row( 'webhook_url', __( 'Webhook URL', 'tehillim-campaign-manager' ), $o );
			$this->secret_row( 'webhook_secret', __( 'Webhook secret (HMAC)', 'tehillim-campaign-manager' ), $o );
			$this->text_row( 'turnstile_site_key', __( 'Turnstile site key', 'tehillim-campaign-manager' ), $o );
			$this->secret_row( 'turnstile_secret_key', __( 'Turnstile secret key', 'tehillim-campaign-manager' ), $o );
		} else {
			foreach ( array(
				'design_primary_color'     => __( 'Primary colour', 'tehillim-campaign-manager' ),
				'design_secondary_color'   => __( 'Secondary / progress', 'tehillim-campaign-manager' ),
				'design_text_color'        => __( 'Text colour', 'tehillim-campaign-manager' ),
				'design_button_text_color' => __( 'Button text colour', 'tehillim-campaign-manager' ),
			) as $key => $label ) {
				$this->color_row( $key, $label, $o );
			}
			echo '<tr><td colspan="2"><h2>' . esc_html__( 'Fonts', 'tehillim-campaign-manager' ) . '</h2></td></tr>';
			$this->text_row( 'design_font_family', __( 'Body font family', 'tehillim-campaign-manager' ), $o, __( 'CSS font-family, e.g. "MyAA Pro", Heebo, sans-serif. Leave blank for the default.', 'tehillim-campaign-manager' ) );
			$this->text_row( 'design_display_font', __( 'Headings font family', 'tehillim-campaign-manager' ), $o );
			$this->checkbox_row( 'design_disable_google_fonts', __( 'Self-host fonts (do not load Google Fonts)', 'tehillim-campaign-manager' ), $o, '0' );
			$this->textarea_row( 'design_custom_css', __( 'Custom CSS (e.g. @font-face for a licensed font)', 'tehillim-campaign-manager' ), $o, __( 'Upload your font files (Media or theme) and paste the @font-face here, then set the family name above.', 'tehillim-campaign-manager' ) );
		}
	}

	/**
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param array  $o     Options.
	 * @param string $help  Description.
	 * @return void
	 */
	private function text_row( $key, $label, $o, $help = '' ) {
		$this->row(
			$label,
			'<input type="text" class="regular-text" name="tcm_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $o[ $key ] ?? '' ) . '">',
			$help
		);
	}

	/**
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param array  $o     Options.
	 * @return void
	 */
	private function url_row( $key, $label, $o ) {
		$this->row( $label, '<input type="url" class="regular-text" placeholder="https://..." name="tcm_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $o[ $key ] ?? '' ) . '">' );
	}

	/**
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param array  $o     Options.
	 * @return void
	 */
	private function secret_row( $key, $label, $o ) {
		$set = ! empty( $o[ $key ] );
		$this->row(
			$label,
			'<input type="password" class="regular-text" autocomplete="new-password" name="tcm_options[' . esc_attr( $key ) . ']" value="" placeholder="' . esc_attr( $set ? '•••••• ' . __( '(saved — leave blank to keep)', 'tehillim-campaign-manager' ) : '' ) . '">'
		);
	}

	/**
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param array  $o     Options.
	 * @return void
	 */
	private function color_row( $key, $label, $o ) {
		$this->row( $label, '<input type="color" name="tcm_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $o[ $key ] ?? '#000000' ) . '">' );
	}

	/**
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param array  $o     Options.
	 * @param string $help  Description.
	 * @return void
	 */
	private function textarea_row( $key, $label, $o, $help = '' ) {
		$this->row(
			$label,
			'<textarea rows="8" class="large-text code" name="tcm_options[' . esc_attr( $key ) . ']">' . esc_textarea( $o[ $key ] ?? '' ) . '</textarea>',
			$help
		);
	}

	/**
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param array  $o     Options.
	 * @return void
	 */
	/**
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param array  $o     Options.
	 * @param int    $min   Minimum value.
	 * @return void
	 */
	private function int_row( $key, $label, $o, $min = 0 ) {
		$this->row(
			$label,
			'<input type="number" min="' . esc_attr( (string) $min ) . '" name="tcm_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $o[ $key ] ?? '' ) . '" style="width:100px">'
		);
	}

	/**
	 * @param string $key     Key.
	 * @param string $label   Label.
	 * @param array  $o       Options.
	 * @param string $default Default state when unset ('1' = checked).
	 * @return void
	 */
	private function checkbox_row( $key, $label, $o, $default = '1' ) {
		$this->row(
			$label,
			'<label><input type="checkbox" name="tcm_options[' . esc_attr( $key ) . ']" value="1" ' . checked( ( $o[ $key ] ?? $default ), '1', false ) . '> ' . esc_html__( 'Enabled', 'tehillim-campaign-manager' ) . '</label>'
		);
	}

	/**
	 * Output a settings table row.
	 *
	 * @param string $label Label.
	 * @param string $field Field HTML (already escaped).
	 * @param string $help  Optional description.
	 * @return void
	 */
	private function row( $label, $field, $help = '' ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	}
}
