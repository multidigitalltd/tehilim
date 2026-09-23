/**
 * Savta al HaSafsal — every behaviour of the design, in vanilla JS.
 *
 * Markup contract (data attributes):
 *   [data-reveal]           fade/slide in on first scroll into view
 *   [data-draw] svg path    stroke draws itself on first scroll into view
 *   [data-window]           soft parallax drift (±14px) of the image frames
 *   [data-progress-track]   "How it works" line that fills with scroll
 *   [data-faq]              accordion, one open at a time
 *   [data-cal]              appointment picker, built from window.svConfig
 *   [data-sv-form]          the booking form, upgraded to fetch
 */
( function () {
	'use strict';

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var config = window.svConfig || {};

	/* ------------------------------------------------ 1. draw-on-scroll */

	function prepDraw() {
		document.querySelectorAll( '[data-draw] svg path' ).forEach( function ( path ) {
			var len = path.getTotalLength ? path.getTotalLength() || 1 : 1;
			path.style.strokeDasharray = len;
			if ( ! path._prepped ) {
				path._prepped = true;
				path.style.strokeDashoffset = reduce ? 0 : len;
				var idx = Array.prototype.indexOf.call( path.parentNode.querySelectorAll( 'path' ), path );
				path.style.transition = 'stroke-dashoffset 1.1s cubic-bezier(.4,0,.2,1) ' + ( idx * 0.16 ) + 's';
			} else if ( path._done ) {
				path.style.strokeDashoffset = '0';
			}
		} );
	}

	/* ---------------------------------------------- 2. reveal + trigger */

	function finish( el ) {
		if ( el._done ) {
			return;
		}
		el._done = true;
		if ( el.hasAttribute( 'data-reveal' ) ) {
			el.classList.add( 'is-in' );
		}
		if ( el.hasAttribute( 'data-draw' ) ) {
			el.querySelectorAll( 'path' ).forEach( function ( p ) {
				p._done = true;
				p.style.strokeDashoffset = '0';
			} );
		}
	}

	function initReveal() {
		var els = document.querySelectorAll( '[data-reveal], [data-draw]' );
		if ( reduce || ! ( 'IntersectionObserver' in window ) ) {
			els.forEach( finish );
			return;
		}
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( e.isIntersecting ) {
					finish( e.target );
					io.unobserve( e.target );
				}
			} );
		}, { rootMargin: '0px 0px -10% 0px', threshold: 0.12 } );
		els.forEach( function ( el ) {
			io.observe( el );
		} );
		// Safety net for fast scrolling and anchor jumps.
		window.addEventListener( 'scroll', function () {
			var vh = window.innerHeight;
			els.forEach( function ( el ) {
				if ( ! el._done && el.getBoundingClientRect().top < vh * 0.9 ) {
					finish( el );
					io.unobserve( el );
				}
			} );
		}, { passive: true } );
	}

	/* -------------------------------------------------- 3. parallax */

	function parallax() {
		if ( reduce ) {
			return;
		}
		var vh = window.innerHeight;
		document.querySelectorAll( '[data-window]' ).forEach( function ( f ) {
			var r = f.getBoundingClientRect();
			if ( r.bottom < -300 || r.top > vh + 300 ) {
				return;
			}
			var p = Math.max( -1, Math.min( 1, ( r.top + r.height / 2 - vh / 2 ) / ( vh / 2 + r.height / 2 ) ) );
			f.style.transform = 'translateY(' + ( p * -14 ).toFixed( 2 ) + 'px)';
		} );
	}

	/* ------------------------------------------ 4. progress line */

	function progress() {
		var track = document.querySelector( '[data-progress-track]' );
		if ( ! track ) {
			return;
		}
		var fill = track.querySelector( '[data-progress-fill]' );
		var r = track.getBoundingClientRect();
		var anchor = window.innerHeight * 0.55;
		var p = Math.max( 0, Math.min( 1, ( anchor - r.top ) / r.height ) );
		if ( fill ) {
			fill.style.height = ( p * r.height ).toFixed( 1 ) + 'px';
		}
		track.querySelectorAll( '[data-progress-step]' ).forEach( function ( step ) {
			var dot = step.querySelector( '[data-progress-dot]' );
			if ( dot ) {
				dot.classList.toggle( 'is-on', step.getBoundingClientRect().top + 44 < anchor );
			}
		} );
	}

	/* --------------------------------------------- 5. FAQ accordion */

	function initFaq() {
		var items = document.querySelectorAll( '[data-faq]' );
		items.forEach( function ( item ) {
			var btn = item.querySelector( '[data-faq-q]' );
			if ( ! btn ) {
				return;
			}
			btn.addEventListener( 'click', function () {
				var wasOpen = item.classList.contains( 'is-open' );
				items.forEach( function ( o ) {
					o.classList.remove( 'is-open' );
					var m = o.querySelector( '[data-faq-mark]' );
					if ( m ) {
						m.textContent = '+';
					}
					var q = o.querySelector( '[data-faq-q]' );
					if ( q ) {
						q.setAttribute( 'aria-expanded', 'false' );
					}
				} );
				if ( ! wasOpen ) {
					item.classList.add( 'is-open' );
					var mark = item.querySelector( '[data-faq-mark]' );
					if ( mark ) {
						mark.textContent = '−';
					}
					btn.setAttribute( 'aria-expanded', 'true' );
				}
			} );
		} );

		// Arrow keys move between questions, as in a native accordion.
		var buttons = Array.prototype.slice.call( document.querySelectorAll( '[data-faq-q]' ) );
		buttons.forEach( function ( btn, i ) {
			btn.addEventListener( 'keydown', function ( e ) {
				var next = null;
				if ( e.key === 'ArrowDown' ) {
					next = buttons[ ( i + 1 ) % buttons.length ];
				} else if ( e.key === 'ArrowUp' ) {
					next = buttons[ ( i - 1 + buttons.length ) % buttons.length ];
				} else if ( e.key === 'Home' ) {
					next = buttons[ 0 ];
				} else if ( e.key === 'End' ) {
					next = buttons[ buttons.length - 1 ];
				}
				if ( next ) {
					e.preventDefault();
					next.focus();
				}
			} );
		} );
	}

	/* ------------------------------------------- 6. appointment picker */

	function pad( n ) {
		return ( n < 10 ? '0' : '' ) + n;
	}

	function nextDays( count, days ) {
		var out = [];
		var d = new Date();
		d.setHours( 0, 0, 0, 0 );
		d.setDate( d.getDate() + 1 );
		var guard = 0;
		while ( out.length < count && guard < 120 ) {
			if ( days.indexOf( d.getDay() ) !== -1 ) {
				out.push( {
					day: d.getDay(),
					label: ( config.dayNames || [] )[ d.getDay() ] + ', ' + d.getDate() + '.' + ( d.getMonth() + 1 ),
					iso: d.getFullYear() + '-' + pad( d.getMonth() + 1 ) + '-' + pad( d.getDate() )
				} );
			}
			d.setDate( d.getDate() + 1 );
			guard++;
		}
		return out;
	}

	function initCalendar() {
		var root = document.querySelector( '[data-cal]' );
		if ( ! root ) {
			return;
		}
		var toggle = root.querySelector( '[data-cal-toggle]' );
		var panel = root.querySelector( '[data-cal-panel]' );
		var list = root.querySelector( '[data-cal-days]' );
		var label = root.querySelector( '[data-cal-label]' );
		var toggleText = root.querySelector( '[data-cal-toggle-text]' );
		var hidden = root.querySelector( '[data-cal-input]' );
		var status = root.querySelector( '[data-cal-status]' );
		var labels = config.labels || {};
		var slots = config.slots || [];
		var days = config.days || [ 1, 2 ];
		var booked = config.booked || [];
		var selected = null;

		if ( ! toggle || ! panel || ! list || ! slots.length || ! days.length ) {
			if ( root ) {
				root.hidden = true;
			}
			return;
		}

		function render() {
			list.innerHTML = '';
			nextDays( config.count || 4, days ).forEach( function ( day ) {
				var wrap = document.createElement( 'div' );
				wrap.className = 'sv-cal__day';
				var h = document.createElement( 'p' );
				h.className = 'sv-cal__day-label';
				h.textContent = day.label;
				var row = document.createElement( 'div' );
				row.className = 'sv-cal__slots';
				slots.forEach( function ( t ) {
					var value = day.iso + ' ' + t;
					var text = day.label + ' · ' + t;
					var b = document.createElement( 'button' );
					b.type = 'button';
					b.className = 'sv-cal__slot';
					b.textContent = t;
					b.setAttribute( 'aria-label', text );
					b.setAttribute( 'aria-pressed', selected === value ? 'true' : 'false' );
					if ( booked.indexOf( value ) !== -1 ) {
						b.disabled = true;
						b.setAttribute( 'aria-label', text + ' — ' + ( labels.taken || '' ) );
					}
					b.addEventListener( 'click', function () {
						selected = value;
						if ( hidden ) {
							hidden.value = value;
						}
						if ( label ) {
							label.textContent = text;
						}
						if ( status && labels.picked ) {
							status.textContent = labels.picked.replace( '%s', text );
						}
						close();
						render();
						toggle.focus();
					} );
					row.appendChild( b );
				} );
				wrap.appendChild( h );
				wrap.appendChild( row );
				list.appendChild( wrap );
			} );
		}

		function open() {
			panel.hidden = false;
			if ( toggleText ) {
				toggleText.textContent = labels.close || '';
			}
			toggle.setAttribute( 'aria-expanded', 'true' );
		}

		function close() {
			panel.hidden = true;
			if ( toggleText ) {
				toggleText.textContent = labels.open || '';
			}
			toggle.setAttribute( 'aria-expanded', 'false' );
		}

		toggle.addEventListener( 'click', function () {
			if ( panel.hidden ) {
				open();
			} else {
				close();
			}
		} );

		root.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! panel.hidden ) {
				close();
				toggle.focus();
			}
		} );

		render();
		close();
	}

	/* ----------------------------------- 7. frames take the image ratio */

	function fitWindows() {
		document.querySelectorAll( '[data-window] img[data-fit]' ).forEach( function ( img ) {
			function apply() {
				if ( img.naturalWidth && img.naturalHeight ) {
					var inner = img.closest( '.sv-window__inner' );
					( inner || img ).style.aspectRatio = ( img.naturalWidth / img.naturalHeight ).toFixed( 4 );
				}
			}
			if ( img.complete ) {
				apply();
			} else {
				img.addEventListener( 'load', apply );
			}
		} );
	}

	/* ------------------------------------------------- 8. booking form */

	function initForm() {
		var form = document.querySelector( '[data-sv-form]' );
		if ( ! form || ! window.fetch || ! config.ajaxUrl ) {
			return;
		}
		var status = form.querySelector( '[data-sv-form-status]' );
		var submit = form.querySelector( '[data-sv-submit]' );
		var nonceInput = form.querySelector( '[data-sv-nonce]' );
		var messages = config.messages || {};
		var freshNonce = false;

		// A page served from a full-page cache may carry a stale nonce; fetch a fresh one on first interaction.
		function refreshNonce() {
			if ( freshNonce ) {
				return;
			}
			freshNonce = true;
			window.fetch( config.ajaxUrl + '?action=savta_nonce', { credentials: 'same-origin' } )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( data ) {
					if ( data && data.success && data.data && data.data.nonce && nonceInput ) {
						nonceInput.value = data.data.nonce;
					}
				} )
				.catch( function () {
					// The embedded nonce is used as-is.
				} );
		}
		form.addEventListener( 'focusin', refreshNonce, { once: true } );

		function say( text, ok ) {
			if ( ! status ) {
				return;
			}
			status.textContent = text;
			status.hidden = ! text;
			status.classList.toggle( 'is-ok', !! ok );
		}

		function markInvalid( name, invalid ) {
			var field = form.querySelector( '[name="' + name + '"]' );
			if ( field ) {
				if ( invalid ) {
					field.setAttribute( 'aria-invalid', 'true' );
				} else {
					field.removeAttribute( 'aria-invalid' );
				}
			}
			return field;
		}

		function validate() {
			var first = null;
			[ 'name', 'phone', 'age', 'consent' ].forEach( function ( name ) {
				markInvalid( name, false );
			} );
			var name = form.querySelector( '[name="name"]' );
			var phone = form.querySelector( '[name="phone"]' );
			var age = form.querySelector( '[name="age"]' );
			var consent = form.querySelector( '[name="consent"]' );
			if ( name && ! name.value.trim() ) {
				first = first || { field: markInvalid( 'name', true ), code: 'name' };
			}
			if ( phone && phone.value.replace( /\D/g, '' ).length < 7 ) {
				first = first || { field: markInvalid( 'phone', true ), code: 'phone' };
			}
			if ( age && age.value && ( Number( age.value ) < 16 || Number( age.value ) > 120 ) ) {
				first = first || { field: markInvalid( 'age', true ), code: 'age' };
			}
			if ( consent && ! consent.checked ) {
				first = first || { field: markInvalid( 'consent', true ), code: 'consent' };
			}
			return first;
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var problem = validate();
			if ( problem ) {
				say( messages[ problem.code ] || '', false );
				if ( problem.field ) {
					problem.field.focus();
				}
				return;
			}

			say( messages.sending || '', true );
			if ( submit ) {
				submit.disabled = true;
			}

			var body = new window.FormData( form );
			body.set( 'action', 'savta_lead' );

			window.fetch( config.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( data ) {
					var payload = ( data && data.data ) || {};
					if ( data && data.success ) {
						var tpl = document.querySelector( '[data-sv-thanks-template]' );
						if ( tpl && tpl.content ) {
							var thanks = tpl.content.firstElementChild.cloneNode( true );
							form.parentNode.replaceChild( thanks, form );
							thanks.focus();
						} else {
							say( payload.message || messages.ok || '', true );
						}
						return;
					}
					say( payload.message || messages.server || '', false );
					if ( payload.code === 'nonce' ) {
						freshNonce = false;
						refreshNonce();
					}
					var field = form.querySelector( '[name="' + payload.code + '"]' );
					if ( field ) {
						field.setAttribute( 'aria-invalid', 'true' );
						field.focus();
					}
				} )
				.catch( function () {
					say( messages.network || '', false );
				} )
				.then( function () {
					if ( submit && submit.isConnected ) {
						submit.disabled = false;
					}
				} );
		} );
	}

	/* ------------------------------------------- 9. thank-you focus */

	function focusOutcome() {
		var thanks = document.querySelector( '[data-sv-thanks]' );
		if ( thanks && window.location.hash === '#signup' ) {
			thanks.focus();
		}
	}

	/* --------------------------------------------------------- boot */

	function onScroll() {
		parallax();
		progress();
	}

	function init() {
		prepDraw();
		initReveal();
		fitWindows();
		initFaq();
		initCalendar();
		initForm();
		focusOutcome();
		onScroll();
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', function () {
			prepDraw();
			onScroll();
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
