<?php
/**
 * Campaign owner self-service.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\PostTypes\CampaignPostType;
use TCM\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets a logged-in user create and manage their own campaigns. Every mutating
 * method re-checks ownership server-side (never trust the client).
 */
final class OwnerService {

	/**
	 * @var RoundService
	 */
	private $rounds;

	/**
	 * @param RoundService|null $rounds Round service.
	 */
	public function __construct( $rounds = null ) {
		$this->rounds = $rounds ? $rounds : new RoundService();
	}

	/**
	 * Whether a user may manage a campaign.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $user_id     User.
	 * @return bool
	 */
	public function can_manage( $campaign_id, $user_id ) {
		$campaign_id = (int) $campaign_id;
		if ( ! $campaign_id || CampaignPostType::POST_TYPE !== get_post_type( $campaign_id ) ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		return $user_id && (int) get_post_field( 'post_author', $campaign_id ) === (int) $user_id;
	}

	/**
	 * Create a campaign owned by the user.
	 *
	 * @param array<string,mixed> $data    title/content/target.
	 * @param int                 $user_id Author.
	 * @return array{ok:bool,code?:string,campaign_id?:int,permalink?:string}
	 */
	public function create( array $data, $user_id ) {
		$title = sanitize_text_field( $data['title'] ?? '' );
		if ( '' === $title ) {
			return array(
				'ok'   => false,
				'code' => 'title_required',
			);
		}
		$target = max( 1, absint( $data['target'] ?? 1 ) );

		$post_id = wp_insert_post(
			array(
				'post_type'    => CampaignPostType::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => wp_kses_post( $data['content'] ?? '' ),
				'post_author'  => (int) $user_id,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return array(
				'ok'   => false,
				'code' => 'create_failed',
			);
		}

		update_post_meta( $post_id, '_tcm_target_books', $target );
		update_post_meta( $post_id, '_tcm_bonus_books', 0 );
		update_post_meta( $post_id, '_tcm_status', 'active' );
		$this->rounds->generate( $post_id, 1 );

		Logger::log( Logger::INFO, 'campaign_created', array( 'campaign_id' => $post_id ) );
		/** Fires when a campaign is created from the front end. */
		do_action( 'tcm_campaign_created', $post_id, (int) $user_id );

		return array(
			'ok'          => true,
			'campaign_id' => (int) $post_id,
			'permalink'   => get_permalink( $post_id ),
		);
	}

	/**
	 * Update a campaign's title/content/target.
	 *
	 * @param int                 $campaign_id Campaign.
	 * @param array<string,mixed> $data        Fields.
	 * @param int                 $user_id     User.
	 * @return array{ok:bool,code?:string}
	 */
	public function update( $campaign_id, array $data, $user_id ) {
		if ( ! $this->can_manage( $campaign_id, $user_id ) ) {
			return array(
				'ok'   => false,
				'code' => 'forbidden',
			);
		}
		$title = sanitize_text_field( $data['title'] ?? '' );
		if ( '' === $title ) {
			return array(
				'ok'   => false,
				'code' => 'title_required',
			);
		}
		wp_update_post(
			array(
				'ID'           => (int) $campaign_id,
				'post_title'   => $title,
				'post_content' => wp_kses_post( $data['content'] ?? '' ),
			)
		);
		update_post_meta( $campaign_id, '_tcm_target_books', max( 1, absint( $data['target'] ?? 1 ) ) );
		StatsService::flush( $campaign_id );
		return array( 'ok' => true );
	}

	/**
	 * Add one bonus book.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $user_id     User.
	 * @return array{ok:bool,code?:string}
	 */
	public function add_bonus( $campaign_id, $user_id ) {
		if ( ! $this->can_manage( $campaign_id, $user_id ) ) {
			return array(
				'ok'   => false,
				'code' => 'forbidden',
			);
		}
		$bonus = max( 0, (int) get_post_meta( $campaign_id, '_tcm_bonus_books', true ) ) + 1;
		update_post_meta( $campaign_id, '_tcm_bonus_books', $bonus );
		update_post_meta( $campaign_id, '_tcm_status', 'active' );

		$round = $this->rounds->current_round( $campaign_id );
		$repo  = new \TCM\Database\AssignmentsRepository();
		if ( 0 === $repo->count_status( $campaign_id, $round, 'free' ) + $repo->count_status( $campaign_id, $round, 'taken' ) ) {
			$this->rounds->generate( $campaign_id, $round + 1 );
		}
		StatsService::flush( $campaign_id );
		return array( 'ok' => true );
	}
}
