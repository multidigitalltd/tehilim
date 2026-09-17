<?php
/**
 * תהילים לפי שם — /tehilim-lefi-shem/
 * Enter a name; show the verses of Psalm 119 (public-domain acrostic) that
 * begin with each of its letters, plus the "קרע שטן" set. Text is loaded
 * client-side from the theme's bundled Psalms data.
 */
get_header();
?>

<div class="tehilim-reader-page page-anim">

	<section class="prayers-hero">
		<h1><?php esc_html_e( 'תהילים לפי שם', 'tehilim' ); ?></h1>
		<p><?php esc_html_e( 'הזינו שם, וקבלו את פסוקי פרק קי״ט (תהילים) המתחילים באותיות השם — מנהג עתיק לאמירת תהילים לרפואה, לזכות ולישועת אדם מסוים. ניתן להוסיף את אותיות "קרע שטן".', 'tehilim' ); ?></p>
	</section>

	<section class="tehilim-name-tool">
		<form class="tehilim-name-form" onsubmit="return false;">
			<input type="text" id="tehilim-name-input" maxlength="30" placeholder="<?php esc_attr_e( 'הזינו שם — לדוגמה: דוד', 'tehilim' ); ?>" autocomplete="off">
			<label class="tehilim-name-krs">
				<input type="checkbox" id="tehilim-name-krs" checked>
				<?php esc_html_e( 'להוסיף קרע שטן', 'tehilim' ); ?>
			</label>
			<button type="button" id="tehilim-name-go" class="btn-create-submit"><?php esc_html_e( 'הצגת הפרקים', 'tehilim' ); ?></button>
		</form>

		<div id="tehilim-name-output" class="tehilim-name-output" hidden></div>
		<p id="tehilim-name-hint" class="tehilim-name-hint"><?php esc_html_e( 'התוצאה תופיע כאן — פסוקי תהילים לפי אותיות השם.', 'tehilim' ); ?></p>
	</section>
</div>

<script>
( function() {
	var textUrl = ( window.tehilim && window.tehilim.text_url ) || '';
	var input   = document.getElementById( 'tehilim-name-input' );
	var krsBox  = document.getElementById( 'tehilim-name-krs' );
	var goBtn   = document.getElementById( 'tehilim-name-go' );
	var out     = document.getElementById( 'tehilim-name-output' );
	var hint    = document.getElementById( 'tehilim-name-hint' );
	if ( ! goBtn ) { return; }

	// Aleph-bet order (1..22); finals map to their base letters
	var ALEPHBET = 'אבגדהוזחטיכלמנסעפצקרשת';
	var FINALS   = { 'ך': 'כ', 'ם': 'מ', 'ן': 'נ', 'ף': 'פ', 'ץ': 'צ' };

	var psalms119 = null;

	function letterIndex( ch ) {
		if ( FINALS[ ch ] ) { ch = FINALS[ ch ]; }
		var i = ALEPHBET.indexOf( ch );
		return i === -1 ? -1 : i + 1; // 1..22
	}

	function versesForLetter( idx ) {
		// Psalm 119 = 8 verses per letter, in order
		var start = ( idx - 1 ) * 8;
		return psalms119.slice( start, start + 8 );
	}

	function render( name, withKrs ) {
		out.textContent = '';
		var letters = [];
		for ( var i = 0; i < name.length; i++ ) {
			var idx = letterIndex( name[ i ] );
			if ( idx >= 1 && idx <= 22 ) { letters.push( { ch: name[ i ], idx: idx } ); }
		}
		if ( withKrs ) {
			'קרעשטן'.split( '' ).forEach( function( ch ) {
				var idx = letterIndex( ch );
				if ( idx >= 1 && idx <= 22 ) { letters.push( { ch: ch, idx: idx } ); }
			} );
		}
		if ( ! letters.length ) {
			hint.textContent = 'לא זוהו אותיות עבריות בשם. נסו שוב.';
			out.hidden = true;
			hint.hidden = false;
			return;
		}

		letters.forEach( function( l ) {
			var block = document.createElement( 'div' );
			block.className = 'tehilim-name-block';
			var h = document.createElement( 'div' );
			h.className = 'tehilim-name-letter';
			h.textContent = 'אות ' + l.ch;
			block.appendChild( h );
			var body = document.createElement( 'div' );
			body.className = 'prayer-body';
			versesForLetter( l.idx ).forEach( function( v ) {
				var row = document.createElement( 'div' );
				row.className = 'chapter-verse';
				row.textContent = v;
				body.appendChild( row );
			} );
			block.appendChild( body );
			out.appendChild( block );
		} );

		hint.hidden = true;
		out.hidden = false;
		out.scrollIntoView( { behavior: 'smooth', block: 'start' } );
	}

	function run() {
		var name = ( input.value || '' ).trim();
		if ( ! name ) { input.focus(); return; }

		if ( psalms119 ) { render( name, krsBox.checked ); return; }

		hint.textContent = 'טוען את פרק קי״ט…';
		fetch( textUrl ).then( function( r ) { return r.json(); } ).then( function( data ) {
			psalms119 = data[ 118 ]; // chapter 119 (0-indexed)
			if ( ! psalms119 || psalms119.length < 176 ) { throw new Error( 'bad' ); }
			render( name, krsBox.checked );
		} ).catch( function() {
			hint.textContent = 'לא הצלחנו לטעון את נוסח הפרק. נסו לרענן את העמוד.';
		} );
	}

	goBtn.addEventListener( 'click', run );
	input.addEventListener( 'keydown', function( e ) { if ( e.key === 'Enter' ) { run(); } } );
} )();
</script>

<?php
get_footer();
