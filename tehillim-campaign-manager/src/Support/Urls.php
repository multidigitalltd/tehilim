<?php
/**
 * URL helpers.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the front-end URLs used by the reader flow.
 */
final class Urls {

	/**
	 * URL that opens the in-page reader for an assignment.
	 *
	 * @param int    $campaign_id   Campaign.
	 * @param int    $assignment_id Assignment.
	 * @param string $token         Access token.
	 * @return string
	 */
	public static function read( $campaign_id, $assignment_id, $token ) {
		$url = add_query_arg(
			array(
				'tcm_read' => (int) $assignment_id,
				'token'    => rawurlencode( $token ),
			),
			get_permalink( (int) $campaign_id )
		);
		return $url . '#tcm-read';
	}
}
