<?php
/**
 * Chapter text provider.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the readable text of a Tehillim chapter, from the saved option first
 * and an optional uploaded HTML file as a fallback. Output is sanitised with
 * wp_kses_post (never trust stored markup blindly) and cached in-process.
 */
final class ChapterTextService {

	const OPTION = 'tcm_chapters';
	const MAX    = 150;

	/**
	 * @var array<int,string>
	 */
	private $cache = array();

	/**
	 * Get sanitised HTML for a chapter, or '' when none is stored.
	 *
	 * @param int $chapter Chapter number (1..150).
	 * @return string
	 */
	public function get( $chapter ) {
		$chapter = absint( $chapter );
		if ( $chapter < 1 || $chapter > self::MAX ) {
			return '';
		}
		if ( isset( $this->cache[ $chapter ] ) ) {
			return $this->cache[ $chapter ];
		}

		$chapters = get_option( self::OPTION, array() );
		if ( is_array( $chapters ) && ! empty( $chapters[ $chapter ] ) ) {
			$this->cache[ $chapter ] = wp_kses_post( wpautop( $chapters[ $chapter ] ) );
			return $this->cache[ $chapter ];
		}

		$file = trailingslashit( wp_upload_dir()['basedir'] ) . 'tcm-tehillim/' . sprintf( '%03d.html', $chapter );
		if ( is_readable( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$this->cache[ $chapter ] = wp_kses_post( (string) file_get_contents( $file ) );
			return $this->cache[ $chapter ];
		}

		$this->cache[ $chapter ] = '';
		return '';
	}
}
