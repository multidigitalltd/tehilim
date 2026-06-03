<?php
/**
 * CSV export.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Admin;

use TCM\Contracts\Registerable;
use TCM\Database\AssignmentsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Streams a CSV of a campaign's participants (admin only, nonce-protected).
 */
final class Exporter implements Registerable {

	const ACTION = 'tcm_export_participants';

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'export' ) );
	}

	/**
	 * Handle the export.
	 *
	 * @return void
	 */
	public function export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'tehillim-campaign-manager' ) );
		}
		check_admin_referer( 'tcm_export' );

		$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
		if ( ! $campaign_id ) {
			wp_die( esc_html__( 'Invalid campaign.', 'tehillim-campaign-manager' ) );
		}

		$rows     = ( new AssignmentsRepository() )->participants_for_campaign( $campaign_id );
		$filename = 'tehillim-participants-' . $campaign_id . '-' . gmdate( 'Ymd' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$out = fopen( 'php://output', 'w' );
		// UTF-8 BOM so Excel reads Hebrew correctly.
		fwrite( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array( 'name', 'email', 'phone', 'chapter', 'round', 'status', 'taken_at', 'completed_at' ) );
		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				array(
					$row->participant_name,
					$row->participant_email,
					$row->participant_phone,
					$row->chapter_number,
					$row->round_number,
					$row->status,
					$row->taken_at,
					$row->completed_at,
				)
			);
		}
		fclose( $out );
		exit;
	}
}
