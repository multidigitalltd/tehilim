<?php
/**
 * Per-event subscriber emails (email channel).
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Contracts\Registerable;
use TCM\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends an actual email (designed RTL HTML, with an ad) to subscribers whose
 * channel is "email" when their list content is due, using the per-event email
 * template the admin edits in Settings. WhatsApp-channel subscribers keep going
 * out via the webhook only.
 */
final class EmailService implements Registerable {

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'tcm_subscription_due', array( $this, 'on_subscription_due' ), 30, 2 );
	}

	/**
	 * Email an email-channel subscriber the per-event content.
	 *
	 * @param object              $subscriber Subscriber row.
	 * @param array<string,mixed> $payload    Content payload.
	 * @return void
	 */
	public function on_subscription_due( $subscriber, $payload ) {
		if ( ! is_object( $subscriber ) || 'email' !== (string) $subscriber->channel ) {
			return;
		}
		$email = (string) $subscriber->email;
		if ( ! is_email( $email ) ) {
			return;
		}

		$list  = (string) ( $payload['list'] ?? '' );
		$unsub = isset( $payload['unsubscribe_url'] )
			? (string) $payload['unsubscribe_url']
			: add_query_arg( 'tcm_unsub', (string) $subscriber->unsubscribe_token, home_url( '/' ) );

		$vars = Options::placeholders(
			array(
				'name'           => $subscriber->name ? $subscriber->name : __( 'Friend', 'tehillim-campaign-manager' ),
				'site_name'      => (string) get_bloginfo( 'name' ),
				'date_he'        => ZmanimService::hebrew_date(),
				'chapter'        => (string) ( $payload['chapter_label'] ?? $payload['chapter_number'] ?? '' ),
				'campaign_title' => (string) ( $payload['campaign_title'] ?? '' ),
				'link'           => (string) ( $payload['permalink'] ?? '' ),
			)
		);

		if ( 'campaign_alerts' === $list ) {
			$subject = (string) Options::get( 'email_subject_campaign' );
			$body    = (string) Options::get( 'email_body_campaign' );
		} else {
			$subject = (string) Options::get( 'email_subject_daily' );
			$body    = (string) Options::get( 'email_body_daily' );
		}

		MailService::send( $email, $subject, $body, $vars, $unsub );
	}
}
