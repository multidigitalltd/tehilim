<?php
/**
 * Logs repository.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persists audit events to the tcm_logs table.
 */
final class LogsRepository extends Repository {

	/**
	 * {@inheritDoc}
	 */
	protected function table_suffix() {
		return 'tcm_logs';
	}

	/**
	 * Record an event row.
	 *
	 * @param string              $event   Event key.
	 * @param array<string,mixed> $context Context (campaign_id/assignment_id are
	 *                                     promoted to columns; the rest is JSON).
	 * @return void
	 */
	public function record( $event, array $context = array() ) {
		$campaign_id   = isset( $context['campaign_id'] ) ? absint( $context['campaign_id'] ) : 0;
		$assignment_id = isset( $context['assignment_id'] ) ? absint( $context['assignment_id'] ) : 0;

		// Enforce redaction here too, so there is no uncensored path to the DB
		// regardless of the caller (PII/secrets never land in tcm_logs).
		$context = \TCM\Support\Logger::redact( $context );

		$this->db->insert(
			$this->table,
			array(
				'event'         => sanitize_key( $event ),
				'campaign_id'   => $campaign_id,
				'assignment_id' => $assignment_id,
				'user_id'       => get_current_user_id(),
				'ip'            => $this->client_ip(),
				'data'          => wp_json_encode( $context, JSON_UNESCAPED_UNICODE ),
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Most-recent events, newest first.
	 *
	 * @param int $limit  Page size.
	 * @param int $offset Offset.
	 * @return array<int,object>
	 */
	public function latest( $limit = 50, $offset = 0 ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table} ORDER BY id DESC LIMIT %d OFFSET %d",
			max( 1, (int) $limit ),
			max( 0, (int) $offset )
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * Delete rows older than N days (retention).
	 *
	 * @param int $days Age threshold in days.
	 * @return int Rows deleted.
	 */
	public function purge_older_than( $days ) {
		$sql = $this->db->prepare(
			"DELETE FROM {$this->table} WHERE created_at < (NOW() - INTERVAL %d DAY)",
			max( 1, (int) $days )
		);
		return (int) $this->db->query( $sql );
	}

	/**
	 * Best-effort client IP.
	 *
	 * @return string
	 */
	private function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
		return sanitize_text_field( $ip );
	}
}
