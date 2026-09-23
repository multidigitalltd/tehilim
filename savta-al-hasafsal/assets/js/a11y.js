/**
 * Accessibility toolbar: toggles classes on <html>, remembers the choices in
 * localStorage, announces every change through a polite live region.
 */
( function () {
	'use strict';

	var root = document.documentElement;
	var widget = document.querySelector( '[data-sv-a11y]' );

	if ( ! widget ) {
		return;
	}

	var trigger = widget.querySelector( '[data-sv-a11y-trigger]' );
	var panel = widget.querySelector( '[data-sv-a11y-panel]' );
	var closeButton = widget.querySelector( '[data-sv-a11y-close]' );
	var resetButton = widget.querySelector( '[data-sv-a11y-reset]' );
	var guide = widget.querySelector( '[data-sv-a11y-guide]' );
	var status = widget.querySelector( '[data-sv-a11y-status]' );
	var options = Array.prototype.slice.call( widget.querySelectorAll( '[data-sv-a11y-option]' ) );

	var STORAGE_KEY = 'sv-a11y';
	var TEXT_STEPS = [ 87.5, 100, 112.5, 125, 137.5, 150, 175, 200 ];
	var DEFAULT_STEP = 1;

	var state = { toggles: {}, text: DEFAULT_STEP };

	function read() {
		try {
			var raw = window.localStorage.getItem( STORAGE_KEY );
			if ( ! raw ) {
				return;
			}
			var parsed = JSON.parse( raw );
			if ( parsed && typeof parsed === 'object' ) {
				state.toggles = parsed.toggles && typeof parsed.toggles === 'object' ? parsed.toggles : {};
				state.text = typeof parsed.text === 'number' && TEXT_STEPS[ parsed.text ] ? parsed.text : DEFAULT_STEP;
			}
		} catch ( error ) {
			// Storage unavailable: the toolbar still works for this visit.
		}
	}

	function write() {
		try {
			window.localStorage.setItem( STORAGE_KEY, JSON.stringify( state ) );
		} catch ( error ) {
			// Persistence is a convenience, not a requirement.
		}
	}

	function announce( message ) {
		if ( status ) {
			status.textContent = message;
		}
	}

	function apply() {
		options.forEach( function ( button ) {
			if ( button.getAttribute( 'data-sv-a11y-type' ) !== 'toggle' ) {
				return;
			}
			var key = button.getAttribute( 'data-sv-a11y-option' );
			var on = !! state.toggles[ key ];
			button.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			root.classList.toggle( 'sv-a11y-' + key, on );
		} );

		root.style.fontSize = state.text === DEFAULT_STEP ? '' : TEXT_STEPS[ state.text ] + '%';

		if ( guide ) {
			guide.hidden = ! state.toggles.guide;
		}
	}

	function reset() {
		state = { toggles: {}, text: DEFAULT_STEP };
		apply();
		write();
		announce( 'הגדרות הנגישות אופסו.' );
	}

	function isOpen() {
		return trigger.getAttribute( 'aria-expanded' ) === 'true';
	}

	function setPanel( open ) {
		trigger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		panel.hidden = ! open;
		if ( open ) {
			var first = panel.querySelector( 'button' );
			if ( first ) {
				first.focus();
			}
		}
	}

	trigger.addEventListener( 'click', function () {
		setPanel( ! isOpen() );
	} );

	if ( closeButton ) {
		closeButton.addEventListener( 'click', function () {
			setPanel( false );
			trigger.focus();
		} );
	}

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && isOpen() ) {
			setPanel( false );
			trigger.focus();
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		if ( isOpen() && ! widget.contains( e.target ) ) {
			setPanel( false );
		}
	} );

	options.forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var key = button.getAttribute( 'data-sv-a11y-option' );
			var type = button.getAttribute( 'data-sv-a11y-type' );

			if ( type === 'step' ) {
				var next = state.text + ( key === 'text-bigger' ? 1 : -1 );
				if ( next < 0 || next >= TEXT_STEPS.length ) {
					announce( key === 'text-bigger' ? 'הגעתם לגודל הטקסט המרבי.' : 'הגעתם לגודל הטקסט המזערי.' );
					return;
				}
				state.text = next;
				announce( 'גודל הטקסט: ' + TEXT_STEPS[ next ] + '%' );
			} else {
				state.toggles[ key ] = ! state.toggles[ key ];
				announce( button.textContent.trim() + ( state.toggles[ key ] ? ' — מופעל' : ' — כבוי' ) );
			}

			apply();
			write();
		} );
	} );

	if ( resetButton ) {
		resetButton.addEventListener( 'click', reset );
	}

	if ( guide ) {
		document.addEventListener( 'mousemove', function ( e ) {
			if ( state.toggles.guide ) {
				guide.style.top = ( e.clientY - 5 ) + 'px';
			}
		}, { passive: true } );
	}

	read();
	apply();
} )();
