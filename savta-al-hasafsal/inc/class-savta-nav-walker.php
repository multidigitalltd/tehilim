<?php
/**
 * Renders the primary menu as a flat run of links, matching the design's nav.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bare links, no list markup: the nav row is a flex container of anchors.
 */
class Savta_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Opens a level. Depth is limited to one, so nothing to open.
	 *
	 * @param string   $output Output buffer.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Arguments.
	 * @return void
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed, Generic.CodeAnalysis.UnusedFunctionParameter.Found

	/**
	 * Closes a level.
	 *
	 * @param string   $output Output buffer.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Arguments.
	 * @return void
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed, Generic.CodeAnalysis.UnusedFunctionParameter.Found

	/**
	 * Renders one link.
	 *
	 * @param string   $output Output buffer.
	 * @param WP_Post  $item   Menu item.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Arguments.
	 * @param int      $id     Item ID.
	 * @return void
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$atts = array(
			'class' => 'sv-nav__link',
			'href'  => (string) $item->url,
		);

		if ( ! empty( $item->target ) ) {
			$atts['target'] = (string) $item->target;
		}

		if ( ! empty( $item->attr_title ) ) {
			$atts['title'] = (string) $item->attr_title;
		}

		if ( ! empty( $item->current ) ) {
			$atts['aria-current'] = 'page';
		}

		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook, applied so plugins keep working.

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( '' === $value ) {
				continue;
			}
			$attributes .= sprintf( ' %s="%s"', esc_attr( $attr ), 'href' === $attr ? esc_url( $value ) : esc_attr( $value ) );
		}

		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hooks, applied so plugins keep working.
		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );
		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$output .= '<a' . $attributes . '>' . esc_html( $title ) . '</a>';
	}

	/**
	 * Closes one link. Nothing to close.
	 *
	 * @param string   $output Output buffer.
	 * @param WP_Post  $item   Menu item.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Arguments.
	 * @return void
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed, Generic.CodeAnalysis.UnusedFunctionParameter.Found
}
