<?php
/**
 * Campaign Creation Page — exact design match (create.html)
 * Requires a logged-in user (also enforced server-side in the REST endpoint).
 */
get_header();

if ( ! is_user_logged_in() ) :
	?>
	<div class="login-page page-anim">
		<div class="login-card">
			<div class="login-icon">
				<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FFF3E4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path></svg>
			</div>
			<h1 class="login-title"><?php esc_html_e( 'נדרשת התחברות', 'tehilim' ); ?></h1>
			<p class="login-subtitle"><?php esc_html_e( 'כדי לפתוח קמפיין חדש יש להתחבר לחשבון — כך תוכלו לנהל את הקמפיין, לאשר שגרירים ולעקוב אחרי ההתקדמות.', 'tehilim' ); ?></p>
			<a class="btn-create-submit login-cta" href="<?php echo esc_url( tehilim_login_page_url( get_permalink() ) ); ?>"><?php esc_html_e( 'להתחברות', 'tehilim' ); ?></a>
		</div>
	</div>
	<?php
	get_footer();
	return;
endif;

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
		<input class="create-input" type="text" id="organizer_name" name="organizer_name" required placeholder="<?php esc_attr_e( 'לדוגמה: משפחת כהן', 'tehilim' ); ?>">

		<div class="create-label"><?php esc_html_e( 'הקדשה (לא חובה)', 'tehilim' ); ?></div>
		<input class="create-input" type="text" id="dedication_text" name="dedication_text" maxlength="200" placeholder="<?php esc_attr_e( 'לדוגמה: לרפואה שלמה בתוך שאר חולי ישראל', 'tehilim' ); ?>">

		<div class="create-label"><?php esc_html_e( 'תמונת הקמפיין', 'tehilim' ); ?></div>
		<div class="create-image" data-mode="upload">
			<div class="create-image-modes">
				<button type="button" class="create-image-mode active" data-image-mode="upload"><?php esc_html_e( 'העלאת תמונה', 'tehilim' ); ?></button>
				<button type="button" class="create-image-mode" data-image-mode="none"><?php esc_html_e( 'ללא תמונה (פסוקי שבח)', 'tehilim' ); ?></button>
			</div>

			<div class="create-image-upload">
				<input type="file" id="campaign_image" name="campaign_image" accept="image/jpeg,image/png,image/webp" hidden>
				<label for="campaign_image" class="create-image-drop">
					<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#B9822B" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v13"></path></svg>
					<span class="create-image-drop-title"><?php esc_html_e( 'גררו לכאן תמונת הנצחה / לוגו הקמפיין', 'tehilim' ); ?></span>
					<span class="create-image-drop-sub"><?php esc_html_e( 'או לחצו לבחירה · JPG / PNG / WEBP · עד 3MB', 'tehilim' ); ?></span>
				</label>
				<div class="create-image-preview" hidden>
					<img alt="" class="create-image-preview-img">
					<button type="button" class="create-image-remove"><?php esc_html_e( 'הסרת התמונה', 'tehilim' ); ?></button>
				</div>
			</div>

			<div class="create-image-nonote" hidden>
				<?php esc_html_e( 'בעמוד הקמפיין יוצגו פסוקים נבחרים בשבח אמירת תהילים במקום תמונה.', 'tehilim' ); ?>
			</div>
		</div>

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

	// Campaign image: upload / no-image toggle + preview
	var imageWrap = form.querySelector( '.create-image' );
	if ( imageWrap ) {
		var MAX_BYTES = 3 * 1024 * 1024;
		var fileInput = imageWrap.querySelector( '#campaign_image' );
		var uploadBox = imageWrap.querySelector( '.create-image-upload' );
		var noNote = imageWrap.querySelector( '.create-image-nonote' );
		var dropLabel = imageWrap.querySelector( '.create-image-drop' );
		var preview = imageWrap.querySelector( '.create-image-preview' );
		var previewImg = imageWrap.querySelector( '.create-image-preview-img' );
		var removeBtn = imageWrap.querySelector( '.create-image-remove' );
		var modeButtons = imageWrap.querySelectorAll( '.create-image-mode' );

		function setMode( mode ) {
			imageWrap.dataset.mode = mode;
			modeButtons.forEach( function( b ) {
				b.classList.toggle( 'active', b.dataset.imageMode === mode );
			} );
			uploadBox.hidden = ( mode !== 'upload' );
			noNote.hidden = ( mode === 'upload' );
			if ( mode === 'none' ) { clearImage(); }
		}

		function clearImage() {
			fileInput.value = '';
			form.dataset.imageData = '';
			preview.hidden = true;
			dropLabel.hidden = false;
			previewImg.removeAttribute( 'src' );
		}

		modeButtons.forEach( function( b ) {
			b.addEventListener( 'click', function() { setMode( b.dataset.imageMode ); } );
		} );

		removeBtn.addEventListener( 'click', clearImage );

		fileInput.addEventListener( 'change', function() {
			var file = fileInput.files && fileInput.files[ 0 ];
			if ( ! file ) { return; }
			if ( ! /^image\/(jpeg|png|webp)$/.test( file.type ) ) {
				window.alert( 'סוג קובץ לא נתמך. יש להעלות JPG, PNG או WEBP.' );
				clearImage();
				return;
			}
			if ( file.size > MAX_BYTES ) {
				window.alert( 'הקובץ גדול מדי (עד 3MB).' );
				clearImage();
				return;
			}
			var reader = new FileReader();
			reader.onload = function( ev ) {
				form.dataset.imageData = ev.target.result; // data URL for the REST body
				previewImg.src = ev.target.result;
				preview.hidden = false;
				dropLabel.hidden = true;
			};
			reader.readAsDataURL( file );
		} );

		// Drag & drop onto the label
		[ 'dragover', 'dragenter' ].forEach( function( evt ) {
			dropLabel.addEventListener( evt, function( e ) { e.preventDefault(); dropLabel.classList.add( 'dragging' ); } );
		} );
		[ 'dragleave', 'drop' ].forEach( function( evt ) {
			dropLabel.addEventListener( evt, function( e ) { e.preventDefault(); dropLabel.classList.remove( 'dragging' ); } );
		} );
		dropLabel.addEventListener( 'drop', function( e ) {
			if ( e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[ 0 ] ) {
				fileInput.files = e.dataTransfer.files;
				fileInput.dispatchEvent( new Event( 'change' ) );
			}
		} );
	}
} )();
</script>

<?php
get_footer();
