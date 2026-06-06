<?php
/**
 * Front-end assets.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\PostTypes\PrayerPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the front-end stylesheet and script — only on pages that need them
 * (campaign/prayer singulars and archives) to keep every other page lean.
 *
 * Design tokens are emitted as CSS custom properties so the admin theming still
 * controls colours, radii and fonts; the actual rules live in a real, cached,
 * versioned stylesheet (not a giant inline string).
 */
final class Assets implements Registerable {

	const HANDLE       = 'tcm-frontend';
	const FONTS_HANDLE = 'tcm-fonts';

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'on_enqueue_scripts' ) );
	}

	/**
	 * Register the handles, then enqueue them on pages that always need them.
	 *
	 * @return void
	 */
	public function on_enqueue_scripts() {
		self::register_handles();
		if ( $this->should_load() ) {
			self::ensure();
		}
	}

	/**
	 * Register the style/script handles (idempotent). Safe to call during
	 * shortcode rendering, after wp_enqueue_scripts has fired.
	 *
	 * @return void
	 */
	public static function register_handles() {
		if ( wp_style_is( self::HANDLE, 'registered' ) ) {
			return;
		}

		// Display + body typography (Frank Ruhl Libre + Heebo). Filterable so a
		// site can self-host or disable the Google Fonts request.
		$fonts_url = apply_filters(
			'tcm_fonts_url',
			'https://fonts.googleapis.com/css2?family=Frank+Ruhl+Libre:wght@500;700;800&family=Heebo:wght@400;500;700;800&display=swap'
		);
		if ( $fonts_url ) {
			wp_register_style( self::FONTS_HANDLE, $fonts_url, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}

		$deps = $fonts_url ? array( self::FONTS_HANDLE ) : array();
		wp_register_style( self::HANDLE, TCM_PLUGIN_URL . 'assets/css/frontend.css', $deps, TCM_VERSION );
		wp_add_inline_style( self::HANDLE, self::token_css() );

		wp_register_script( self::HANDLE, TCM_PLUGIN_URL . 'assets/js/frontend.js', array(), TCM_VERSION, true );
		wp_localize_script(
			self::HANDLE,
			'tcmData',
			array(
				'restUrl' => esc_url_raw( rest_url( \TCM\Rest\RestController::NS ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'copied' => __( 'Copied', 'tehillim-campaign-manager' ),
					'error'  => __( 'Something went wrong. Please try again.', 'tehillim-campaign-manager' ),
				),
			)
		);
	}

	/**
	 * Ensure the assets are enqueued. Called from any shortcode that relies on
	 * the front-end script (forms, copy buttons, owner/reader actions) so it
	 * works on arbitrary pages, not only campaign/prayer singulars.
	 *
	 * @return void
	 */
	public static function ensure() {
		self::register_handles();
		wp_enqueue_style( self::HANDLE );
		wp_enqueue_script( self::HANDLE );
	}

	/**
	 * Whether the current request should load front-end assets up-front.
	 *
	 * @return bool
	 */
	private function should_load() {
		if ( is_singular( array( CampaignPostType::POST_TYPE, PrayerPostType::POST_TYPE ) ) ) {
			return true;
		}
		if ( is_post_type_archive( array( CampaignPostType::POST_TYPE, PrayerPostType::POST_TYPE ) ) ) {
			return true;
		}
		$post = get_post();
		if ( $post && has_shortcode( (string) $post->post_content, 'tehillim_campaigns' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Build the :root design-token block from saved options, with accessible
	 * defaults. Only values are dynamic; all rules live in the static CSS file.
	 *
	 * @return string
	 */
	private static function token_css() {
		$options = get_option( 'tcm_options', array() );

		$hex = static function ( $key, $default ) use ( $options ) {
			$value = isset( $options[ $key ] ) ? sanitize_hex_color( $options[ $key ] ) : '';
			return $value ? $value : $default;
		};
		$int = static function ( $key, $default, $min, $max ) use ( $options ) {
			$value = isset( $options[ $key ] ) ? absint( $options[ $key ] ) : $default;
			return max( $min, min( $max, $value ) );
		};

		// Defaults follow the "Psalms Unite" skin: deep-indigo primary, soft-gold
		// accent, warm parchment surfaces. All remain overridable from settings.
		$vars = array(
			'--tcm-primary'       => $hex( 'design_primary_color', '#3a3578' ),
			'--tcm-secondary'     => $hex( 'design_secondary_color', '#c39a45' ),
			'--tcm-card-bg'       => $hex( 'design_card_bg', '#ffffff' ),
			'--tcm-text'          => $hex( 'design_text_color', '#23213a' ),
			'--tcm-muted'         => $hex( 'design_muted_color', '#6b6880' ),
			'--tcm-field-bg'      => $hex( 'design_field_bg', '#ffffff' ),
			'--tcm-field-border'  => $hex( 'design_field_border', '#e0dccf' ),
			'--tcm-button-text'   => $hex( 'design_button_text_color', '#ffffff' ),
			'--tcm-radius'        => $int( 'design_radius', 18, 0, 60 ) . 'px',
			'--tcm-button-radius' => $int( 'design_button_radius', 14, 0, 999 ) . 'px',
			'--tcm-field-radius'  => $int( 'design_field_radius', 12, 0, 60 ) . 'px',
			'--tcm-title-size'    => $int( 'design_title_size', 28, 16, 64 ) . 'px',
			'--tcm-max-width'     => $int( 'design_max_width', 980, 320, 1600 ) . 'px',
		);

		$declarations = '';
		foreach ( $vars as $name => $value ) {
			$declarations .= $name . ':' . $value . ';';
		}
		return ':root{' . $declarations . '}';
	}
}
