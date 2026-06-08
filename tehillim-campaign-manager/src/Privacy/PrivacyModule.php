<?php
/**
 * Privacy (GDPR / personal data) integration.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Privacy;

use TCM\Contracts\Registerable;
use TCM\Database\AssignmentsRepository;
use TCM\Database\SubscribersRepository;
use TCM\Support\Hebrew;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers WordPress personal-data exporters and erasers so a participant can
 * exercise their data-access / erasure rights over the plugin's PII.
 */
final class PrivacyModule implements Registerable {

	const GROUP = 'tehillim-campaign-manager';

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * Register the exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public function register_exporter( $exporters ) {
		$exporters[ self::GROUP ] = array(
			'exporter_friendly_name' => __( 'Tehillim Campaign Manager', 'tehillim-campaign-manager' ),
			'callback'               => array( $this, 'export' ),
		);
		return $exporters;
	}

	/**
	 * Register the eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers[ self::GROUP ] = array(
			'eraser_friendly_name' => __( 'Tehillim Campaign Manager', 'tehillim-campaign-manager' ),
			'callback'             => array( $this, 'erase' ),
		);
		return $erasers;
	}

	/**
	 * Export a person's data.
	 *
	 * @param string $email Email address.
	 * @param int    $page  Page (unused - single page).
	 * @return array{data:array,done:bool}
	 */
	public function export( $email, $page = 1 ) {
		$items = array();

		foreach ( ( new AssignmentsRepository() )->all_by_email( $email ) as $row ) {
			$items[] = array(
				'group_id'    => 'tcm_assignments',
				'group_label' => __( 'Tehillim chapters', 'tehillim-campaign-manager' ),
				'item_id'     => 'assignment-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'Campaign', 'tehillim-campaign-manager' ),
						'value' => get_the_title( (int) $row->campaign_id ),
					),
					array(
						'name'  => __( 'Chapter', 'tehillim-campaign-manager' ),
						'value' => Hebrew::chapter_label( (int) $row->chapter_number ),
					),
					array(
						'name'  => __( 'Status', 'tehillim-campaign-manager' ),
						'value' => $row->status,
					),
					array(
						'name'  => __( 'Name', 'tehillim-campaign-manager' ),
						'value' => (string) $row->participant_name,
					),
					array(
						'name'  => __( 'Phone', 'tehillim-campaign-manager' ),
						'value' => (string) $row->participant_phone,
					),
				),
			);
		}

		foreach ( ( new SubscribersRepository() )->all_by_email( $email ) as $sub ) {
			$items[] = array(
				'group_id'    => 'tcm_subscribers',
				'group_label' => __( 'Subscriptions', 'tehillim-campaign-manager' ),
				'item_id'     => 'subscriber-' . (int) $sub->id,
				'data'        => array(
					array(
						'name'  => __( 'List', 'tehillim-campaign-manager' ),
						'value' => (string) $sub->list_key,
					),
					array(
						'name'  => __( 'Channel', 'tehillim-campaign-manager' ),
						'value' => (string) $sub->channel,
					),
					array(
						'name'  => __( 'Phone', 'tehillim-campaign-manager' ),
						'value' => (string) $sub->phone,
					),
					array(
						'name'  => __( 'Status', 'tehillim-campaign-manager' ),
						'value' => (string) $sub->status,
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => true,
		);
	}

	/**
	 * Erase a person's data.
	 *
	 * @param string $email Email address.
	 * @param int    $page  Page (unused).
	 * @return array{items_removed:bool,items_retained:bool,messages:array,done:bool}
	 */
	public function erase( $email, $page = 1 ) {
		$anonymised = ( new AssignmentsRepository() )->anonymize_by_email( $email );
		$deleted    = ( new SubscribersRepository() )->delete_by_email( $email );

		$messages = array();
		if ( $anonymised ) {
			$messages[] = __( 'Tehillim chapter records were anonymised (the merit is kept without personal details).', 'tehillim-campaign-manager' );
		}

		return array(
			'items_removed'  => ( $anonymised > 0 || $deleted > 0 ),
			'items_retained' => ( $anonymised > 0 ),
			'messages'       => $messages,
			'done'           => true,
		);
	}
}
