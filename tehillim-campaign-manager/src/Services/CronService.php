<?php
/**
 * Scheduled tasks (reminders + auto-release).
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Services;

use TCM\Contracts\Registerable;
use TCM\Database\AssignmentsRepository;
use TCM\Database\LogsRepository;
use TCM\Support\Options;
use TCM\Services\SubscriptionService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hourly task that nudges participants and frees abandoned chapters.
 *
 * Notifications are delivered ONLY as webhook events (chapter_reminder,
 * chapter_release_warning, chapter_auto_released) - the site routes those to
 * WhatsApp (or anything else) via its own automation. This service never sends
 * email or talks to a messaging API directly.
 */
final class CronService implements Registerable {

	const HOOK     = 'tcm_cron_tasks';
	const BATCH    = 200;
	const LOG_DAYS = 180;

	/**
	 * @var AssignmentsRepository
	 */
	private $assignments;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->assignments = new AssignmentsRepository();
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( self::HOOK, array( $this, 'run' ) );
	}

	/**
	 * Process due assignments.
	 *
	 * @return void
	 */
	public function run() {
		( new LogsRepository() )->purge_older_than( self::LOG_DAYS );

		// Daily subscription content (webhook only) - independent of reminders.
		( new SubscriptionService() )->process_daily();

		if ( '1' !== (string) Options::get( 'reminders_enabled', '1' ) ) {
			return;
		}

		// Local-time timestamp, compared against the locally-stored *_at datetimes.
		$now            = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$reminder_hours = max( 1, (int) Options::get( 'reminder_hours', 6 ) );
		$reminder_max   = max( 0, (int) Options::get( 'reminder_max', 2 ) );
		$warning_hours  = max( 1, (int) Options::get( 'release_warning_hours', 24 ) );
		$release_hours  = max( $warning_hours + 1, (int) Options::get( 'release_after_hours', 36 ) );

		foreach ( $this->assignments->due_taken( self::BATCH ) as $row ) {
			$taken_ts = strtotime( $row->taken_at ? $row->taken_at : $row->updated_at );
			if ( ! $taken_ts ) {
				continue;
			}
			$hours = ( $now - $taken_ts ) / HOUR_IN_SECONDS;

			// 1) Reminder.
			if ( $reminder_max > 0 && (int) $row->reminder_count < $reminder_max ) {
				$last = $row->last_reminder_at ? strtotime( $row->last_reminder_at ) : $taken_ts;
				if ( ( $now - $last ) >= $reminder_hours * HOUR_IN_SECONDS ) {
					/** Fires when a reminder is due (webhook only). */
					do_action( 'tcm_chapter_reminder', $row );
					$this->assignments->record_reminder( (int) $row->id, (int) $row->reminder_count + 1 );
					continue;
				}
			}

			// 2) Release warning.
			if ( $hours >= $warning_hours && empty( $row->release_notice_at ) ) {
				/** Fires when a release warning is due (webhook only). */
				do_action( 'tcm_chapter_release_warning', $row );
				$this->assignments->record_release_notice( (int) $row->id );
				continue;
			}

			// 3) Auto-release (after a warning was sent).
			if ( $hours >= $release_hours && ! empty( $row->release_notice_at ) ) {
				/** Fires just before an abandoned chapter is auto-released. */
				do_action( 'tcm_chapter_auto_released', $row );
				$this->assignments->release( (int) $row->id );
				StatsService::flush( (int) $row->campaign_id );
			}
		}
	}
}
