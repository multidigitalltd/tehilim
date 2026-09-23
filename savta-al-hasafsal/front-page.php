<?php
/**
 * The one-page homepage. Every section reads its copy from the page's meta.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/home/hero' );

$savta_sections = array(
	'intro'  => 'intro_enable',
	'about'  => 'about_enable',
	'how'    => 'how_enable',
	'who'    => 'who_enable',
	'efrat'  => 'efrat_enable',
	'first'  => 'first_enable',
	'notice' => 'notice_enable',
	'quotes' => 'quotes_enable',
	'faq'    => 'faq_enable',
);

foreach ( $savta_sections as $savta_part => $savta_toggle ) {
	if ( savta_section_enabled( $savta_toggle ) ) {
		get_template_part( 'template-parts/home/' . $savta_part );
	}
}

// The booking form is the point of the site, so it cannot be switched off.
get_template_part( 'template-parts/home/signup' );

get_footer();
