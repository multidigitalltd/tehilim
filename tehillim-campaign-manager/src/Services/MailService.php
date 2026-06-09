<?php
/**
 * Transactional email.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Contracts\Registerable;
use TCM\Support\Logger;
use TCM\Support\Options;
use TCM\Support\Urls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends participant emails in response to domain events. Emails are multipart:
 * an RTL HTML body plus a plain-text alternative (set via phpmailer_init) for
 * deliverability.
 */
final class MailService implements Registerable {

	/**
	 * Plain-text alternative for the email currently being sent.
	 *
	 * @var string
	 */
	private static $alt_body = '';

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'tcm_chapter_claimed', array( $this, 'on_claimed' ), 10, 4 );
		add_action( 'phpmailer_init', array( $this, 'set_alt_body' ) );
	}

	/**
	 * Send the "you received a chapter" email after a successful claim.
	 *
	 * @param int                  $campaign_id Campaign.
	 * @param array<int,object>    $rows        Claimed rows.
	 * @param string               $token       Claim token.
	 * @param array<string,string> $participant Participant (name/email/phone).
	 * @return void
	 */
	public function on_claimed( $campaign_id, $rows, $token, $participant ) {
		$email = isset( $participant['email'] ) ? $participant['email'] : '';
		if ( ! $email || ! is_email( $email ) || empty( $rows ) ) {
			return;
		}

		$first    = $rows[0];
		$read_url = Urls::read( $campaign_id, (int) $first->id, $token );
		$replace  = Options::placeholders(
			array(
				'name'           => $participant['name'] ? $participant['name'] : __( 'Friend', 'tehillim-campaign-manager' ),
				'campaign_title' => get_the_title( $campaign_id ),
				'chapter'        => \TCM\Support\Hebrew::chapter_label( (int) $first->chapter_number ),
				'read_url'       => $read_url,
			)
		);

		self::send(
			$email,
			(string) Options::get( 'email_subject' ),
			(string) Options::get( 'email_body' ),
			$replace
		);
	}

	/**
	 * Send an HTML email with a plain-text alternative.
	 *
	 * @param string               $to      Recipient.
	 * @param string               $subject Subject (with placeholders).
	 * @param string               $body    Body (with placeholders).
	 * @param array<string,string> $replace     Placeholder replacements.
	 * @param string               $unsubscribe Optional unsubscribe URL.
	 * @return bool
	 */
	public static function send( $to, $subject, $body, array $replace = array(), $unsubscribe = '' ) {
		if ( ! $to || ! is_email( $to ) ) {
			return false;
		}

		$subject = strtr( $subject, $replace );
		$body    = strtr( $body, $replace );

		self::$alt_body = wp_strip_all_tags( $body );
		$is_html        = ( false !== strpos( $body, '<' ) );
		$html_body      = $is_html ? $body : nl2br( esc_html( $body ) );

		$html = self::wrap( $html_body, (string) $unsubscribe );

		$sent           = wp_mail( $to, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
		self::$alt_body = '';

		if ( ! $sent ) {
			Logger::log(
				Logger::WARN,
				'email_send_failed',
				array(
					'to'      => $to,
					'subject' => $subject,
				)
			);
		}
		return (bool) $sent;
	}

	/**
	 * Designed, RTL HTML email wrapper: site-name header, the body in a card,
	 * an active "email_footer" ad, and an optional unsubscribe footer.
	 *
	 * @param string $body_html   Inner body HTML.
	 * @param string $unsubscribe Optional unsubscribe URL.
	 * @return string
	 */
	private static function wrap( $body_html, $unsubscribe = '' ) {
		$site = esc_html( (string) get_bloginfo( 'name' ) );
		$ad   = ( new AdService() )->email_html();

		$footer = '';
		if ( '' !== $unsubscribe ) {
			$footer = '<div style="margin-top:18px;font-size:12px;color:#999;text-align:center">'
				. '<a href="' . esc_url( $unsubscribe ) . '" style="color:#999">' . esc_html__( 'Unsubscribe', 'tehillim-campaign-manager' ) . '</a></div>';
		}

		return '<!doctype html><html lang="he" dir="rtl"><head><meta charset="utf-8"></head>'
			. '<body dir="rtl" style="direction:rtl;text-align:right;margin:0;padding:24px;background:#f4f1e8;font-family:Arial,Helvetica,sans-serif;color:#13192d">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">'
			. '<table role="presentation" width="600" style="max-width:600px;width:100%" cellpadding="0" cellspacing="0">'
			. '<tr><td style="padding:0 8px 14px;font-size:20px;font-weight:bold;color:#2e40c0">' . $site . '</td></tr>'
			. '<tr><td style="background:#ffffff;border-radius:14px;padding:24px;font-size:16px;line-height:1.9">' . $body_html . '</td></tr>'
			. '<tr><td>' . $ad . '</td></tr>'
			. '<tr><td>' . $footer . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	/**
	 * Attach the plain-text alternative to the outgoing message.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer.
	 * @return void
	 */
	public function set_alt_body( $phpmailer ) {
		if ( '' !== self::$alt_body ) {
			$phpmailer->AltBody = self::$alt_body; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer property.
		}
	}
}
