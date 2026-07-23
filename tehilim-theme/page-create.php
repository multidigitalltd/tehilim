<?php
/**
 * Campaign Creation Page — exact design match (create.html)
 */
get_header();

$occasions = get_terms( array(
	'taxonomy'   => 'occasion',
	'hide_empty' => false,
) );
if ( is_wp_error( $occasions ) ) {
	$occasions = array();
}
?>

<form class="form-campaign-create create-page">
	<div class="create-header">
		<h1 class="create-title"><?php esc_html_e( 'פתיחת קמפיין תהילים', 'tehilim' ); ?></h1>
		<p class="create-subtitle"><?php esc_html_e( 'שני שלבים קצרים והקמפיין שלכם באוויר.', 'tehilim' ); ?></p>
	</div>

	<div class="create-card">
		<div class="create-label first"><?php esc_html_e( 'מטרת הקריאה', 'tehilim' ); ?></div>

		<!-- Hidden select keeps form.js (select[name="occasion"]) working; chips drive it -->
		<label class="sr-only" for="occasion"><?php esc_html_e( 'מטרת הקריאה', 'tehilim' ); ?></label>
		<select class="sr-only" id="occasion" name="occasion" aria-required="true" tabindex="-1">
			<option value=""><?php esc_html_e( 'בחרו סיבה', 'tehilim' ); ?></option>
			<?php foreach ( $occasions as $occasion ) : ?>
				<option value="<?php echo esc_attr( $occasion->term_id ); ?>"><?php echo esc_html( $occasion->name ); ?></option>
			<?php endforeach; ?>
		</select>

		<div class="occasion-chips">
			<?php foreach ( $occasions as $occasion ) : ?>
				<button type="button" class="occasion-chip" data-occasion="<?php echo esc_attr( $occasion->term_id ); ?>"><?php echo esc_html( $occasion->name ); ?></button>
			<?php endforeach; ?>
		</div>

		<div class="create-label"><?php esc_html_e( 'שם לרפואה / לזכות', 'tehilim' ); ?></div>
		<input class="create-input" type="text" id="dedication_name" name="dedication_name" required placeholder="<?php esc_attr_e( 'לדוגמה: משה בן חיה', 'tehilim' ); ?>">

		<div class="create-label"><?php esc_html_e( 'שם המארגן / הקבוצה', 'tehilim' ); ?></div>
		<input class="create-input last" type="text" id="organizer_name" name="organizer_name" required placeholder="<?php esc_attr_e( 'לדוגמה: משפחת כהן', 'tehilim' ); ?>">

		<div class="create-goal">
			<div class="create-goal-label"><?php esc_html_e( 'יעד הקמפיין', 'tehilim' ); ?></div>
			<div class="create-goal-value" id="goal_value">10</div>
			<div class="create-goal-sub"><?php esc_html_e( 'ספרי תהילים', 'tehilim' ); ?> · <span id="goal_chapters">1,500</span> <?php esc_html_e( 'פרקים', 'tehilim' ); ?></div>
			<input class="create-goal-range" type="range" id="goal_books" name="goal_books" min="1" max="100" value="10">
		</div>

		<?php if ( defined( 'TURNSTILE_SITE_KEY' ) && TURNSTILE_SITE_KEY ) : ?>
			<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( TURNSTILE_SITE_KEY ); ?>" style="margin-bottom:20px"></div>
		<?php endif; ?>

		<button type="submit" class="btn-create-submit"><?php esc_html_e( 'יצירת הקמפיין', 'tehilim' ); ?></button>
	</div>
</form>

<script>
( function() {
	var form = document.querySelector( '.form-campaign-create' );
	if ( ! form ) { return; }

	// Occasion chips <-> hidden select
	var select = form.querySelector( 'select[name="occasion"]' );
	var chips = form.querySelectorAll( '.occasion-chip' );
	chips.forEach( function( chip ) {
		chip.addEventListener( 'click', function() {
			chips.forEach( function( c ) { c.classList.remove( 'active' ); } );
			chip.classList.add( 'active' );
			if ( select ) { select.value = chip.dataset.occasion; }
		} );
	} );

	// Goal slider display
	var range = form.querySelector( '#goal_books' );
	var valEl = form.querySelector( '#goal_value' );
	var chapEl = form.querySelector( '#goal_chapters' );
	if ( range ) {
		range.addEventListener( 'input', function() {
			var v = parseInt( range.value, 10 ) || 0;
			if ( valEl ) { valEl.textContent = v; }
			if ( chapEl ) { chapEl.textContent = ( v * 150 ).toLocaleString( 'en-US' ); }
		} );
	}
} )();
</script>

<?php
get_footer();
