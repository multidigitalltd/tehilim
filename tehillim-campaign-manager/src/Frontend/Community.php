<?php
/**
 * Community widgets: ambassador leaderboard and activity feed.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Frontend;

use TCM\Contracts\Registerable;
use TCM\Database\AssignmentsRepository;
use TCM\Database\ReferralsRepository;
use TCM\PostTypes\CampaignPostType;
use TCM\Support\Hebrew;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [tehillim_leaderboard] and [tehillim_activity] — the "live community" pieces
 * from the Lovable design, rendered server-side from the plugin's own data.
 */
final class Community implements Registerable {

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_shortcode( 'tehillim_leaderboard', array( $this, 'leaderboard' ) );
		add_shortcode( 'tehillim_activity', array( $this, 'activity' ) );
	}

	/**
	 * Ambassador leaderboard for a campaign.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function leaderboard( $atts ) {
		$id = $this->resolve_id( $atts );
		if ( ! $id ) {
			return '';
		}
		Assets::ensure();
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'limit' => 10,
			),
			$atts
		);
		$rows = ( new ReferralsRepository() )->leaderboard( $id, (int) $atts['limit'] );

		$entries = array();
		foreach ( $rows as $row ) {
			$entries[] = array(
				'name'  => $row->name ? $row->name : __( 'Ambassador', 'tehillim-campaign-manager' ),
				'done'  => (int) $row->done,
				'total' => (int) $row->total,
			);
		}
		return Templating::render( 'partials/leaderboard', array( 'entries' => $entries ) );
	}

	/**
	 * Recent activity feed for a campaign.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function activity( $atts ) {
		$id = $this->resolve_id( $atts );
		if ( ! $id ) {
			return '';
		}
		Assets::ensure();
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'limit' => 12,
			),
			$atts
		);
		$rows = ( new AssignmentsRepository() )->recent_activity( $id, (int) $atts['limit'] );
		$now  = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		$items = array();
		foreach ( $rows as $row ) {
			$name    = trim( (string) $row->participant_name );
			$first   = $name ? explode( ' ', $name )[0] : __( 'A participant', 'tehillim-campaign-manager' );
			$stamp   = strtotime( (string) $row->updated_at );
			$items[] = array(
				'name'    => $first,
				'chapter' => Hebrew::chapter_label( (int) $row->chapter_number ),
				'done'    => ( 'done' === $row->status ),
				'ago'     => $stamp ? human_time_diff( $stamp, $now ) : '',
			);
		}
		return Templating::render( 'partials/activity', array( 'items' => $items ) );
	}

	/**
	 * Resolve the campaign id from attributes/context.
	 *
	 * @param array $atts Attributes.
	 * @return int
	 */
	private function resolve_id( $atts ) {
		$id = is_array( $atts ) && ! empty( $atts['id'] ) ? absint( $atts['id'] ) : 0;
		if ( ! $id && CampaignPostType::POST_TYPE === get_post_type( get_the_ID() ) ) {
			$id = (int) get_the_ID();
		}
		if ( $id && CampaignPostType::POST_TYPE !== get_post_type( $id ) ) {
			return 0;
		}
		return $id;
	}
}
