<?php
/**
 * Prayer / Segula custom post type.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\PostTypes;

use TCM\Contracts\Registerable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the `tcm_prayer` post type - special prayers and segulot, browsable
 * by category and connectable to campaigns and subscription lists.
 */
final class PrayerPostType implements Registerable {

	const POST_TYPE = 'tcm_prayer';
	const TAXONOMY  = 'tcm_prayer_category';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Register the post type and its category taxonomy.
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Prayers & Segulot', 'tehillim-campaign-manager' ),
					'singular_name' => __( 'Prayer / Segula', 'tehillim-campaign-manager' ),
				),
				'public'       => true,
				'show_ui'      => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-heart',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'has_archive'  => true,
				'rewrite'      => array(
					'slug'       => 'segulot',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			self::TAXONOMY,
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Categories', 'tehillim-campaign-manager' ),
					'singular_name' => __( 'Category', 'tehillim-campaign-manager' ),
				),
				'public'       => true,
				'hierarchical' => true,
				'show_in_rest' => true,
			)
		);
	}
}
