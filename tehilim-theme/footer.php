<?php
/**
 * Footer Template - Tehilim
 */
?>

<footer class="site-footer">
	<div class="footer-wrapper">
		<!-- Brand Column -->
		<div>
			<div class="footer-brand">
				<div class="footer-logo">
					<svg width="21" height="21" viewBox="0 0 24 24" fill="none">
						<path d="M12 6.4C10.2 5 7.4 4.5 4.6 5v12.3c2.8-.5 5.6 0 7.4 1.4 1.8-1.4 4.6-1.9 7.4-1.4V5c-2.8-.5-5.6 0-7.4 1.4Z" stroke="#FFF3E4" stroke-width="1.7" stroke-linejoin="round"></path>
						<path d="M12 6.4v12.3" stroke="#F0CE7E" stroke-width="1.7" stroke-linecap="round"></path>
					</svg>
				</div>
				<span class="footer-brand-name"><?php bloginfo( 'name' ); ?></span>
			</div>
			<p class="footer-description">
				<?php echo esc_html( get_bloginfo( 'description' ) ); ?>
			</p>
			<div class="footer-heart">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="#C05A3A">
					<path d="M12 21s-7.5-4.7-10-9.3C.4 8.6 2 5 5.5 5c2 0 3.4 1.1 4.5 2.6C11 6.1 12.5 5 14.5 5 18 5 19.6 8.6 22 11.7 19.5 16.3 12 21 12 21z"></path>
				</svg>
				<?php esc_html_e( 'נבנה באהבה לעם ישראל', 'tehilim' ); ?>
			</div>
		</div>

		<!-- Product Links -->
		<div>
			<h4><?php esc_html_e( 'המוצר', 'tehilim' ); ?></h4>
			<ul class="footer-col">
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'campaign' ) ); ?>"><?php esc_html_e( 'גלו קמפיינים', 'tehilim' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/create/' ) ); ?>"><?php esc_html_e( 'פתחו קמפיין', 'tehilim' ); ?></a></li>
				<li><a href="#"><?php esc_html_e( 'האזור שלי', 'tehilim' ); ?></a></li>
			</ul>
		</div>

		<!-- About Links -->
		<div>
			<h4><?php esc_html_e( 'אודות', 'tehilim' ); ?></h4>
			<ul class="footer-col">
				<li><a href="#"><?php esc_html_e( 'אודות הפלטפורמה', 'tehilim' ); ?></a></li>
			</ul>
		</div>

		<!-- Resources Links -->
		<div>
			<h4><?php esc_html_e( 'משאבים', 'tehilim' ); ?></h4>
			<ul class="footer-col">
				<li><a href="#"><?php esc_html_e( 'שאלות נפוצות', 'tehilim' ); ?></a></li>
			</ul>
		</div>
	</div>

	<div class="footer-bottom">
		<div class="footer-copyright">
			&copy; <?php echo esc_html( date( 'Y' ) . ' ' . get_bloginfo( 'name' ) ); ?> · <?php esc_html_e( 'כל הזכויות שמורות.', 'tehilim' ); ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
