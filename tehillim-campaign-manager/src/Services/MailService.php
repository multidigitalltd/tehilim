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

if (!defined('ABSPATH')) {
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
    private $alt_body = '';

    /**
     * {@inheritDoc}
     */
    public function register() {
        add_action('tcm_chapter_claimed', array($this, 'on_claimed'), 10, 4);
        add_action('phpmailer_init', array($this, 'set_alt_body'));
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
    public function on_claimed($campaign_id, $rows, $token, $participant) {
        $email = isset($participant['email']) ? $participant['email'] : '';
        if (!$email || !is_email($email) || empty($rows)) {
            return;
        }

        $first    = $rows[0];
        $read_url = Urls::read($campaign_id, (int) $first->id, $token);
        $replace  = Options::placeholders(
            array(
                'name'           => $participant['name'] ? $participant['name'] : __('Friend', 'tehillim-campaign-manager'),
                'campaign_title' => get_the_title($campaign_id),
                'chapter'        => \TCM\Support\Hebrew::chapter_label((int) $first->chapter_number),
                'read_url'       => $read_url,
            )
        );

        $this->send(
            $email,
            (string) Options::get('email_subject'),
            (string) Options::get('email_body'),
            $replace
        );
    }

    /**
     * Send an HTML email with a plain-text alternative.
     *
     * @param string                $to      Recipient.
     * @param string                $subject Subject (with placeholders).
     * @param string                $body    Body (with placeholders).
     * @param array<string,string>  $replace Placeholder replacements.
     * @return bool
     */
    public function send($to, $subject, $body, array $replace = array()) {
        if (!$to || !is_email($to)) {
            return false;
        }

        $subject = strtr($subject, $replace);
        $body    = strtr($body, $replace);

        $this->alt_body = wp_strip_all_tags($body);
        $is_html        = (false !== strpos($body, '<'));
        $html_body      = $is_html ? $body : nl2br(esc_html($body));
        $html           = '<!doctype html><html lang="he" dir="rtl"><body dir="rtl" style="direction:rtl;text-align:right;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#111">'
            . $html_body . '</body></html>';

        $sent = wp_mail($to, $subject, $html, array('Content-Type: text/html; charset=UTF-8'));
        $this->alt_body = '';

        if (!$sent) {
            Logger::log(Logger::WARN, 'email_send_failed', array('to' => $to, 'subject' => $subject));
        }
        return (bool) $sent;
    }

    /**
     * Attach the plain-text alternative to the outgoing message.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer.
     * @return void
     */
    public function set_alt_body($phpmailer) {
        if ('' !== $this->alt_body) {
            $phpmailer->AltBody = $this->alt_body;
        }
    }
}
