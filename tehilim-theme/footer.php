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
					<h4>מוצרים</h4>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/campaigns' ) ); ?>">קמפיינים</a></li>
						<li><a href="<?php echo esc_url( home_url( '/create' ) ); ?>">צרו קמפיין</a></li>
					</ul>
				</div>

				<div class="footer-col">
					<h4>על אודות</h4>
					<ul>
						<li><a href="#">כך זה עובד</a></li>
						<li><a href="#">תכונות</a></li>
					</ul>
				</div>

				<div class="footer-col">
					<h4>משאבים</h4>
					<ul>
						<li><a href="#">שאלות נפוצות</a></li>
						<li><a href="#">צרו קשר</a></li>
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
