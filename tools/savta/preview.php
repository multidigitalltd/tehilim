<?php
/**
 * Static preview renderer (development only — not part of the theme).
 *
 * Stubs the WordPress functions the front-end templates call, then renders the
 * homepage to a standalone HTML file so markup and CSS can be checked in a
 * browser and diffed against the design reference without a WordPress install.
 *
 *   php tools/savta/preview.php > preview.html
 *   php tools/savta/preview.php first_image=            # a field override
 *   php tools/savta/preview.php mod:savta_a11y_enable=0 # a Customizer override
 *
 * @package Savta_Al_Hasafsal
 */

declare( strict_types = 1 );

define( 'ABSPATH', __DIR__ );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'OBJECT', 'OBJECT' );
function wp_enqueue_style( ...$a ) {}
function wp_enqueue_script( ...$a ) {}
function wp_script_add_data( ...$a ) {}
function wp_add_inline_script( ...$a ) {}
function wp_dequeue_style( ...$a ) {}
function comments_open() { return false; }

$GLOBALS['savta_theme_dir']    = dirname( __DIR__, 2 ) . '/savta-al-hasafsal';
$GLOBALS['savta_preview_meta'] = array();
$GLOBALS['savta_preview_mods'] = array();

foreach ( array_slice( $argv, 1 ) as $savta_pair ) {
	if ( ! str_contains( $savta_pair, '=' ) ) {
		continue;
	}

	if ( str_starts_with( $savta_pair, 'mod:' ) ) {
		list( $savta_key, $savta_value )              = explode( '=', substr( $savta_pair, 4 ), 2 );
		$GLOBALS['savta_preview_mods'][ $savta_key ] = $savta_value;
		continue;
	}

	list( $savta_key, $savta_value ) = explode( '=', $savta_pair, 2 );

	if ( str_starts_with( $savta_value, '[' ) || str_starts_with( $savta_value, '{' ) ) {
		$savta_value = json_decode( $savta_value, true );
	}

	$GLOBALS['savta_preview_meta'][ '_savta_' . $savta_key ] = $savta_value;
}

/** Minimal stand-in for the post object the templates inspect. */
class WP_Post {
	public $ID        = 1;
	public $post_type = 'page';
}

/** Minimal stand-in so the walker's parent class exists. */
class Walker_Nav_Menu {}

/* --------------------------------------------------------- escaping stubs */

function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' ); }
function esc_url_raw( $url, $protocols = null ) { return (string) $url; }
function wp_strip_all_tags( $text, $break = false ) { return strip_tags( (string) $text ); }

function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_attr__( $text, $domain = '' ) { return esc_attr( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }

/* ---------------------------------------------------------- sanitisation */

function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_email( $value ) { return (string) filter_var( (string) $value, FILTER_SANITIZE_EMAIL ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) ); }
function sanitize_file_name( $value ) { return basename( (string) $value ); }
function absint( $value ) { return abs( (int) $value ); }
function is_email( $value ) { return (bool) filter_var( (string) $value, FILTER_VALIDATE_EMAIL ); }
function wp_unslash( $value ) { return $value; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, (array) $args ); }
function wp_json_encode( $data, $flags = 0 ) { return json_encode( $data, $flags ); }

/* ------------------------------------------------------------ theme stubs */

function get_theme_file_uri( $path = '' ) {
	// SAVTA_PREVIEW_URI=http://127.0.0.1:8123/savta-al-hasafsal serves the assets over HTTP for browser tests.
	$base = getenv( 'SAVTA_PREVIEW_URI' ) ?: 'file://' . $GLOBALS['savta_theme_dir'];
	return rtrim( $base, '/' ) . '/' . ltrim( $path, '/' );
}
function get_theme_file_path( $path = '' ) { return $GLOBALS['savta_theme_dir'] . '/' . ltrim( $path, '/' ); }
function get_template_directory() { return $GLOBALS['savta_theme_dir']; }
function get_posts( $args = array() ) { return array(); }
function current_time( $format ) { return gmdate( $format ); }
function wp_timezone() { return new DateTimeZone( 'Asia/Jerusalem' ); }

function get_option( $name, $default = false ) {
	if ( 'page_on_front' === $name ) {
		return 1;
	}
	if ( 'wp_page_for_privacy_policy' === $name ) {
		return 2;
	}
	return $default;
}

function get_theme_mod( $name, $default = false ) {
	$mods = $GLOBALS['savta_preview_mods'];
	return array_key_exists( $name, $mods ) ? $mods[ $name ] : $default;
}

