<?php
/**
 * Message templates (WhatsApp/email) with {placeholder} substitution.
 *
 * @package Tehillim_Campaign_Manager
 */

namespace TCM\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders an admin-editable message template by substituting {placeholders}.
 * Used to include a ready-to-send `message` string in webhook payloads so the
 * automation can forward it as-is (and for the daily/reminder emails).
 */
final class Templates {

	/**
	 * Replace {key} tokens in a template with the given values; any unknown
	 * leftover {tokens} are removed.
	 *
	 * @param string               $template Template string.
	 * @param array<string,scalar> $vars     key => value.
	 * @return string
	 */
	public static function render( $template, array $vars ) {
		$template = (string) $template;
		foreach ( $vars as $key => $value ) {
			$template = str_replace( '{' . $key . '}', (string) $value, $template );
		}
		$template = (string) preg_replace( '/\{[a-z0-9_]+\}/i', '', $template );
		return trim( $template );
	}

	/**
	 * Render a named template option for the given variables.
	 *
	 * @param string               $option_key Options key (e.g. tpl_campaign_new).
	 * @param array<string,scalar> $vars       Placeholders.
	 * @return string
	 */
	public static function from_option( $option_key, array $vars ) {
		return self::render( (string) Options::get( $option_key ), $vars );
	}
}
