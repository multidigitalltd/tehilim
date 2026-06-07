<?php
/**
 * Ambassadors repository.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access for tcm_ambassadors (personal sharing codes per campaign/user).
 */
final class AmbassadorsRepository extends Repository {

	/**
	 * {@inheritDoc}
	 */
	protected function table_suffix() {
		return 'tcm_ambassadors';
	}

	/**
	 * Find by sharing code.
	 *
	 * @param string $code Code.
	 * @return object|null
	 */
	public function find_by_code( $code ) {
		$sql = $this->db->prepare( "SELECT * FROM {$this->table} WHERE code=%s", $code );
		return $this->db->get_row( $sql );
	}

	/**
	 * Find a code within a campaign.
	 *
	 * @param string $code        Code.
	 * @param int    $campaign_id Campaign.
	 * @return object|null
	 */
	public function find_by_code_in_campaign( $code, $campaign_id ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table} WHERE code=%s AND campaign_id=%d",
			$code,
			$campaign_id
		);
		return $this->db->get_row( $sql );
	}

	/**
	 * Find an existing ambassador for a campaign + user.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $user_id     User.
	 * @return object|null
	 */
	public function find_for_user( $campaign_id, $user_id ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table} WHERE campaign_id=%d AND user_id=%d LIMIT 1",
			$campaign_id,
			$user_id
		);
		return $this->db->get_row( $sql );
	}

	/**
	 * All ambassador rows for a user (optionally scoped to a campaign).
	 *
	 * @param int $user_id     User.
	 * @param int $campaign_id Campaign (0 = all).
	 * @return array<int,object>
	 */
	public function for_user( $user_id, $campaign_id = 0 ) {
		if ( $campaign_id ) {
			$sql = $this->db->prepare(
				"SELECT * FROM {$this->table} WHERE user_id=%d AND campaign_id=%d ORDER BY created_at DESC",
				$user_id,
				$campaign_id
			);
		} else {
			$sql = $this->db->prepare(
				"SELECT * FROM {$this->table} WHERE user_id=%d ORDER BY created_at DESC",
				$user_id
			);
		}
		return $this->db->get_results( $sql );
	}

	/**
	 * Whether a code is already taken.
	 *
	 * @param string $code Code.
	 * @return bool
	 */
	public function code_exists( $code ) {
		$sql = $this->db->prepare( "SELECT id FROM {$this->table} WHERE code=%s", $code );
		return (bool) $this->db->get_var( $sql );
	}

	/**
	 * Number of ambassadors registered for a campaign.
	 *
	 * @param int $campaign_id Campaign.
	 * @return int
	 */
	public function count_for_campaign( $campaign_id ) {
		$sql = $this->db->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE campaign_id=%d", (int) $campaign_id );
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Insert an ambassador and return the new row.
	 *
	 * @param array<string,mixed> $data Row data.
	 * @return object|null
	 */
	public function create( array $data ) {
		$now = current_time( 'mysql' );
		$this->db->insert(
			$this->table,
			array(
				'campaign_id'      => (int) $data['campaign_id'],
				'user_id'          => (int) $data['user_id'],
				'ambassador_name'  => sanitize_text_field( $data['name'] ?? '' ),
				'ambassador_email' => sanitize_email( $data['email'] ?? '' ),
				'code'             => $data['code'],
				'goal_chapters'    => max( 1, (int) ( $data['goal_chapters'] ?? 10 ) ),
				'created_at'       => $now,
				'updated_at'       => $now,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		$sql = $this->db->prepare( "SELECT * FROM {$this->table} WHERE id=%d", $this->db->insert_id );
		return $this->db->get_row( $sql );
	}
}
