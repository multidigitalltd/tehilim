<?php
/**
 * Ambassador service.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Database\AmbassadorsRepository;
use TCM\Database\ReferralsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages personal sharing codes and referral attribution.
 */
final class AmbassadorService {

	const CHAPTERS_PER_BOOK = 150;

	/**
	 * @var AmbassadorsRepository
	 */
	private $ambassadors;

	/**
	 * @var ReferralsRepository
	 */
	private $referrals;

	/**
	 * @param AmbassadorsRepository|null $ambassadors Repository.
	 * @param ReferralsRepository|null   $referrals   Repository.
	 */
	public function __construct( $ambassadors = null, $referrals = null ) {
		$this->ambassadors = $ambassadors ? $ambassadors : new AmbassadorsRepository();
		$this->referrals   = $referrals ? $referrals : new ReferralsRepository();
	}

	/**
	 * Get (or create) the current user's ambassador record for a campaign.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $user_id     User.
	 * @return object|null
	 */
	public function get_or_create( $campaign_id, $user_id ) {
		$campaign_id = (int) $campaign_id;
		$user_id     = (int) $user_id;
		if ( ! $campaign_id || ! $user_id ) {
			return null;
		}

		$existing = $this->ambassadors->find_for_user( $campaign_id, $user_id );
		if ( $existing ) {
			return $existing;
		}

		$user = get_user_by( 'id', $user_id );
		do {
			$code = 'a' . wp_generate_password( 10, false, false );
		} while ( $this->ambassadors->code_exists( $code ) );

		return $this->ambassadors->create(
			array(
				'campaign_id' => $campaign_id,
				'user_id'     => $user_id,
				'name'        => $user ? ( $user->display_name ? $user->display_name : $user->user_login ) : '',
				'email'       => $user ? $user->user_email : '',
				'code'        => $code,
			)
		);
	}

	/**
	 * Record a referral for a claimed assignment, based on the ref cookie.
	 *
	 * @param int    $campaign_id   Campaign.
	 * @param int    $assignment_id Assignment.
	 * @param string $email         Participant email.
	 * @return void
	 */
	public function record_from_cookie( $campaign_id, $assignment_id, $email = '' ) {
		if ( empty( $_COOKIE['tcm_ref'] ) ) {
			return;
		}
		$code       = sanitize_key( wp_unslash( $_COOKIE['tcm_ref'] ) );
		$ambassador = $this->ambassadors->find_by_code_in_campaign( $code, (int) $campaign_id );
		if ( ! $ambassador ) {
			return;
		}
		$this->referrals->record( (int) $campaign_id, (int) $ambassador->id, (int) $assignment_id, $email );

		/** Fires when a referral is attributed to an ambassador. */
		do_action( 'tcm_ambassador_referral', (int) $campaign_id, $ambassador, (int) $assignment_id, $email );
	}

	/**
	 * Progress figures for an ambassador.
	 *
	 * @param object $ambassador Ambassador row.
	 * @return array{total:int,done:int,books:int}
	 */
	public function stats( $ambassador ) {
		$total = $this->referrals->count_total( (int) $ambassador->id );
		$done  = $this->referrals->count_done( (int) $ambassador->id );
		return array(
			'total' => $total,
			'done'  => $done,
			'books' => intdiv( $done, self::CHAPTERS_PER_BOOK ),
		);
	}
}
