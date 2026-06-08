<?php
/**
 * Achievement badges (gamification).
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Computes earned achievement badges. Pure, data-driven helpers so they can be
 * reused for ambassadors, participants and campaign milestones — and unit
 * tested without WordPress.
 */
final class BadgeService {

	/**
	 * Ambassador tiers by completed (referred-and-done) chapters, highest first.
	 *
	 * @return array<int,array{min:int,slug:string,label:string,icon:string}>
	 */
	public static function ambassador_tiers() {
		return array(
			array(
				'min'   => 25,
				'slug'  => 'gold',
				'label' => __( 'Gold ambassador', 'tehillim-campaign-manager' ),
				'icon'  => '👑',
			),
			array(
				'min'   => 10,
				'slug'  => 'star',
				'label' => __( 'Top ambassador', 'tehillim-campaign-manager' ),
				'icon'  => '🏆',
			),
			array(
				'min'   => 5,
				'slug'  => 'active',
				'label' => __( 'Active ambassador', 'tehillim-campaign-manager' ),
				'icon'  => '⭐',
			),
			array(
				'min'   => 1,
				'slug'  => 'rookie',
				'label' => __( 'Ambassador', 'tehillim-campaign-manager' ),
				'icon'  => '🌱',
			),
		);
	}

	/**
	 * Participant tiers by completed chapters, highest first.
	 *
	 * @return array<int,array{min:int,slug:string,label:string,icon:string}>
	 */
	public static function participant_tiers() {
		return array(
			array(
				'min'   => 150,
				'slug'  => 'book',
				'label' => __( 'Completed a book', 'tehillim-campaign-manager' ),
				'icon'  => '📚',
			),
			array(
				'min'   => 18,
				'slug'  => 'devoted',
				'label' => __( 'Devoted reader', 'tehillim-campaign-manager' ),
				'icon'  => '🔥',
			),
			array(
				'min'   => 5,
				'slug'  => 'regular',
				'label' => __( 'Regular reader', 'tehillim-campaign-manager' ),
				'icon'  => '🌟',
			),
			array(
				'min'   => 1,
				'slug'  => 'first',
				'label' => __( 'First chapter', 'tehillim-campaign-manager' ),
				'icon'  => '🌱',
			),
		);
	}

	/**
	 * The highest tier reached for a given count, or null when none.
	 *
	 * @param array<int,array{min:int,slug:string,label:string,icon:string}> $tiers Tier list (highest first).
	 * @param int                                                            $count Completed count.
	 * @return array{slug:string,label:string,icon:string}|null
	 */
	public static function highest( array $tiers, $count ) {
		$count = (int) $count;
		foreach ( $tiers as $tier ) {
			if ( $count >= (int) $tier['min'] ) {
				return array(
					'slug'  => $tier['slug'],
					'label' => $tier['label'],
					'icon'  => $tier['icon'],
				);
			}
		}
		return null;
	}

	/**
	 * Badge for an ambassador with the given completed count.
	 *
	 * @param int $done Completed referrals.
	 * @return array{slug:string,label:string,icon:string}|null
	 */
	public static function for_ambassador( $done ) {
		return self::highest( self::ambassador_tiers(), $done );
	}

	/**
	 * Badge for a participant with the given completed count.
	 *
	 * @param int $done Completed chapters.
	 * @return array{slug:string,label:string,icon:string}|null
	 */
	public static function for_participant( $done ) {
		return self::highest( self::participant_tiers(), $done );
	}
}
