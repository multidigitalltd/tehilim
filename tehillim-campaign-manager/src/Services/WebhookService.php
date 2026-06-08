<?php
/**
 * Outbound webhooks.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Contracts\Registerable;
use TCM\PostTypes\CampaignPostType;
use TCM\Support\Hebrew;
use TCM\Support\Logger;
use TCM\Support\Options;
use TCM\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
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
		add_action( 'tcm_chapter_claimed', array( $this, 'on_claimed' ), 20, 4 );
		add_action( 'tcm_chapter_done', array( $this, 'on_done' ), 20, 1 );
		add_action( 'tcm_chapter_released', array( $this, 'on_released' ), 20, 1 );
		add_action( 'tcm_book_completed', array( $this, 'on_book_completed' ), 20, 3 );
		add_action( 'tcm_campaign_completed', array( $this, 'on_campaign_completed' ), 20, 2 );
		add_action( 'tcm_chapter_reminder', array( $this, 'on_reminder' ), 20, 1 );
		add_action( 'tcm_chapter_release_warning', array( $this, 'on_release_warning' ), 20, 1 );
		add_action( 'tcm_chapter_auto_released', array( $this, 'on_auto_released' ), 20, 1 );
		add_action( 'tcm_subscription_due', array( $this, 'on_subscription_due' ), 20, 2 );
		add_action( 'transition_post_status', array( $this, 'maybe_announce_campaign' ), 10, 3 );
	}

	/**
	 * Broadcast a `campaign_new` event the first time a campaign is published,
	 * so an automation can post every new campaign to a WhatsApp group/channel.
	 * Fires once per campaign (guarded by a meta flag) and only on the
	 * not-published → published transition.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post.
	 * @return void
	 */
	public function maybe_announce_campaign( $new_status, $old_status, $post ) {
		if ( ! $post instanceof \WP_Post || CampaignPostType::POST_TYPE !== $post->post_type ) {
			return;
		}
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		if ( get_post_meta( $post->ID, '_tcm_announced', true ) ) {
			return;
		}
		update_post_meta( $post->ID, '_tcm_announced', 1 );
		$this->dispatch(
			'campaign_new',
			array(
				'campaign_id'    => (int) $post->ID,
				'campaign_title' => get_the_title( $post ),
				'dedicated_to'   => (string) get_post_meta( $post->ID, '_tcm_dedicated_to', true ),
				'target_books'   => (int) get_post_meta( $post->ID, '_tcm_target_books', true ),
				'permalink'      => (string) get_permalink( $post ),
			)
		);
	}

	/**
	 * Daily subscription content — sent as a webhook for WhatsApp/automation.
	 *
	 * @param object              $subscriber Subscriber row.
	 * @param array<string,mixed> $payload    Content payload (chapter, etc.).
	 * @return void
	 */
	public function on_subscription_due( $subscriber, $payload ) {
		$payload['channel']          = (string) $subscriber->channel;
		$payload['subscriber_name']  = (string) $subscriber->name;
		$payload['subscriber_phone'] = (string) $subscriber->phone;
		$payload['subscriber_email'] = (string) $subscriber->email;
		$this->dispatch( 'subscription_daily', $payload );
	}

	/**
	 * Reminder due — sent as a webhook for WhatsApp/automation routing.
	 *
	 * @param object $row Assignment row (carries a token).
	 * @return void
	 */
	public function on_reminder( $row ) {
		$this->dispatch( 'chapter_reminder', $this->contact_payload( $row ) );
	}

	/**
	 * Release warning due.
	 *
	 * @param object $row Assignment row.
	 * @return void
	 */
	public function on_release_warning( $row ) {
		$this->dispatch( 'chapter_release_warning', $this->contact_payload( $row ) );
	}

	/**
	 * Chapter auto-released after no response.
	 *
	 * @param object $row Assignment row.
	 * @return void
	 */
	public function on_auto_released( $row ) {
		$this->dispatch( 'chapter_auto_released', $this->contact_payload( $row ) );
	}

	/**
	 * Payload enriched with contact details and the reader URL, so an external
	 * automation can deliver the message (e.g. via WhatsApp).
	 *
	 * @param object $row Assignment row.
	 * @return array<string,mixed>
	 */
	private function contact_payload( $row ) {
		$payload                      = $this->row_payload( $row );
		$payload['participant_name']  = (string) $row->participant_name;
		$payload['participant_phone'] = (string) $row->participant_phone;
		$payload['participant_email'] = (string) $row->participant_email;
		$payload['reminder_count']    = (int) $row->reminder_count;
		$payload['read_url']          = Urls::read( (int) $row->campaign_id, (int) $row->id, (string) $row->token );
		return $payload;
	}

	/**
	 * @param int                  $campaign_id Campaign.
	 * @param array<int,object>    $rows        Claimed rows.
	 * @param string               $token       Token (not exported).
	 * @param array<string,string> $participant Participant.
	 * @return void
	 */
	public function on_claimed( $campaign_id, $rows, $token, $participant ) {
		$first = ! empty( $rows ) ? $rows[0] : null;
		$this->dispatch(
			'chapter_taken',
			array(
				'campaign_id'       => (int) $campaign_id,
				'campaign_title'    => get_the_title( $campaign_id ),
				'count'             => count( (array) $rows ),
				'chapters'          => array_map(
					static function ( $r ) {
						return (int) $r->chapter_number;
					},
					(array) $rows
				),
				'chapter_number'    => $first ? (int) $first->chapter_number : 0,
				'participant_name'  => isset( $participant['name'] ) ? $participant['name'] : '',
				'participant_email' => isset( $participant['email'] ) ? $participant['email'] : '',
				'permalink'         => get_permalink( $campaign_id ),
			)
		);
	}

	/**
	 * @param object $row Assignment row.
	 * @return void
	 */
	public function on_done( $row ) {
		$this->dispatch( 'chapter_done', $this->row_payload( $row ) );
	}

	/**
	 * @param object $row Assignment row.
	 * @return void
	 */
	public function on_released( $row ) {
		$this->dispatch( 'chapter_released', $this->row_payload( $row ) );
	}

	/**
	 * @param int   $campaign_id Campaign.
	 * @param int   $round       Round.
	 * @param array $stats       Stats.
	 * @return void
	 */
	public function on_book_completed( $campaign_id, $round, $stats ) {
		$this->dispatch(
			'book_completed',
			array(
				'campaign_id'     => (int) $campaign_id,
				'campaign_title'  => get_the_title( $campaign_id ),
				'round_number'    => (int) $round,
				'completed_books' => isset( $stats['completed_books'] ) ? (int) $stats['completed_books'] : 0,
				'permalink'       => get_permalink( $campaign_id ),
			)
		);
	}

	/**
	 * @param int   $campaign_id Campaign.
	 * @param array $stats       Stats.
	 * @return void
	 */
	public function on_campaign_completed( $campaign_id, $stats ) {
		$this->dispatch(
			'campaign_completed',
			array(
				'campaign_id'    => (int) $campaign_id,
				'campaign_title' => get_the_title( $campaign_id ),
				'permalink'      => get_permalink( $campaign_id ),
			)
		);
	}

	/**
	 * Build a payload from an assignment row.
	 *
	 * @param object $row Row.
	 * @return array<string,mixed>
	 */
	private function row_payload( $row ) {
		return array(
			'campaign_id'    => (int) $row->campaign_id,
			'campaign_title' => get_the_title( (int) $row->campaign_id ),
			'assignment_id'  => (int) $row->id,
			'round_number'   => (int) $row->round_number,
			'chapter_number' => (int) $row->chapter_number,
			'chapter_label'  => Hebrew::chapter_label( (int) $row->chapter_number ),
			'permalink'      => get_permalink( (int) $row->campaign_id ),
		);
	}

	/**
	 * HMAC-SHA256 signature of a body (pure; unit-tested).
	 *
	 * @param string $body   JSON body.
	 * @param string $secret Shared secret.
	 * @return string
	 */
	public static function sign( $body, $secret ) {
		return hash_hmac( 'sha256', (string) $body, (string) $secret );
	}

	/**
	 * Basic SSRF guard: only allow http(s) to a public host. Blocks localhost
	 * and private/reserved IP ranges (after DNS resolution). Can be overridden
	 * for trusted internal endpoints via the `tcm_webhook_allow_private_hosts`
	 * filter.
	 *
	 * @param string $url Destination URL.
	 * @return bool
	 */
	public static function is_allowed_url( $url ) {
		if ( apply_filters( 'tcm_webhook_allow_private_hosts', false, $url ) ) {
			return true;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( strtolower( (string) $scheme ), array( 'http', 'https' ), true ) ) {
			return false;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$host = strtolower( $host );
		if ( 'localhost' === $host || '.localhost' === substr( $host, -10 ) ) {
			return false;
		}

		$ip = filter_var( $host, FILTER_VALIDATE_IP ) ? $host : gethostbyname( $host );
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false; // Unresolvable host — refuse rather than guess.
		}

		// Reject private and reserved ranges (e.g. 127.0.0.1, 10.x, 192.168.x, ::1).
		return (bool) filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	/**
	 * Send an event to the configured webhook, with bounded retry.
	 *
	 * @param string              $event   Event name.
	 * @param array<string,mixed> $payload Payload.
	 * @return void
	 */
	private function dispatch( $event, array $payload ) {
		$url = (string) Options::get( 'webhook_url' );
		if ( ! $url ) {
			return;
		}
		if ( ! self::is_allowed_url( $url ) ) {
			Logger::log( Logger::WARN, 'webhook_blocked_ssrf', array( 'event' => $event ) );
			return;
		}

		$payload['event']    = $event;
		$payload['site_url'] = home_url();
		$payload['sent_at']  = current_time( 'mysql' );

		$body      = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );
		$timestamp = (string) time();
		$secret    = (string) Options::get( 'webhook_secret' );

		$headers = array( 'Content-Type' => 'application/json' );
		if ( $secret ) {
			$headers['X-TCM-Timestamp'] = $timestamp;
			$headers['X-TCM-Signature'] = self::sign( $timestamp . '.' . $body, $secret );
		}

		for ( $attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++ ) {
			$response = wp_remote_post(
				$url,
				array(
					'timeout' => 12,
					'headers' => $headers,
					'body'    => $body,
				)
			);
			if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) < 500 ) {
				return;
			}
			if ( $attempt < self::MAX_ATTEMPTS ) {
				sleep( $attempt );
			}
		}

		Logger::log(
			Logger::WARN,
			'webhook_failed',
			array(
				'event'    => $event,
				'attempts' => self::MAX_ATTEMPTS,
			)
		);
	}
}
