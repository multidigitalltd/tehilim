<?php
/**
 * Subscribers repository.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access for tcm_subscribers (opt-in lists, e.g. the daily chapter).
 */
final class SubscribersRepository extends Repository {

	/**
	 * {@inheritDoc}
	 */
	protected function table_suffix() {
		return 'tcm_subscribers';
	}

	/**
	 * Create a subscriber and return the row.
	 *
	 * @param array<string,mixed> $data Row data.
	 * @return object|null
	 */
	public function create( array $data ) {
		$now = current_time( 'mysql' );
		$this->db->insert(
			$this->table,
			array(
				'list_key'          => sanitize_key( $data['list_key'] ),
				'name'              => sanitize_text_field( $data['name'] ?? '' ),
				'email'             => sanitize_email( $data['email'] ?? '' ),
				'phone'             => sanitize_text_field( $data['phone'] ?? '' ),
				'channel'           => sanitize_key( $data['channel'] ?? 'email' ),
				'status'            => sanitize_key( $data['status'] ?? 'active' ),
				'consent_at'        => ! empty( $data['consent'] ) ? $now : null,
				'unsubscribe_token' => $data['token'],
				'created_at'        => $now,
				'updated_at'        => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		$sql = $this->db->prepare( "SELECT * FROM {$this->table} WHERE id=%d", $this->db->insert_id );
		return $this->db->get_row( $sql );
	}

	/**
	 * Find by unsubscribe token.
	 *
	 * @param string $token Token.
	 * @return object|null
	 */
	public function find_by_token( $token ) {
		$sql = $this->db->prepare( "SELECT * FROM {$this->table} WHERE unsubscribe_token=%s", $token );
		return $this->db->get_row( $sql );
	}

	/**
	 * Find an existing subscriber (to avoid duplicates).
	 *
	 * @param string $list  List key.
	 * @param string $email Email.
	 * @param string $phone Phone.
	 * @return object|null
	 */
	public function find_existing( $list, $email, $phone ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table}
             WHERE list_key=%s AND ((email<>'' AND email=%s) OR (phone<>'' AND phone=%s))
             LIMIT 1",
			$list,
			$email,
			$phone
		);
		return $this->db->get_row( $sql );
	}

	/**
	 * Set a subscriber's status.
	 *
	 * @param int    $id     Subscriber id.
	 * @param string $status Status.
	 * @return void
	 */
	public function set_status( $id, $status ) {
		$this->db->update(
			$this->table,
			array(
				'status'     => sanitize_key( $status ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Active subscribers of a list that have not been sent today.
	 *
	 * @param string $list  List key.
	 * @param string $today Y-m-d.
	 * @param int    $limit Batch size.
	 * @return array<int,object>
	 */
	public function due_for_daily( $list, $today, $limit = 200 ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table}
             WHERE list_key=%s AND status='active'
               AND (last_sent_at IS NULL OR DATE(last_sent_at) < %s)
             ORDER BY id ASC LIMIT %d",
			$list,
			$today,
			max( 1, (int) $limit )
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * All active subscribers of a list (for one-off broadcasts like campaign
	 * alerts). Not date-gated, unlike the daily digest.
	 *
	 * @param string $list  List key.
	 * @param int    $limit Batch size.
	 * @return array<int,object>
	 */
	public function active_by_list( $list, $limit = 500 ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table}
             WHERE list_key=%s AND status='active'
             ORDER BY id ASC LIMIT %d",
			$list,
			max( 1, (int) $limit )
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * All subscribers for an email (privacy export).
	 *
	 * @param string $email Email.
	 * @return array<int,object>
	 */
	public function all_by_email( $email ) {
		$sql = $this->db->prepare( "SELECT * FROM {$this->table} WHERE email=%s", $email );
		return $this->db->get_results( $sql );
	}

	/**
	 * Delete every subscriber row for an email (privacy erasure).
	 *
	 * @param string $email Email.
	 * @return int Rows deleted.
	 */
	public function delete_by_email( $email ) {
		return (int) $this->db->delete( $this->table, array( 'email' => $email ), array( '%s' ) );
	}

	/**
	 * Count active subscribers, optionally for one list.
	 *
	 * @param string $list Optional list slug; empty for all lists.
	 * @return int
	 */
	public function count_active( $list = '' ) {
		if ( '' !== $list ) {
			$sql = $this->db->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE status='active' AND list_key=%s",
				$list
			);
			return (int) $this->db->get_var( $sql );
		}
		return (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE status='active'" );
	}

	/**
	 * Mark a subscriber as sent now.
	 *
	 * @param int $id Subscriber id.
	 * @return void
	 */
	public function mark_sent( $id ) {
		$now = current_time( 'mysql' );
		$this->db->update(
			$this->table,
			array(
				'last_sent_at' => $now,
				'updated_at'   => $now,
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}
}
