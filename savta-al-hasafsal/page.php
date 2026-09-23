<?php
/**
 * Inner page (accessibility statement, privacy policy, and any other page).
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/page/content' );
}

get_footer();
