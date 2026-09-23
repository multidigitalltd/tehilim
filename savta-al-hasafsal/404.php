<?php
/**
 * Not-found page.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<article class="sv-page">
	<div class="sv-page__inner sv-page__inner--center">
		<header class="sv-page__head">
			<p class="sv-eyebrow">404</p>
			<h1 class="sv-page__title"><?php esc_html_e( 'העמוד לא נמצא', 'savta-al-hasafsal' ); ?></h1>
		</header>
		<p><?php esc_html_e( 'אולי הקישור השתנה, ואולי פשוט יצא לטיול. הספסל עדיין כאן.', 'savta-al-hasafsal' ); ?></p>
		<p><a class="sv-btn sv-btn--sage" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'חזרה לדף הבית', 'savta-al-hasafsal' ); ?></a></p>
	</div>
</article>

<?php
get_footer();
