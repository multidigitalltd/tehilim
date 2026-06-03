<?php
/**
 * Outbound webhooks.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Contracts\Registerable;
use TCM\Support\Hebrew;
use TCM\Support\Logger;
use TCM\Support\Options;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Posts domain events to a configured webhook URL, signed with an HMAC over the
 * request body + timestamp (defends against tampering and replay), with a small
 * bounded retry. The body is never trusted by the receiver without the
 * signature.
 */
final class WebhookService implements Registerable {

    const MAX_ATTEMPTS = 3;

    /**
     * {@inheritDoc}
     */
    public function register() {
        add_action('tcm_chapter_claimed', array($this, 'on_claimed'), 20, 4);
        add_action('tcm_chapter_done', array($this, 'on_done'), 20, 1);
        add_action('tcm_chapter_released', array($this, 'on_released'), 20, 1);
        add_action('tcm_book_completed', array($this, 'on_book_completed'), 20, 3);
        add_action('tcm_campaign_completed', array($this, 'on_campaign_completed'), 20, 2);
    }

    /**
     * @param int                  $campaign_id Campaign.
     * @param array<int,object>    $rows        Claimed rows.
     * @param string               $token       Token (not exported).
     * @param array<string,string> $participant Participant.
     * @return void
     */
    public function on_claimed($campaign_id, $rows, $token, $participant) {
        $first = !empty($rows) ? $rows[0] : null;
        $this->dispatch(
            'chapter_taken',
            array(
                'campaign_id'    => (int) $campaign_id,
                'campaign_title' => get_the_title($campaign_id),
                'count'          => count((array) $rows),
                'chapters'       => array_map(static function ($r) {
                    return (int) $r->chapter_number;
                }, (array) $rows),
                'chapter_number' => $first ? (int) $first->chapter_number : 0,
                'participant_name' => isset($participant['name']) ? $participant['name'] : '',
                'participant_email' => isset($participant['email']) ? $participant['email'] : '',
                'permalink'      => get_permalink($campaign_id),
            )
        );
    }

    /**
     * @param object $row Assignment row.
     * @return void
     */
    public function on_done($row) {
        $this->dispatch('chapter_done', $this->row_payload($row));
    }

    /**
     * @param object $row Assignment row.
     * @return void
     */
    public function on_released($row) {
        $this->dispatch('chapter_released', $this->row_payload($row));
    }

    /**
     * @param int   $campaign_id Campaign.
     * @param int   $round       Round.
     * @param array $stats       Stats.
     * @return void
     */
    public function on_book_completed($campaign_id, $round, $stats) {
        $this->dispatch(
            'book_completed',
            array(
                'campaign_id'     => (int) $campaign_id,
                'campaign_title'  => get_the_title($campaign_id),
                'round_number'    => (int) $round,
                'completed_books' => isset($stats['completed_books']) ? (int) $stats['completed_books'] : 0,
                'permalink'       => get_permalink($campaign_id),
            )
        );
    }

    /**
     * @param int   $campaign_id Campaign.
     * @param array $stats       Stats.
     * @return void
     */
    public function on_campaign_completed($campaign_id, $stats) {
        $this->dispatch(
            'campaign_completed',
            array(
                'campaign_id'    => (int) $campaign_id,
                'campaign_title' => get_the_title($campaign_id),
                'permalink'      => get_permalink($campaign_id),
            )
        );
    }

    /**
     * Build a payload from an assignment row.
     *
     * @param object $row Row.
     * @return array<string,mixed>
     */
    private function row_payload($row) {
        return array(
            'campaign_id'    => (int) $row->campaign_id,
            'campaign_title' => get_the_title((int) $row->campaign_id),
            'assignment_id'  => (int) $row->id,
            'round_number'   => (int) $row->round_number,
            'chapter_number' => (int) $row->chapter_number,
            'chapter_label'  => Hebrew::chapter_label((int) $row->chapter_number),
            'permalink'      => get_permalink((int) $row->campaign_id),
        );
    }

    /**
     * HMAC-SHA256 signature of a body (pure; unit-tested).
     *
     * @param string $body   JSON body.
     * @param string $secret Shared secret.
     * @return string
     */
    public static function sign($body, $secret) {
        return hash_hmac('sha256', (string) $body, (string) $secret);
    }

    /**
     * Send an event to the configured webhook, with bounded retry.
     *
     * @param string              $event   Event name.
     * @param array<string,mixed> $payload Payload.
     * @return void
     */
    private function dispatch($event, array $payload) {
        $url = (string) Options::get('webhook_url');
        if (!$url) {
            return;
        }

        $payload['event']    = $event;
        $payload['site_url'] = home_url();
        $payload['sent_at']  = current_time('mysql');

        $body      = wp_json_encode($payload, JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $secret    = (string) Options::get('webhook_secret');

        $headers = array('Content-Type' => 'application/json');
        if ($secret) {
            $headers['X-TCM-Timestamp'] = $timestamp;
            $headers['X-TCM-Signature'] = self::sign($timestamp . '.' . $body, $secret);
        }

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $response = wp_remote_post(
                $url,
                array('timeout' => 12, 'headers' => $headers, 'body' => $body)
            );
            if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) < 500) {
                return;
            }
            if ($attempt < self::MAX_ATTEMPTS) {
                sleep($attempt);
            }
        }

        Logger::log(Logger::WARN, 'webhook_failed', array('event' => $event, 'attempts' => self::MAX_ATTEMPTS));
    }
}
