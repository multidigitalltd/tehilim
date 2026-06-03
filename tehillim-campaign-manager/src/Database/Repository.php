<?php
/**
 * Base repository.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for data access. Centralises the $wpdb handle and the prefixed
 * table name so concrete repositories only express queries.
 *
 * All queries MUST use $this->db->prepare() for any value that originates from
 * a request (see docs/ENGINEERING-STANDARDS.md — "never trust input").
 */
abstract class Repository {

	/**
	 * WordPress database handle.
	 *
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * Fully-qualified (prefixed) table name.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * @param \wpdb|null $db Optional handle (injected in tests).
	 */
	public function __construct( $db = null ) {
		global $wpdb;
		$this->db    = $db ? $db : $wpdb;
		$this->table = $this->db->prefix . $this->table_suffix();
	}

	/**
	 * Table name without prefix (e.g. "tcm_assignments").
	 *
	 * @return string
	 */
	abstract protected function table_suffix();
}
