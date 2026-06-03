<?php
/**
 * Assignment lifecycle service.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Database\AssignmentsRepository;
use TCM\Support\Logger;
use TCM\Support\Tokens;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Per-participant assignment actions: mark a chapter done, take another, or
 * release one. Each action is authorised by the per-assignment token and
 * scopes every query to that token (the RLS-equivalent here).
 */
final class AssignmentService {

    /**
     * @var AssignmentsRepository
     */
    private $assignments;

    /**
     * @var CampaignService
     */
    private $campaigns;

    /**
     * @param AssignmentsRepository|null $assignments Repository.
     * @param CampaignService|null       $campaigns   Campaign service.
     */
    public function __construct($assignments = null, $campaigns = null) {
        $this->assignments = $assignments ? $assignments : new AssignmentsRepository();
        $this->campaigns   = $campaigns ? $campaigns : new CampaignService($this->assignments);
    }

    /**
     * Mark a chapter as completed and report the next one in the same claim.
     *
     * @param int    $assignment_id Assignment id.
     * @param string $token         Access token.
     * @return array{ok:bool,code?:string,campaign_id?:int,next?:array|null}
     */
    public function mark_done($assignment_id, $token) {
        $row = $this->authorize($assignment_id, $token);
        if (!$row) {
            return array('ok' => false, 'code' => 'invalid_link');
        }

        if ('done' !== $row->status) {
            $this->assignments->mark_done((int) $row->id);
            $this->campaigns->after_chapter_done((int) $row->campaign_id, (int) $row->round_number);
            Logger::log(Logger::INFO, 'chapter_done', array('campaign_id' => (int) $row->campaign_id, 'assignment_id' => (int) $row->id));
            /** Fires after a chapter is marked done. */
            do_action('tcm_chapter_done', $row);
        }

        $next = $this->assignments->next_taken_in_claim(
            (int) $row->campaign_id,
            (int) $row->round_number,
            $token
        );

        return array(
            'ok'          => true,
            'campaign_id' => (int) $row->campaign_id,
            'next'        => $next ? array('assignment_id' => (int) $next->id, 'token' => $token) : null,
        );
    }

    /**
     * Mark the current chapter done and claim a fresh one for the same person.
     *
     * @param int    $assignment_id Assignment id.
     * @param string $token         Access token.
     * @return array{ok:bool,code?:string,campaign_id?:int,full?:bool,assignment_id?:int,token?:string}
     */
    public function take_more($assignment_id, $token) {
        $row = $this->authorize($assignment_id, $token);
        if (!$row) {
            return array('ok' => false, 'code' => 'invalid_link');
        }

        if ('done' !== $row->status) {
            $this->assignments->mark_done((int) $row->id);
            $this->campaigns->after_chapter_done((int) $row->campaign_id, (int) $row->round_number);
            do_action('tcm_chapter_done', $row);
        }

        $new_token   = Tokens::generate();
        $participant = array(
            'name'  => (string) $row->participant_name,
            'email' => (string) $row->participant_email,
            'phone' => (string) $row->participant_phone,
        );

        $claimed = $this->campaigns->claim_chapters((int) $row->campaign_id, 1, $participant, $new_token);
        if (!$claimed) {
            return array('ok' => true, 'campaign_id' => (int) $row->campaign_id, 'full' => true);
        }

        $new = $claimed[0];
        return array(
            'ok'            => true,
            'campaign_id'   => (int) $row->campaign_id,
            'assignment_id' => (int) $new->id,
            'token'         => $new_token,
        );
    }

    /**
     * Release a still-taken chapter back to the pool.
     *
     * @param int    $assignment_id Assignment id.
     * @param string $token         Access token.
     * @return array{ok:bool,code?:string,campaign_id?:int}
     */
    public function release($assignment_id, $token) {
        $row = $this->authorize($assignment_id, $token);
        if (!$row || 'taken' !== $row->status) {
            return array('ok' => false, 'code' => 'invalid_link');
        }

        $this->assignments->release((int) $row->id);
        \TCM\Services\StatsService::flush((int) $row->campaign_id);
        Logger::log(Logger::INFO, 'chapter_released', array('campaign_id' => (int) $row->campaign_id, 'assignment_id' => (int) $row->id));
        /** Fires after a chapter is released by its participant. */
        do_action('tcm_chapter_released', $row);

        return array('ok' => true, 'campaign_id' => (int) $row->campaign_id);
    }

    /**
     * Look up a row and verify the token in constant time.
     *
     * @param int    $assignment_id Assignment id.
     * @param string $token         Access token.
     * @return object|null
     */
    private function authorize($assignment_id, $token) {
        $assignment_id = absint($assignment_id);
        if (!$assignment_id || !is_string($token) || '' === $token) {
            return null;
        }
        $row = $this->assignments->find($assignment_id);
        if (!$row || !Tokens::verify((string) $row->token, $token)) {
            return null;
        }
        return $row;
    }
}
