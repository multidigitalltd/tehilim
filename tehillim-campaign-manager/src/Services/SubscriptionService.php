<?php
/**
 * Subscription service (lists + daily Tehillim).
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Database\SubscribersRepository;
use TCM\Support\Hebrew;
use TCM\Support\Logger;
use TCM\Support\Tokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Opt-in lists with webhook-only daily delivery. Like reminders, the daily
 * content is emitted as a webhook event for WhatsApp/automation routing - this
 * service never sends messages itself.
 */
final class SubscriptionService {

	const CHAPTERS_PER_BOOK = 150;

	/**
	 * Allowed list keys.
	 *
	 * @var string[]
	 */
	private $lists = array( 'daily_chapter', 'campaign_alerts' );

	/**
	 * @var SubscribersRepository
	 */
	private $subscribers;

	/**
	 * @param SubscribersRepository|null $subscribers Repository.
	 */
	public function __construct( $subscribers = null ) {
		$this->subscribers = $subscribers ? $subscribers : new SubscribersRepository();
	}

	/**
	 * Subscribe a person to a list.
	 *
	 * @param string              $list List key.
	 * @param array<string,mixed> $data name/email/phone/channel/consent.
	 * @return array{ok:bool,code?:string}
	 */
	public function subscribe( $list, array $data ) {
		$list = sanitize_key( $list );
		if ( ! in_array( $list, $this->lists, true ) ) {
			return array(
				'ok'   => false,
				'code' => 'bad_list',
			);
		}
		if ( empty( $data['consent'] ) ) {
			return array(
				'ok'   => false,
				'code' => 'consent_required',
			);
		}

		$channel = ( 'whatsapp' === ( $data['channel'] ?? '' ) ) ? 'whatsapp' : 'email';
		$email   = sanitize_email( $data['email'] ?? '' );
		$phone   = sanitize_text_field( $data['phone'] ?? '' );

		if ( 'whatsapp' === $channel && '' === $phone ) {
			return array(
				'ok'   => false,
				'code' => 'phone_required',
			);
		}
		if ( 'email' === $channel && ( '' === $email || ! is_email( $email ) ) ) {
			return array(
				'ok'   => false,
				'code' => 'email_required',
			);
		}

		if ( $this->subscribers->find_existing( $list, $email, $phone ) ) {
			return array( 'ok' => true ); // Idempotent: already subscribed.
		}

		$this->subscribers->create(
			array(
				'list_key' => $list,
				'name'     => $data['name'] ?? '',
				'email'    => $email,
				'phone'    => $phone,
				'channel'  => $channel,
				'status'   => 'active',
				'consent'  => true,
				'token'    => Tokens::generate(),
			)
		);

		Logger::log(
			Logger::INFO,
			'subscriber_added',
			array(
				'list'    => $list,
				'channel' => $channel,
			)
		);
		return array( 'ok' => true );
	}

	/**
	 * Unsubscribe by token.
	 *
	 * @param string $token Unsubscribe token.
	 * @return bool
	 */
	public function unsubscribe( $token ) {
		$row = $this->subscribers->find_by_token( $token );
		if ( ! $row ) {
			return false;
		}
		$this->subscribers->set_status( (int) $row->id, 'unsubscribed' );
		return true;
	}

	/**
	 * Emit today's chapter to each due subscriber as a webhook event.
	 *
	 * @return void
	 */
	public function process_daily() {
		$today   = current_time( 'Y-m-d' );
		$chapter = ( (int) current_time( 'z' ) % self::CHAPTERS_PER_BOOK ) + 1;

		foreach ( $this->subscribers->due_for_daily( 'daily_chapter', $today ) as $subscriber ) {
			$payload = array(
				'list'            => 'daily_chapter',
				'chapter_number'  => $chapter,
				'chapter_label'   => Hebrew::chapter_label( $chapter ),
				'unsubscribe_url' => add_query_arg( 'tcm_unsub', $subscriber->unsubscribe_token, home_url( '/' ) ),
			);

			/** Fires when a subscriber is due their daily content (webhook only). */
			do_action( 'tcm_subscription_due', $subscriber, $payload );
			$this->subscribers->mark_sent( (int) $subscriber->id );
		}
	}

	/**
	 * Notify every "campaign_alerts" subscriber that a new campaign launched,
	 * as a per-subscriber webhook event (for personal WhatsApp/email routing).
	 * Reuses the subscription-due channel so the automation handles delivery.
	 *
	 * @param int $campaign_id Campaign post id.
	 * @return void
	 */
	public function notify_campaign( $campaign_id ) {
		$campaign_id = (int) $campaign_id;
		if ( $campaign_id <= 0 ) {
			return;
		}

		$this->dispatch_to_alerts(
			array(
				'list'           => 'campaign_alerts',
				'kind'           => 'new',
				'campaign_id'    => $campaign_id,
				'campaign_title' => get_the_title( $campaign_id ),
				'dedicated_to'   => (string) get_post_meta( $campaign_id, '_tcm_dedicated_to', true ),
				'permalink'      => (string) get_permalink( $campaign_id ),
			)
		);
	}

	/**
	 * Nudge "campaign_alerts" subscribers that a campaign is almost finished, so
	 * they come and close out the remaining chapters.
	 *
	 * @param int $campaign_id Campaign post id.
	 * @param int $remaining   Chapters left in the current book.
	 * @return void
	 */
	public function notify_campaign_nearly_done( $campaign_id, $remaining ) {
		$campaign_id = (int) $campaign_id;
		if ( $campaign_id <= 0 ) {
			return;
		}

		$this->dispatch_to_alerts(
			array(
				'list'           => 'campaign_alerts',
				'kind'           => 'nearly_done',
				'campaign_id'    => $campaign_id,
				'campaign_title' => get_the_title( $campaign_id ),
				'remaining'      => max( 0, (int) $remaining ),
				'permalink'      => (string) get_permalink( $campaign_id ),
			)
		);
	}

	/**
	 * Send a payload to each active "campaign_alerts" subscriber as a
	 * per-subscriber webhook event (adds their personal unsubscribe link).
	 *
	 * @param array<string,mixed> $base Shared payload.
	 * @return void
	 */
	private function dispatch_to_alerts( array $base ) {
		foreach ( $this->subscribers->active_by_list( 'campaign_alerts' ) as $subscriber ) {
			$payload                    = $base;
			$payload['unsubscribe_url'] = add_query_arg( 'tcm_unsub', $subscriber->unsubscribe_token, home_url( '/' ) );

			do_action( 'tcm_subscription_due', $subscriber, $payload );
		}
	}
}
