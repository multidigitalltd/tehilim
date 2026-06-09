<?php
/**
 * Standalone reader, prayers/segulot archive and contact form shortcodes.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Services\ChapterTextService;
use TCM\Support\Hebrew;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site-wide reading and contact shortcodes, independent of campaigns:
 * - [tehillim_reader]       a comfortable chapter-by-chapter Tehillim reader.
 * - [tehillim_prayers]      a designed archive of the site's regular posts.
 * - [tehillim_contact_form] an accessible contact form (with Turnstile).
 */
final class SiteExtras implements Registerable {

	/**
	 * @var ChapterTextService
	 */
	private $chapter_text;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->chapter_text = new ChapterTextService();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_shortcode( 'tehillim_reader', array( $this, 'reader' ) );
		add_shortcode( 'tehillim_prayers', array( $this, 'prayers' ) );
		add_shortcode( 'tehillim_contact_form', array( $this, 'contact_form' ) );
	}

	/**
	 * Standalone Tehillim reader.
	 *
	 * @return string
	 */
	public function reader() {
		Assets::ensure();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only chapter selection.
		$chapter = isset( $_GET['tcm_ch'] ) ? absint( $_GET['tcm_ch'] ) : 1;
		if ( $chapter < 1 || $chapter > 150 ) {
			$chapter = 1;
		}
		return Templating::render(
			'partials/reader-standalone',
			array(
				'chapter' => $chapter,
				'prev'    => $chapter <= 1 ? 150 : $chapter - 1,
				'next'    => $chapter >= 150 ? 1 : $chapter + 1,
				'text'    => $this->chapter_text->get( $chapter ),
			)
		);
	}

	/**
	 * Prayers / segulot archive built from the site's regular posts.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function prayers( $atts ) {
		Assets::ensure();
		$atts = shortcode_atts(
			array(
				'category' => '',
				'count'    => 24,
			),
			$atts,
			'tehillim_prayers'
		);
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, min( 60, (int) $atts['count'] ) ),
			'no_found_rows'  => true,
		);
		if ( '' !== $atts['category'] ) {
			$args['category_name'] = sanitize_title( (string) $atts['category'] );
		}
		$query = new \WP_Query( $args );
		$posts = array();
		foreach ( $query->posts as $post ) {
			$raw = (string) get_post_field( 'post_excerpt', $post );
			if ( '' === $raw ) {
				$raw = (string) get_post_field( 'post_content', $post );
			}
			$posts[] = array(
				'title'     => get_the_title( $post ),
				'permalink' => (string) get_permalink( $post ),
				'excerpt'   => wp_trim_words( wp_strip_all_tags( $raw ), 24 ),
				'thumb'     => (string) get_the_post_thumbnail_url( $post, 'large' ),
				'date'      => get_the_date( '', $post ),
			);
		}
		return Templating::render( 'partials/prayers-archive', array( 'posts' => $posts ) );
	}

	/**
	 * Accessible contact form (with optional Turnstile).
	 *
	 * @return string
	 */
	public function contact_form() {
		Assets::ensure();
		$options  = get_option( 'tcm_options', array() );
		$site_key = isset( $options['turnstile_site_key'] ) ? trim( (string) $options['turnstile_site_key'] ) : '';
		if ( '' !== $site_key ) {
			wp_enqueue_script( 'tcm-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}
		return Templating::render( 'partials/contact-form', array( 'site_key' => $site_key ) );
	}
}