function get_bloginfo( $show = '', $filter = '' ) {
	$map = array(
		'name'        => 'סבתא על הספסל',
		'description' => 'מיזם חברתי למען הקהילה',
		'charset'     => 'UTF-8',
		'language'    => 'he-IL',
	);
	return $map[ $show ] ?? '';
}

function bloginfo( $show = '' ) { echo esc_html( get_bloginfo( $show ) ); }
function get_post_meta( $post_id, $key = '', $single = false ) {
	$overrides = $GLOBALS['savta_preview_meta'];
	if ( $single && array_key_exists( $key, $overrides ) ) {
		return $overrides[ $key ];
	}
	return $single ? '' : array();
}
function metadata_exists( $type, $id, $key ) { return array_key_exists( $key, $GLOBALS['savta_preview_meta'] ); }

function home_url( $path = '/' ) { return 'https://example.test' . $path; }
function get_permalink( $id = 0 ) { return 'https://example.test/page/'; }
function get_the_title( $id = 0 ) { return 2 === (int) $id ? 'מדיניות פרטיות' : 'הצהרת נגישות'; }
function get_queried_object_id() { return 1; }
function get_post( $post = null ) { return new WP_Post(); }
function get_locale() { return 'he_IL'; }

function is_front_page() { return true; }
function is_page( $page = '' ) { return false; }
function is_singular( $types = '' ) { return true; }
function has_post_thumbnail() { return false; }
function has_nav_menu( $location ) { return false; }
function get_page_template_slug( $post = null ) { return ''; }

function wp_get_attachment_image_url( $id, $size = 'full' ) { return ''; }
function wp_get_attachment_image( $id, $size = 'full', $icon = false, $attr = array() ) { return ''; }
function wp_create_nonce( $action ) { return 'preview'; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }

function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function apply_filters( $hook, $value, ...$rest ) { return $value; }
function remove_action( ...$args ) {}
function add_theme_support( ...$args ) {}
function add_editor_style( ...$args ) {}
function load_theme_textdomain( ...$args ) {}
function register_nav_menus( ...$args ) {}
function register_post_type( ...$args ) {}
function get_transient( $key ) { return false; }
function set_transient( $key, $value, $ttl ) { return true; }
function wp_salt( $scheme = 'auth' ) { return 'preview'; }
function language_attributes() { echo 'lang="he-IL" dir="rtl"'; }
function body_class( $extra = '' ) { echo 'class="sv sv-home"'; }
function wp_body_open() {}

/**
 * Renders a template part the way WordPress would.
 *
 * @param string      $slug Slug.
 * @param string|null $name Name.
 * @param array       $args Arguments.
 */
function get_template_part( $slug, $name = null, $args = array() ) {
	$file = get_theme_file_path( $slug . ( $name ? '-' . $name : '' ) . '.php' );
	if ( is_readable( $file ) ) {
		include $file;
	}
}

/* ------------------------------------------------------------ theme files */

const SAVTA_VERSION = 'preview';

require get_theme_file_path( 'inc/fields.php' );
require get_theme_file_path( 'inc/helpers.php' );
require get_theme_file_path( 'inc/setup.php' );
require get_theme_file_path( 'inc/class-savta-nav-walker.php' );
require get_theme_file_path( 'inc/customizer.php' );
require get_theme_file_path( 'inc/assets.php' );
require get_theme_file_path( 'inc/accessibility.php' );
require get_theme_file_path( 'inc/form.php' );
require get_theme_file_path( 'inc/seo.php' );

/** Emits what the enqueue layer would print in the head. */
function wp_head() {
	echo '<title>' . esc_html( get_bloginfo( 'name' ) ) . '</title>' . "\n";
	savta_preload_fonts();
	savta_social_meta();
	savta_structured_data();

	foreach ( array( 'base', 'home', 'a11y' ) as $sheet ) {
		printf( '<link rel="stylesheet" href="%s" />' . "\n", esc_url( get_theme_file_uri( 'assets/css/' . $sheet . '.css' ) ) );
	}
}

/** Emits the toolbar, the config and the scripts. */
function wp_footer() {
	savta_the_a11y_toolbar();
	printf( '<script>window.svConfig=%s;</script>' . "\n", wp_json_encode( savta_form_config(), JSON_UNESCAPED_UNICODE ) );

	foreach ( array( 'main', 'a11y' ) as $script ) {
		printf( '<script src="%s" defer></script>' . "\n", esc_url( get_theme_file_uri( 'assets/js/' . $script . '.js' ) ) );
	}
}

function get_header( $name = null ) { require get_theme_file_path( 'header.php' ); }
function get_footer( $name = null ) { require get_theme_file_path( 'footer.php' ); }

require get_theme_file_path( 'front-page.php' );
