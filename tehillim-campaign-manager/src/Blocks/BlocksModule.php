<?php
/**
 * Gutenberg block integration.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Blocks;

use TCM\Contracts\Registerable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin's dynamic blocks (category "Tehillim"). Each block is a
 * thin, server-rendered wrapper around an existing shortcode, so all rendering
 * logic and styling lives in one place and the blocks stay editable in the
 * block editor (and usable inside Elementor / any builder). No build step: the
 * editor script is hand-written against the global `wp.*` APIs.
 */
final class BlocksModule implements Registerable {

	const CATEGORY = 'tehillim';
	const HANDLE   = 'tcm-blocks';

	/**
	 * Block definitions: name => [shortcode tag, attribute schema].
	 *
	 * The attribute schema maps a block attribute to its type/default and is
	 * forwarded to the shortcode as `key="value"` when non-empty.
	 *
	 * @return array<string,array{shortcode:string,attributes:array<string,array{type:string,default:mixed}>}>
	 */
	private function blocks() {
		return array(
			'global-stats'    => array(
				'shortcode'  => 'tehillim_global_stats',
				'attributes' => array(),
			),
			'campaigns'       => array(
				'shortcode'  => 'tehillim_campaigns',
				'attributes' => array(),
			),
			'campaign'        => array(
				'shortcode'  => 'tehillim_campaign',
				'attributes' => array(
					'id' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
			),
			'leaderboard'     => array(
				'shortcode'  => 'tehillim_leaderboard',
				'attributes' => array(
					'id'    => array(
						'type'    => 'number',
						'default' => 0,
					),
					'limit' => array(
						'type'    => 'number',
						'default' => 10,
					),
				),
			),
			'activity'        => array(
				'shortcode'  => 'tehillim_activity',
				'attributes' => array(
					'id'    => array(
						'type'    => 'number',
						'default' => 0,
					),
					'limit' => array(
						'type'    => 'number',
						'default' => 12,
					),
				),
			),
			'segulot'         => array(
				'shortcode'  => 'tehillim_segulot',
				'attributes' => array(
					'category' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			),
			'subscribe'       => array(
				'shortcode'  => 'tehillim_subscribe',
				'attributes' => array(),
			),
			'create-campaign' => array(
				'shortcode'  => 'tehillim_create_campaign_form',
				'attributes' => array(),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'register_category' ) );
	}

	/**
	 * Add the "Tehillim" block category.
	 *
	 * @param array<int,array<string,mixed>> $categories Existing categories.
	 * @return array<int,array<string,mixed>>
	 */
	public function register_category( $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => self::CATEGORY,
				'title' => __( 'Tehillim', 'tehillim-campaign-manager' ),
				'icon'  => 'book-alt',
			)
		);
		return $categories;
	}

	/**
	 * Register the editor script and every dynamic block.
	 *
	 * @return void
	 */
	public function register_blocks() {
		wp_register_script(
			self::HANDLE,
			TCM_PLUGIN_URL . 'assets/js/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			TCM_VERSION,
			true
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( self::HANDLE, 'tehillim-campaign-manager' );
		}

		foreach ( $this->blocks() as $name => $def ) {
			$attributes = array();
			foreach ( $def['attributes'] as $key => $schema ) {
				$attributes[ $key ] = array(
					'type'    => 'number' === $schema['type'] ? 'number' : 'string',
					'default' => $schema['default'],
				);
			}
			register_block_type(
				self::CATEGORY . '/' . $name,
				array(
					'category'        => self::CATEGORY,
					'editor_script'   => self::HANDLE,
					'attributes'      => $attributes,
					'render_callback' => function ( $attrs ) use ( $def ) {
						return $this->render( $def, is_array( $attrs ) ? $attrs : array() );
					},
				)
			);
		}
	}

	/**
	 * Render a block by delegating to its shortcode.
	 *
	 * @param array{shortcode:string,attributes:array<string,array{type:string,default:mixed}>} $def   Block definition.
	 * @param array<string,mixed>                                                               $attrs Block attributes.
	 * @return string
	 */
	private function render( array $def, array $attrs ) {
		$pairs = '';
		foreach ( $def['attributes'] as $key => $schema ) {
			if ( ! isset( $attrs[ $key ] ) || '' === $attrs[ $key ] || 0 === $attrs[ $key ] ) {
				continue;
			}
			if ( 'number' === $schema['type'] ) {
				$pairs .= ' ' . $key . '="' . (int) $attrs[ $key ] . '"';
			} else {
				$pairs .= ' ' . $key . '="' . esc_attr( sanitize_text_field( (string) $attrs[ $key ] ) ) . '"';
			}
		}
		return do_shortcode( '[' . $def['shortcode'] . $pairs . ']' );
	}
}
