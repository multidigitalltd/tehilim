<?php
/**
 * Header Template
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> dir="rtl">
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="header-top">
		<div class="container">
			<div class="header-logo">
				<?php
				if ( has_custom_logo() ) {
					the_custom_logo();
				} else {
					echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="site-logo">' . esc_html( get_bloginfo( 'name' ) ) . '</a>';
				}
				?>
			</div>

			<nav class="primary-nav" role="navigation">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'fallback_cb'    => '__return_empty_string',
					'container'      => false,
				) );
				?>
			</nav>

			<button class="nav-toggle" aria-label="פתחו תפריט ניווט">
				<span></span>
				<span></span>
				<span></span>
			</button>
		</div>
	</div>
</header>
