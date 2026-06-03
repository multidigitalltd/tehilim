<?php
/**
 * Referrals repository.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data access for tcm_referrals — attribution of an assignment to an ambassador.
 */
final class ReferralsRepository extends Repository {

    /**
     * {@inheritDoc}
     */
    protected function table_suffix() {
        return 'tcm_referrals';
    }

    /**
     * Record a referral (ignores duplicates via the unique assignment key).
     *
     * @param int    $campaign_id   Campaign.
     * @param int    $ambassador_id Ambassador.
     * @param int    $assignment_id Assignment.
     * @param string $email         Participant email.
     * @return void
     */
    public function record($campaign_id, $ambassador_id, $assignment_id, $email = '') {
        $this->db->query(
            $this->db->prepare(
                "INSERT IGNORE INTO {$this->table}
                 (campaign_id, ambassador_id, assignment_id, participant_email, created_at)
                 VALUES (%d, %d, %d, %s, %s)",
                $campaign_id,
                $ambassador_id,
                $assignment_id,
                sanitize_email($email),
                current_time('mysql')
            )
        );
    }

    /**
     * Number of chapters referred by an ambassador.
     *
     * @param int $ambassador_id Ambassador.
     * @return int
     */
    public function count_total($ambassador_id) {
        $sql = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE ambassador_id=%d",
            $ambassador_id
        );
        return (int) $this->db->get_var($sql);
    }

    /**
     * Number of referred chapters that are completed.
     *
     * @param int $ambassador_id Ambassador.
     * @return int
     */
    public function count_done($ambassador_id) {
        $assignments = $this->db->prefix . 'tcm_assignments';
        $sql         = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} r
             INNER JOIN {$assignments} a ON a.id = r.assignment_id
             WHERE r.ambassador_id=%d AND a.status='done'",
            $ambassador_id
        );
        return (int) $this->db->get_var($sql);
    }
}
