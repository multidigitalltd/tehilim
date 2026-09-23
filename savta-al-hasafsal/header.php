<?php
/**
 * Document head and the sticky nav.
 *
 * @package Savta_Al_Hasafsal
 */

defined( 'ABSPATH' ) || exit;

$savta_cta_text = (string) savta_option( 'savta_nav_cta_text' );
$savta_cta_url  = (string) savta_option( 'savta_nav_cta_url' );
$savta_home     = savta_is_home_layout() ? '' : home_url( '/' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php // Marks that scripts run, so the entrance animations only hide content when they will reveal it. ?>
	<script>document.documentElement.classList.add( 'js' );</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php savta_the_skip_link(); ?>

<nav class="sv-nav" aria-label="<?php esc_attr_e( 'ניווט ראשי', 'savta-al-hasafsal' ); ?>">
	<div class="sv-nav__row">
		<?php savta_the_nav_logo(); ?>

		<div class="sv-nav__links">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'items_wrap'     => '%3$s',
						'depth'          => 1,
						'fallback_cb'    => false,
						'walker'         => new Savta_Nav_Walker(),
					)
				);
			} else {
				foreach ( savta_default_nav() as $savta_anchor => $savta_label ) {
					printf( '<a class="sv-nav__link" href="%s">%s</a>', esc_url( $savta_home . $savta_anchor ), esc_html( $savta_label ) );
				}
			}
			?>

			<?php if ( '' !== $savta_cta_text && '' !== $savta_cta_url ) : ?>
				<a class="sv-nav__cta" href="<?php echo esc_url( 0 === strpos( $savta_cta_url, '#' ) ? $savta_home . $savta_cta_url : $savta_cta_url ); ?>"><?php echo esc_html( $savta_cta_text ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</nav>

<main id="sv-main" class="sv-main" tabindex="-1">
