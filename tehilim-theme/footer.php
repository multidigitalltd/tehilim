<?php
/**
 * Footer Template
 */
?>

<footer class="site-footer">
	<div class="footer-content">
		<div class="container">
			<div class="footer-grid">
				<div class="footer-col footer-about">
					<div class="footer-logo">
						<?php
						if ( has_custom_logo() ) {
							the_custom_logo();
						} else {
							echo '<span class="site-logo">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
						}
						?>
					</div>
					<p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
				</div>

				<div class="footer-col">
					<h4><?php esc_html_e( 'Product', 'tehilim' ); ?></h4>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/campaigns' ) ); ?>"><?php esc_html_e( 'Campaigns', 'tehilim' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/create' ) ); ?>"><?php esc_html_e( 'Create Campaign', 'tehilim' ); ?></a></li>
					</ul>
				</div>

				<div class="footer-col">
					<h4><?php esc_html_e( 'About', 'tehilim' ); ?></h4>
					<ul>
						<li><a href="#"><?php esc_html_e( 'How it Works', 'tehilim' ); ?></a></li>
						<li><a href="#"><?php esc_html_e( 'Features', 'tehilim' ); ?></a></li>
					</ul>
				</div>

				<div class="footer-col">
					<h4><?php esc_html_e( 'Resources', 'tehilim' ); ?></h4>
					<ul>
						<li><a href="#"><?php esc_html_e( 'FAQ', 'tehilim' ); ?></a></li>
						<li><a href="#"><?php esc_html_e( 'Contact', 'tehilim' ); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>

	<div class="footer-bottom">
		<div class="container">
			<p>&copy; <?php echo esc_html( date( 'Y' ) . ' ' . get_bloginfo( 'name' ) ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
