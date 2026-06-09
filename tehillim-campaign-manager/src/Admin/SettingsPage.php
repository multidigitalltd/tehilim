<?php
/**
 * Settings page.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\Services\ZmanimService;
use TCM\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the settings page (tabbed) and - crucially - a strict
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Load the media library + font picker on the settings screen only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'tcm-settings' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'tcm-admin-fonts', TCM_PLUGIN_URL . 'assets/js/admin-fonts.js', array(), TCM_VERSION, true );
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
		$clean['allow_multi_chapters']   = empty( $input['allow_multi_chapters'] ) ? '0' : '1';
		$clean['allow_full_book']        = empty( $input['allow_full_book'] ) ? '0' : '1';
		$clean['auto_publish_campaigns'] = empty( $input['auto_publish_campaigns'] ) ? '0' : '1';
		$city                            = isset( $input['zmanim_city'] ) ? sanitize_key( $input['zmanim_city'] ) : 'tel_aviv';
		$clean['zmanim_city']            = isset( ZmanimService::cities()[ $city ] ) ? $city : 'tel_aviv';
		$clean['a11y_contact_name']      = isset( $input['a11y_contact_name'] ) ? sanitize_text_field( $input['a11y_contact_name'] ) : '';
		$clean['a11y_contact_email']     = isset( $input['a11y_contact_email'] ) ? sanitize_email( $input['a11y_contact_email'] ) : '';
		$clean['a11y_contact_phone']     = isset( $input['a11y_contact_phone'] ) ? sanitize_text_field( $input['a11y_contact_phone'] ) : '';
		$clean['reminders_enabled']      = empty( $input['reminders_enabled'] ) ? '0' : '1';

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

		// Message templates (plain text with {placeholders}).
		foreach ( array( 'tpl_campaign_new', 'tpl_campaign_nearly_done', 'tpl_subscription_campaign', 'tpl_subscription_daily' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_textarea_field( $input[ $key ] ) : ( $existing[ $key ] ?? '' );
		}
		// Per-event email subjects (text) and bodies (light HTML allowed).
		foreach ( array( 'email_subject_daily', 'email_subject_campaign' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : ( $existing[ $key ] ?? '' );
		}
		foreach ( array( 'email_body_daily', 'email_body_campaign' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? wp_kses_post( $input[ $key ] ) : ( $existing[ $key ] ?? '' );
		}

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

		// Fonts: family / name values (strip characters that could break the
		// declaration); the custom font URL is a normal URL.
		foreach ( array( 'design_font_family', 'design_display_font', 'design_custom_font_name' ) as $key ) {
			$value         = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
			$clean[ $key ] = (string) preg_replace( '/[;{}<>"]/', '', $value );
		}
		$clean['design_custom_font_url']      = isset( $input['design_custom_font_url'] ) ? esc_url_raw( $input['design_custom_font_url'] ) : '';
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
			'templates' => __( 'Message templates', 'tehillim-campaign-manager' ),
			'reminders' => __( 'Reminders', 'tehillim-campaign-manager' ),
			'webhooks'  => __( 'Webhooks', 'tehillim-campaign-manager' ),
			'design'    => __( 'Design', 'tehillim-campaign-manager' ),
		);
		$base = admin_url( 'edit.php?post_type=' . CampaignPostType::POST_TYPE . '&page=tcm-settings' );
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'Tehillim Campaign Manager - Settings', 'tehillim-campaign-manager' ); ?></h1>
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
			$this->text_row( 'link_base', __( 'Campaign URL base', 'tehillim-campaign-manager' ), $o, __( 'English letters, digits and dashes. After changing: Settings - Permalinks - Save.', 'tehillim-campaign-manager' ) );
			$this->text_row( 'join_title', __( 'Join form heading', 'tehillim-campaign-manager' ), $o );
			$this->text_row( 'join_button_text', __( 'Join button text', 'tehillim-campaign-manager' ), $o );
			$this->checkbox_row( 'allow_multi_chapters', __( 'Allow taking several chapters', 'tehillim-campaign-manager' ), $o );
			$this->text_row( 'multi_chapter_options', __( 'Multi-chapter options', 'tehillim-campaign-manager' ), $o, __( 'Comma separated, e.g. 3,5,10', 'tehillim-campaign-manager' ) );
			$this->checkbox_row( 'allow_full_book', __( 'Allow taking a whole book', 'tehillim-campaign-manager' ), $o );
			$this->checkbox_row( 'auto_publish_campaigns', __( 'Publish user-created campaigns immediately (otherwise they await admin approval)', 'tehillim-campaign-manager' ), $o );
			$this->city_row( $o );
			echo '<tr><td colspan="2"><h2>' . esc_html__( 'Accessibility statement', 'tehillim-campaign-manager' ) . '</h2><p class="description">' . esc_html__( 'Contact details for the accessibility coordinator, shown by the [tehillim_accessibility_statement] shortcode.', 'tehillim-campaign-manager' ) . '</p></td></tr>';
			$this->text_row( 'a11y_contact_name', __( 'Accessibility coordinator', 'tehillim-campaign-manager' ), $o );
			$this->text_row( 'a11y_contact_email', __( 'Accessibility email', 'tehillim-campaign-manager' ), $o );
			$this->text_row( 'a11y_contact_phone', __( 'Accessibility phone', 'tehillim-campaign-manager' ), $o );
		} elseif ( 'messaging' === $tab ) {
			$this->text_row( 'email_subject', __( 'Email subject', 'tehillim-campaign-manager' ), $o );
			$this->textarea_row( 'email_body', __( 'Email body', 'tehillim-campaign-manager' ), $o, __( 'Placeholders: {name}, {campaign_title}, {chapter}, {read_url}', 'tehillim-campaign-manager' ) );
		} elseif ( 'templates' === $tab ) {
			echo '<tr><td colspan="2"><p class="description">' . esc_html__( 'These texts are included as a ready-to-send "message" field in the matching webhook events, so your WhatsApp/automation can forward them as-is. One line or several; leave a placeholder out to omit it.', 'tehillim-campaign-manager' ) . '</p></td></tr>';
			$this->textarea_row( 'tpl_campaign_new', __( 'New campaign (broadcast)', 'tehillim-campaign-manager' ), $o, __( 'Placeholders: {campaign_title}, {dedicated_to}, {link}', 'tehillim-campaign-manager' ) );
			$this->textarea_row( 'tpl_campaign_nearly_done', __( 'Campaign almost done (broadcast)', 'tehillim-campaign-manager' ), $o, __( 'Placeholders: {campaign_title}, {remaining}, {link}', 'tehillim-campaign-manager' ) );
			$this->textarea_row( 'tpl_subscription_campaign', __( 'Personal campaign alert (Tehillim Corps)', 'tehillim-campaign-manager' ), $o, __( 'Placeholders: {campaign_title}, {link}', 'tehillim-campaign-manager' ) );
			$this->textarea_row( 'tpl_subscription_daily', __( 'Daily chapter (subscriber)', 'tehillim-campaign-manager' ), $o, __( 'Placeholders: {chapter}', 'tehillim-campaign-manager' ) );
			echo '<tr><td colspan="2"><h2>' . esc_html__( 'Email per event (email-channel subscribers)', 'tehillim-campaign-manager' ) . '</h2><p class="description">' . esc_html__( 'Sent as a designed RTL email (with an email_footer ad). Placeholders: {name}, {chapter}, {campaign_title}, {link}, {date_he}, {site_name}.', 'tehillim-campaign-manager' ) . '</p></td></tr>';
			$this->text_row( 'email_subject_daily', __( 'Daily email subject', 'tehillim-campaign-manager' ), $o );
			$this->textarea_row( 'email_body_daily', __( 'Daily email body', 'tehillim-campaign-manager' ), $o );
			$this->text_row( 'email_subject_campaign', __( 'Campaign-alert email subject', 'tehillim-campaign-manager' ), $o );
			$this->textarea_row( 'email_body_campaign', __( 'Campaign-alert email body', 'tehillim-campaign-manager' ), $o );
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
			$this->webhook_events_help();
		} else {
			foreach ( array(
				'design_primary_color'     => __( 'Primary colour', 'tehillim-campaign-manager' ),
				'design_secondary_color'   => __( 'Secondary / progress', 'tehillim-campaign-manager' ),
				'design_text_color'        => __( 'Text colour', 'tehillim-campaign-manager' ),
				'design_button_text_color' => __( 'Button text colour', 'tehillim-campaign-manager' ),
			) as $key => $label ) {
				$this->color_row( $key, $label, $o );
			}
			echo '<tr><td colspan="2"><h2>' . esc_html__( 'Your own font (easy)', 'tehillim-campaign-manager' ) . '</h2>';
			echo '<p class="description">' . esc_html__( 'Upload your licensed font file, choose it here and give it a name - the plugin builds the @font-face and applies it everywhere. No code needed.', 'tehillim-campaign-manager' ) . '</p></td></tr>';
			$this->text_row( 'design_custom_font_name', __( 'Font name', 'tehillim-campaign-manager' ), $o, __( 'Any name, e.g. AA Pro.', 'tehillim-campaign-manager' ) );
			$this->font_file_row( 'design_custom_font_url', __( 'Font file', 'tehillim-campaign-manager' ), $o );

			echo '<tr><td colspan="2"><h2>' . esc_html__( 'Advanced fonts', 'tehillim-campaign-manager' ) . '</h2></td></tr>';
			$this->text_row( 'design_font_family', __( 'Body font family', 'tehillim-campaign-manager' ), $o, __( 'Overrides the body font stack, e.g. "AA Pro", Heebo, sans-serif.', 'tehillim-campaign-manager' ) );
			$this->text_row( 'design_display_font', __( 'Headings font family', 'tehillim-campaign-manager' ), $o );
			$this->checkbox_row( 'design_disable_google_fonts', __( 'Do not load Google Fonts (Heebo / Frank Ruhl Libre)', 'tehillim-campaign-manager' ), $o, '0' );
			$this->textarea_row( 'design_custom_css', __( 'Custom CSS', 'tehillim-campaign-manager' ), $o, __( 'For advanced cases (extra @font-face weights, tweaks).', 'tehillim-campaign-manager' ) );
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
	 * Print a reference table of every webhook event the plugin dispatches, so
	 * whoever wires the automation knows what to route. The signed payload of
	 * each event always includes its fields plus an `event` name.
	 *
	 * @return void
	 */
	private function webhook_events_help() {
		$events = array(
			'campaign_new'            => __( 'A new campaign was published - broadcast it to the group.', 'tehillim-campaign-manager' ),
			'campaign_nearly_done'    => __( 'A campaign is almost finished - rally people to close it.', 'tehillim-campaign-manager' ),
			'campaign_completed'      => __( 'A campaign reached its goal.', 'tehillim-campaign-manager' ),
			'subscription_campaign'   => __( 'Personal campaign alert for a Tehillim Corps subscriber (kind: new / nearly_done).', 'tehillim-campaign-manager' ),
			'subscription_daily'      => __( 'Daily chapter for a subscriber.', 'tehillim-campaign-manager' ),
			'chapter_claimed'         => __( 'Someone took a chapter.', 'tehillim-campaign-manager' ),
			'chapter_done'            => __( 'A chapter was completed.', 'tehillim-campaign-manager' ),
			'chapter_released'        => __( 'A chapter was released back to the pool.', 'tehillim-campaign-manager' ),
			'book_completed'          => __( 'A book (150 chapters) was completed.', 'tehillim-campaign-manager' ),
			'chapter_reminder'        => __( 'Reminder to a participant to finish their chapter.', 'tehillim-campaign-manager' ),
			'chapter_release_warning' => __( 'Warning that a chapter is about to be auto-released.', 'tehillim-campaign-manager' ),
			'chapter_auto_released'   => __( 'A chapter was automatically released after timeout.', 'tehillim-campaign-manager' ),
		);

		echo '<tr><td colspan="2"><h2>' . esc_html__( 'Webhook events', 'tehillim-campaign-manager' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Every event is POSTed to your Webhook URL, signed with the HMAC secret. Route them in your automation (e.g. to a WhatsApp group or personal messages).', 'tehillim-campaign-manager' ) . '</p>';
		echo '<table class="widefat striped" style="max-width:760px;margin-top:8px"><thead><tr><th>' . esc_html__( 'Event', 'tehillim-campaign-manager' ) . '</th><th>' . esc_html__( 'Fires when', 'tehillim-campaign-manager' ) . '</th></tr></thead><tbody>';
		foreach ( $events as $name => $desc ) {
			echo '<tr><td><code>' . esc_html( $name ) . '</code></td><td>' . esc_html( $desc ) . '</td></tr>';
		}
		echo '</tbody></table></td></tr>';
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
	 * A URL field with a "Choose file" media-library button.
	 *
	 * @param string $key   Key.
	 * @param string $label Label.
	 * @param array  $o     Options.
	 * @return void
	 */
	private function font_file_row( $key, $label, $o ) {
		$id = 'tcm-field-' . $key;
		$this->row(
			$label,
			'<input type="url" id="' . esc_attr( $id ) . '" class="regular-text" placeholder="https://...woff2" name="tcm_options[' . esc_attr( $key ) . ']" value="' . esc_attr( $o[ $key ] ?? '' ) . '"> '
			. '<button type="button" class="button tcm-font-pick" data-target="' . esc_attr( $id ) . '" data-title="' . esc_attr__( 'Choose font file', 'tehillim-campaign-manager' ) . '" data-button="' . esc_attr__( 'Use this file', 'tehillim-campaign-manager' ) . '">' . esc_html__( 'Choose file', 'tehillim-campaign-manager' ) . '</button>',
			__( 'Supported: woff2, woff, ttf, otf.', 'tehillim-campaign-manager' )
		);
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
			'<input type="password" class="regular-text" autocomplete="new-password" name="tcm_options[' . esc_attr( $key ) . ']" value="" placeholder="' . esc_attr( $set ? '•••••• ' . __( '(saved - leave blank to keep)', 'tehillim-campaign-manager' ) : '' ) . '">'
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
	 * City selector for the zmanim/Hebrew-date area.
	 *
	 * @param array<string,mixed> $o Options.
	 * @return void
	 */
	private function city_row( $o ) {
		$current = (string) ( $o['zmanim_city'] ?? 'tel_aviv' );
		$html    = '<select name="tcm_options[zmanim_city]">';
		foreach ( ZmanimService::cities() as $key => $city ) {
			$html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $current, $key, false ) . '>' . esc_html( $city['label'] ) . '</option>';
		}
		$html .= '</select>';
		$this->row(
			__( 'Zmanim city', 'tehillim-campaign-manager' ),
			$html,
			__( 'City used for the Hebrew date and daily times. Shortcode: [tehillim_zmanim]', 'tehillim-campaign-manager' )
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
