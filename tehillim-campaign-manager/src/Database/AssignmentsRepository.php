<?php
/**
 * Assignments repository.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data access for tcm_assignments - one row per (campaign, round, chapter).
 *
 * Every method that accepts request-derived values uses prepared statements.
 */
final class AssignmentsRepository extends Repository {

	const TOTAL_CHAPTERS = 150;

	/**
	 * {@inheritDoc}
	 */
	protected function table_suffix() {
		return 'tcm_assignments';
	}

	/**
	 * Whether any rows exist for a round.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $round       Round number.
	 * @return bool
	 */
	public function round_exists( $campaign_id, $round ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table} WHERE campaign_id=%d AND round_number=%d",
			$campaign_id,
			$round
		);
		return (int) $this->db->get_var( $sql ) > 0;
	}

	/**
	 * Bulk-insert the 150 chapters of a round in a single statement.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $round       Round number.
	 * @return void
	 */
	public function insert_round( $campaign_id, $round ) {
		$now          = current_time( 'mysql' );
		$placeholders = array();
		$values       = array();
		for ( $chapter = 1; $chapter <= self::TOTAL_CHAPTERS; $chapter++ ) {
			$placeholders[] = '(%d,%d,%d,%s,%s,%s)';
			array_push( $values, $campaign_id, $round, $chapter, 'free', $now, $now );
		}
		$sql = "INSERT IGNORE INTO {$this->table}
                (campaign_id, round_number, chapter_number, status, created_at, updated_at)
                VALUES " . implode( ',', $placeholders );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->db->query( $this->db->prepare( $sql, $values ) );
	}

	/**
	 * Highest round number for a campaign.
	 *
	 * @param int $campaign_id Campaign.
	 * @return int
	 */
	public function max_round( $campaign_id ) {
		$sql = $this->db->prepare(
			"SELECT MAX(round_number) FROM {$this->table} WHERE campaign_id=%d",
			$campaign_id
		);
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * The first round that is not fully completed (the "current" round).
	 *
	 * @param int $campaign_id Campaign.
	 * @return int 0 if every round is complete.
	 */
	public function first_incomplete_round( $campaign_id ) {
		$sql = $this->db->prepare(
			"SELECT round_number FROM {$this->table}
             WHERE campaign_id=%d
             GROUP BY round_number
             HAVING SUM(CASE WHEN status='done' THEN 1 ELSE 0 END) < %d
             ORDER BY round_number ASC
             LIMIT 1",
			$campaign_id,
			self::TOTAL_CHAPTERS
		);
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Count rows of a given status in a round.
	 *
	 * @param int    $campaign_id Campaign.
	 * @param int    $round       Round.
	 * @param string $status      free|taken|done.
	 * @return int
	 */
	public function count_status( $campaign_id, $round, $status ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table}
             WHERE campaign_id=%d AND round_number=%d AND status=%s",
			$campaign_id,
			$round,
			$status
		);
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Total rows in a round.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $round       Round.
	 * @return int
	 */
	public function count_round( $campaign_id, $round ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table} WHERE campaign_id=%d AND round_number=%d",
			$campaign_id,
			$round
		);
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Completed-chapter counts grouped by day since a datetime, oldest first.
	 * Powers the admin reading-trend chart.
	 *
	 * @param string $since Datetime in MySQL `Y-m-d H:i:s` format.
	 * @return array<int,object> Rows of { day, c }.
	 */
	public function done_by_day( $since ) {
		$sql = $this->db->prepare(
			"SELECT DATE(completed_at) AS day, COUNT(*) AS c
             FROM {$this->table}
             WHERE status='done' AND completed_at >= %s
             GROUP BY DATE(completed_at)
             ORDER BY day ASC",
			$since
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * Total completed chapters across all rounds.
	 *
	 * @param int $campaign_id Campaign.
	 * @return int
	 */
	public function count_total_done( $campaign_id ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table} WHERE campaign_id=%d AND status='done'",
			$campaign_id
		);
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Recent activity in a campaign (taken/done), newest first. No emails.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $limit       Max rows.
	 * @return array<int,\stdClass>
	 */
	public function recent_activity( $campaign_id, $limit = 12 ) {
		$sql = $this->db->prepare(
			"SELECT participant_name, chapter_number, status, taken_at, completed_at, updated_at
			 FROM {$this->table}
			 WHERE campaign_id=%d AND status IN ('taken','done')
			 ORDER BY updated_at DESC
			 LIMIT %d",
			$campaign_id,
			max( 1, (int) $limit )
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * Site-wide totals across all campaigns (for the homepage stats strip).
	 *
	 * @return array{done:int,participants:int}
	 */
	public function global_totals() {
		$done         = (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE status='done'" );
		$participants = (int) $this->db->get_var(
			"SELECT COUNT(DISTINCT COALESCE(NULLIF(participant_email,''), NULLIF(token,''), id))
			 FROM {$this->table} WHERE status IN ('taken','done')"
		);
		return array(
			'done'         => $done,
			'participants' => $participants,
		);
	}

	/**
	 * Distinct participants in a campaign (by email, else token, else row).
	 *
	 * @param int $campaign_id Campaign.
	 * @return int
	 */
	public function participant_count( $campaign_id ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(DISTINCT COALESCE(NULLIF(participant_email,''), NULLIF(token,''), id))
             FROM {$this->table}
             WHERE campaign_id=%d AND status IN ('taken','done')",
			$campaign_id
		);
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Free chapters in a round.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $round       Round.
	 * @param int $limit       Max rows (0 = all).
	 * @return array<int,object>
	 */
	public function free_chapters( $campaign_id, $round, $limit = 0 ) {
		$limit = (int) $limit;
		if ( $limit > 0 ) {
			$sql = $this->db->prepare(
				"SELECT * FROM {$this->table}
                 WHERE campaign_id=%d AND round_number=%d AND status='free'
                 ORDER BY chapter_number ASC LIMIT %d",
				$campaign_id,
				$round,
				$limit
			);
		} else {
			$sql = $this->db->prepare(
				"SELECT * FROM {$this->table}
                 WHERE campaign_id=%d AND round_number=%d AND status='free'
                 ORDER BY chapter_number ASC",
				$campaign_id,
				$round
			);
		}
		return $this->db->get_results( $sql );
	}

	/**
	 * A single free chapter by number.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $round       Round.
	 * @param int $chapter     Chapter number.
	 * @return object|null
	 */
	public function free_chapter( $campaign_id, $round, $chapter ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table}
             WHERE campaign_id=%d AND round_number=%d AND chapter_number=%d AND status='free'
             LIMIT 1",
			$campaign_id,
			$round,
			$chapter
		);
		return $this->db->get_row( $sql );
	}

	/**
	 * All rows for an email (privacy export).
	 *
	 * @param string $email Email.
	 * @return array<int,object>
	 */
	public function all_by_email( $email ) {
		$sql = $this->db->prepare( "SELECT * FROM {$this->table} WHERE participant_email=%s", $email );
		return $this->db->get_results( $sql );
	}

	/**
	 * Strip personal data from every row of an email (privacy erasure), keeping
	 * the chapter/merit record intact but anonymous.
	 *
	 * @param string $email Email.
	 * @return int Rows affected.
	 */
	public function anonymize_by_email( $email ) {
		return (int) $this->db->query(
			$this->db->prepare(
				"UPDATE {$this->table}
                 SET participant_name=NULL, participant_email=NULL, participant_phone=NULL, updated_at=%s
                 WHERE participant_email=%s",
				current_time( 'mysql' ),
				$email
			)
		);
	}

	/**
	 * All participant rows for a campaign (CSV export).
	 *
	 * @param int $campaign_id Campaign.
	 * @return array<int,object>
	 */
	public function participants_for_campaign( $campaign_id ) {
		$sql = $this->db->prepare(
			"SELECT participant_name, participant_email, participant_phone, chapter_number, round_number, status, taken_at, completed_at
             FROM {$this->table}
             WHERE campaign_id=%d AND status IN ('taken','done')
             ORDER BY round_number ASC, chapter_number ASC",
			$campaign_id
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * Recent activity for a participant email.
	 *
	 * @param string $email Email.
	 * @param int    $limit Max rows.
	 * @return array<int,object>
	 */
	public function by_participant_email( $email, $limit = 50 ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table}
             WHERE participant_email=%s
             ORDER BY updated_at DESC LIMIT %d",
			$email,
			max( 1, (int) $limit )
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * Count chapters completed since a given datetime, across all campaigns.
	 * Used for the admin analytics trend (e.g. last 30 days).
	 *
	 * @param string $since Datetime in MySQL `Y-m-d H:i:s` format.
	 * @return int
	 */
	public function count_done_since( $since ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table}
             WHERE status='done' AND completed_at >= %s",
			$since
		);
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Count chapters a participant has personally completed, across all
	 * campaigns. Used to award participant achievement badges.
	 *
	 * @param string $email Participant email.
	 * @return int
	 */
	public function count_done_by_participant_email( $email ) {
		$sql = $this->db->prepare(
			"SELECT COUNT(*) FROM {$this->table}
             WHERE participant_email=%s AND status='done'",
			$email
		);
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Fetch a row by id.
	 *
	 * @param int $id Row id.
	 * @return object|null
	 */
	public function find( $id ) {
		$sql = $this->db->prepare( "SELECT * FROM {$this->table} WHERE id=%d", $id );
		return $this->db->get_row( $sql );
	}

	/**
	 * Fetch a row by id + token (authorization).
	 *
	 * @param int    $id    Row id.
	 * @param string $token Per-assignment token.
	 * @return object|null
	 */
	public function find_by_token( $id, $token ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table} WHERE id=%d AND token=%s",
			$id,
			$token
		);
		return $this->db->get_row( $sql );
	}

	/**
	 * All rows in a round, ordered by chapter number.
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $round       Round.
	 * @return array<int,object>
	 */
	public function round_rows( $campaign_id, $round ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table}
             WHERE campaign_id=%d AND round_number=%d
             ORDER BY chapter_number ASC",
			$campaign_id,
			$round
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * All chapters belonging to one claim (same round + token), ordered.
	 *
	 * @param int    $campaign_id Campaign.
	 * @param int    $round       Round.
	 * @param string $token       Claim token.
	 * @return array<int,object>
	 */
	public function claim_siblings( $campaign_id, $round, $token ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table}
             WHERE campaign_id=%d AND round_number=%d AND token=%s
             ORDER BY chapter_number ASC",
			$campaign_id,
			$round,
			$token
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * The next still-taken chapter in the same claim (same round + token).
	 *
	 * @param int    $campaign_id Campaign.
	 * @param int    $round       Round.
	 * @param string $token       Claim token.
	 * @return object|null
	 */
	public function next_taken_in_claim( $campaign_id, $round, $token ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table}
             WHERE campaign_id=%d AND round_number=%d AND token=%s AND status='taken'
             ORDER BY chapter_number ASC LIMIT 1",
			$campaign_id,
			$round,
			$token
		);
		return $this->db->get_row( $sql );
	}

	/**
	 * Atomically claim a single free row. Returns rows affected (1 = success).
	 *
	 * @param int                  $id          Row id.
	 * @param array<string,string> $participant name/email/phone.
	 * @param string               $token       Token.
	 * @return int
	 */
	public function claim( $id, array $participant, $token ) {
		$now = current_time( 'mysql' );
		return (int) $this->db->query(
			$this->db->prepare(
				"UPDATE {$this->table}
                 SET status='taken', participant_name=%s, participant_email=%s,
                     participant_phone=%s, token=%s, taken_at=%s, completed_at=NULL,
                     reminder_count=0, last_reminder_at=NULL, release_notice_at=NULL,
                     released_at=NULL, updated_at=%s
                 WHERE id=%d AND status='free'",
				isset( $participant['name'] ) ? $participant['name'] : '',
				isset( $participant['email'] ) ? $participant['email'] : '',
				isset( $participant['phone'] ) ? $participant['phone'] : '',
				$token,
				$now,
				$now,
				$id
			)
		);
	}

	/**
	 * Mark a row done.
	 *
	 * @param int $id Row id.
	 * @return void
	 */
	public function mark_done( $id ) {
		$now = current_time( 'mysql' );
		$this->db->update(
			$this->table,
			array(
				'status'       => 'done',
				'completed_at' => $now,
				'updated_at'   => $now,
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Release a row back to the pool.
	 *
	 * @param int $id Row id.
	 * @return void
	 */
	public function release( $id ) {
		$now = current_time( 'mysql' );
		$this->db->update(
			$this->table,
			array(
				'status'            => 'free',
				'participant_name'  => null,
				'participant_email' => null,
				'participant_phone' => null,
				'token'             => null,
				'taken_at'          => null,
				'completed_at'      => null,
				'reminder_count'    => 0,
				'last_reminder_at'  => null,
				'release_notice_at' => null,
				'released_at'       => $now,
				'updated_at'        => $now,
			),
			array( 'id' => (int) $id )
		);
	}

	/**
	 * Still-taken rows that carry a token (candidates for reminders / release).
	 *
	 * @param int $limit Max rows per run.
	 * @return array<int,\stdClass>
	 */
	public function due_taken( $limit = 200 ) {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->table}
             WHERE status='taken' AND token IS NOT NULL AND token<>''
             ORDER BY id ASC LIMIT %d",
			max( 1, (int) $limit )
		);
		return $this->db->get_results( $sql );
	}

	/**
	 * Record that a reminder was sent.
	 *
	 * @param int $id    Row id.
	 * @param int $count New reminder count.
	 * @return void
	 */
	public function record_reminder( $id, $count ) {
		$now = current_time( 'mysql' );
		$this->db->update(
			$this->table,
			array(
				'reminder_count'   => (int) $count,
				'last_reminder_at' => $now,
				'updated_at'       => $now,
			),
			array( 'id' => (int) $id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Record that a release warning was sent.
	 *
	 * @param int $id Row id.
	 * @return void
	 */
	public function record_release_notice( $id ) {
		$now = current_time( 'mysql' );
		$this->db->update(
			$this->table,
			array(
				'release_notice_at' => $now,
				'updated_at'        => $now,
			),
			array( 'id' => (int) $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Begin a transaction (InnoDB).
	 *
	 * @return void
	 */
	public function begin() {
		$this->db->query( 'START TRANSACTION' );
	}

	/**
	 * Commit.
	 *
	 * @return void
	 */
	public function commit() {
		$this->db->query( 'COMMIT' );
	}

	/**
	 * Roll back.
	 *
	 * @return void
	 */
	public function rollback() {
		$this->db->query( 'ROLLBACK' );
	}
}
