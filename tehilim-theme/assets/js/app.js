/**
 * Tehilim App — Reader, Live Stats, Sharing, Ambassador Join
 */

( function() {
	'use strict';

	var CHAPTERS = 150;

	var App = {
		apiUrl: ( window.tehilim && window.tehilim.api_url ) || '/wp-json/tehilim/v1/',
		nonce: ( window.tehilim && window.tehilim.nonce ) || '',
		turnstileSiteKey: ( window.tehilim && window.tehilim.turnstile_site_key ) || '',
		turnstileWidgetId: null,
		currentChapter: 0,
		chapterCache: {},
		pollTimer: null,

		init: function() {
			this.setupEventListeners();
			this.initReader();
			this.startStatsPolling();
			if ( this.turnstileSiteKey && document.querySelector( '.btn-say-chapter' ) ) {
				this.loadTurnstile();
			}
		},

		/* ============ Utilities ============ */

		// Hebrew numeral (gematria) for 1..150, e.g. 1→א׳ 15→ט״ו 100→ק׳ 103→ק״ג 150→ק״נ
		hebrewNumeral: function( n ) {
			var letters = [];
			var hundreds = [ '', 'ק' ];
			var tens = [ '', 'י', 'כ', 'ל', 'מ', 'נ', 'ס', 'ע', 'פ', 'צ' ];
			var ones = [ '', 'א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ז', 'ח', 'ט' ];

			var h = Math.floor( n / 100 );
			var rest = n % 100;

			if ( h ) { letters.push( hundreds[ h ] ); }

			if ( rest === 15 ) {
				letters.push( 'ט', 'ו' );
			} else if ( rest === 16 ) {
				letters.push( 'ט', 'ז' );
			} else {
				var t = Math.floor( rest / 10 );
				var o = rest % 10;
				if ( t ) { letters.push( tens[ t ] ); }
				if ( o ) { letters.push( ones[ o ] ); }
			}

			if ( letters.length === 1 ) {
				return letters[ 0 ] + '׳'; // geresh
			}
			// gershayim before the last letter
			return letters.slice( 0, -1 ).join( '' ) + '״' + letters[ letters.length - 1 ];
		},

		formatNumber: function( n ) {
			return Number( n || 0 ).toLocaleString( 'en-US' );
		},

		/* ============ Event wiring ============ */

		setupEventListeners: function() {
			var self = this;

			document.addEventListener( 'click', function( e ) {
				var say = e.target.closest( '.btn-say-chapter' );
				if ( say ) {
					e.preventDefault();
					self.handleRecitation( say );
					return;
				}

				var share = e.target.closest( '.btn-share' );
				if ( share ) {
					e.preventDefault();
					self.handleShare( share );
					return;
				}

				var random = e.target.closest( '.btn-random' );
				if ( random ) {
					e.preventDefault();
					self.loadChapter( 1 + Math.floor( Math.random() * CHAPTERS ) );
					return;
				}

				var next = e.target.closest( '.btn-next' );
				if ( next && document.querySelector( '.reader-card' ) ) {
					e.preventDefault();
					self.loadChapter( ( self.currentChapter % CHAPTERS ) + 1 );
					return;
				}

				var pick = e.target.closest( '.btn-pick' );
				if ( pick ) {
					e.preventDefault();
					self.pickChapter();
					return;
				}

				var join = e.target.closest( '.btn-ambassador-cta, .btn-amb-join' );
				if ( join ) {
					e.preventDefault();
					self.toggleJoinForm( join );
				}
			} );
		},

		/* ============ Reader ============ */

		initReader: function() {
			var btn = document.querySelector( '.btn-say-chapter' );
			if ( ! btn || ! document.querySelector( '.reader-card' ) ) {
				return;
			}

			var campaignId = btn.dataset.campaignId;
			var self = this;

			// Ask the server for the communal next chapter, then render it
			fetch( this.apiUrl + 'campaigns/' + campaignId + '/next-chapter' )
				.then( function( r ) { return r.ok ? r.json() : null; } )
				.then( function( data ) {
					if ( data && data.chapter_number ) {
						self.updateStatsUI( data.stats );
						self.loadChapter( data.chapter_number );
					} else {
						self.loadChapter( parseInt( btn.dataset.chapterNumber, 10 ) || 1 );
					}
				} )
				.catch( function() {
					self.loadChapter( parseInt( btn.dataset.chapterNumber, 10 ) || 1 );
				} );
		},

		pickChapter: function() {
			var input = window.prompt( 'בחרו פרק (1–150):', String( this.currentChapter || 1 ) );
			if ( input === null ) { return; }
			var n = parseInt( input, 10 );
			if ( n >= 1 && n <= CHAPTERS ) {
				this.loadChapter( n );
			}
		},

		loadChapter: function( n ) {
			var self = this;
			n = Math.min( CHAPTERS, Math.max( 1, n ) );
			this.currentChapter = n;

			// Sync the say-button + title immediately
			document.querySelectorAll( '.btn-say-chapter' ).forEach( function( b ) {
				b.dataset.chapterNumber = String( n );
				b.disabled = false;
			} );

			var title = document.querySelector( '.reader-chapter-title' );
			if ( title ) {
				title.textContent = 'תהילים · פרק ' + this.hebrewNumeral( n );
			}

			var container = document.querySelector( '.chapter-text' );
			if ( ! container ) { return; }

			if ( this.chapterCache[ n ] ) {
				this.renderVerses( container, this.chapterCache[ n ] );
				return;
			}

			container.textContent = 'טוען את הפרק…';

			// Public-domain Psalms text (with nikud) from the Sefaria API
			fetch( 'https://www.sefaria.org/api/texts/Psalms.' + n + '?context=0&commentary=0' )
				.then( function( r ) {
					if ( ! r.ok ) { throw new Error( 'HTTP ' + r.status ); }
					return r.json();
				} )
				.then( function( data ) {
					var verses = ( data && data.he ) || [];
					if ( ! verses.length ) { throw new Error( 'empty' ); }
					// Strip any markup Sefaria embeds; keep plain nikud text
					verses = verses.map( function( v ) {
						var div = document.createElement( 'div' );
						div.innerHTML = v;
						return div.textContent.replace( /\s+/g, ' ' ).trim();
					} );
					self.chapterCache[ n ] = verses;
					if ( self.currentChapter === n ) {
						self.renderVerses( container, verses );
					}
				} )
				.catch( function() {
					container.textContent = '';
					var msg = document.createElement( 'div' );
					msg.className = 'chapter-verse';
					msg.textContent = 'לא הצלחנו לטעון את נוסח הפרק. אפשר לומר את פרק ' + self.hebrewNumeral( n ) + ' מתוך ספר תהילים ולסמן שאמרתם.';
					container.appendChild( msg );
				} );
		},

		renderVerses: function( container, verses ) {
			var self = this;
			container.textContent = '';
			verses.forEach( function( text, i ) {
				var row = document.createElement( 'div' );
				row.className = 'chapter-verse';
				var num = document.createElement( 'span' );
				num.className = 'chapter-verse-num';
				num.textContent = self.hebrewNumeral( i + 1 ).replace( /[׳״]/g, '' );
				row.appendChild( num );
				row.appendChild( document.createTextNode( text ) );
				container.appendChild( row );
			} );
			container.scrollTop = 0;
		},

		/* ============ Recitation ============ */

		handleRecitation: function( button ) {
			var self = this;
			var campaignId = parseInt( button.dataset.campaignId, 10 );
			var chapterNumber = parseInt( button.dataset.chapterNumber, 10 );
			var ambassadorId = button.dataset.ambassadorId ? parseInt( button.dataset.ambassadorId, 10 ) : null;
			var nameInput = document.querySelector( '.reader-name-input' );

			if ( ! campaignId || ! chapterNumber ) { return; }

			var original = button.innerHTML;
			button.disabled = true;
			button.innerHTML = 'רושמים…';

			var body = {
				campaign_id: campaignId,
				chapter_number: chapterNumber,
				ambassador_id: ambassadorId,
				reciter_name: nameInput ? nameInput.value.trim() : '',
			};

			if ( this.turnstileSiteKey && window.turnstile && this.turnstileWidgetId !== null ) {
				body.cf_turnstile_response = window.turnstile.getResponse( this.turnstileWidgetId ) || '';
			}

			this.apiPost( 'recitations', body )
				.then( function( data ) {
					self.showToast( 'פרק ' + self.hebrewNumeral( chapterNumber ) + ' נרשם — תודה!' );
					if ( data.stats ) {
						self.updateStatsUI( data.stats );
					}
					if ( window.turnstile && self.turnstileWidgetId !== null ) {
						window.turnstile.reset( self.turnstileWidgetId );
					}
					button.innerHTML = original;
					self.loadChapter( data.chapter_number || ( ( chapterNumber % CHAPTERS ) + 1 ) );
				} )
				.catch( function( err ) {
					button.disabled = false;
					button.innerHTML = original;
					var code = ( err && err.code ) || '';
					var msg = 'לא הצלחנו לרשום את האמירה. נסו שוב.';
					if ( code === 'rate_limit' ) {
						msg = 'הגעתם למגבלת האמירות לשעה. נסו שוב מאוחר יותר.';
					} else if ( code === 'turnstile_failed' ) {
						msg = 'אימות האבטחה נכשל. רעננו את העמוד ונסו שוב.';
					} else if ( code ) {
						msg += ' [' + code + ']';
					}
					self.showToast( msg, true );
				} );
		},

		/**
		 * POST helper: JSON-parse-safe, retries once without the nonce header
		 * if WordPress rejects it as stale (cached pages serve old nonces).
		 */
		apiPost: function( path, body, withNonce ) {
			var self = this;
			var useNonce = ( withNonce !== false ) && !! this.nonce;
			var headers = { 'Content-Type': 'application/json' };
			if ( useNonce ) {
				headers[ 'X-WP-Nonce' ] = this.nonce;
			}

			return fetch( this.apiUrl + path, {
				method: 'POST',
				headers: headers,
				body: JSON.stringify( body ),
			} ).then( function( r ) {
				return r.text().then( function( text ) {
					var data;
					try {
						data = JSON.parse( text );
					} catch ( e ) {
						data = { code: 'http_' + r.status };
					}
					if ( ! r.ok ) {
						if ( useNonce && data && ( data.code === 'rest_cookie_invalid_nonce' || data.code === 'rest_forbidden' ) ) {
							return self.apiPost( path, body, false );
						}
						throw data;
					}
					return data;
				} );
			} );
		},

		/* ============ Live stats ============ */

		startStatsPolling: function() {
			var holder = document.querySelector( '[data-campaign-id]' );
			if ( ! holder || ! document.querySelector( '.reader-card' ) ) {
				return;
			}
			var campaignId = holder.dataset.campaignId;
			var self = this;

			this.pollTimer = window.setInterval( function() {
				if ( document.hidden ) { return; }
				fetch( self.apiUrl + 'campaigns/' + campaignId + '/stats' )
					.then( function( r ) { return r.ok ? r.json() : null; } )
					.then( function( stats ) {
						if ( stats ) { self.updateStatsUI( stats ); }
					} )
					.catch( function() {} );
			}, 10000 );
		},

		updateStatsUI: function( stats ) {
			if ( ! stats ) { return; }
			var fmt = this.formatNumber;

			// Campaign page — progress overview
			var percentEl = document.querySelector( '.progress-overview-percent' );
			if ( percentEl ) { percentEl.textContent = stats.progress_percent + '%'; }

			var fill = document.querySelector( '.progress-track-fill' );
			if ( fill ) { fill.style.width = stats.progress_percent + '%'; }

			var metaSpans = document.querySelectorAll( '.progress-overview-meta span' );
			if ( metaSpans.length >= 2 ) {
				metaSpans[ 0 ].textContent = fmt( stats.books_done ) + ' ספרים הושלמו';
				metaSpans[ 1 ].textContent = fmt( stats.goal_books ) + ' מתוך היעד';
			}

			var nums = document.querySelectorAll( '.campaign-stats .progress-stat-num' );
			if ( nums.length >= 4 ) {
				nums[ 0 ].textContent = fmt( stats.books_done );
				nums[ 1 ].textContent = fmt( stats.total_chapters );
				nums[ 2 ].textContent = fmt( stats.participants );
				nums[ 3 ].textContent = fmt( stats.ambassadors );
			}

			// Reader subtitle
			var sub = document.querySelector( '.reader-head-sub' );
			if ( sub ) {
				sub.textContent = 'ספר #' + stats.current_book + ' פעיל · ' + stats.in_book + '/' + CHAPTERS + ' · נותרו ' + stats.remaining_in_book + ' פרקים';
			}

			// Ambassador page — campaign overview card
			var ambPercent = document.querySelector( '.amb-overview-percent' );
			if ( ambPercent ) { ambPercent.textContent = stats.progress_percent + '%'; }

			var ambFill = document.querySelector( '.amb-overview-track-fill' );
			if ( ambFill ) { ambFill.style.width = stats.progress_percent + '%'; }

			var ambBooks = document.querySelector( '.amb-overview-books' );
			if ( ambBooks ) { ambBooks.textContent = fmt( stats.books_done ) + ' מתוך ' + fmt( stats.goal_books ) + ' ספרים'; }

			var ambNums = document.querySelectorAll( '.amb-overview-stat-num' );
			if ( ambNums.length >= 3 ) {
				ambNums[ 0 ].textContent = fmt( stats.participants );
				ambNums[ 1 ].textContent = fmt( stats.total_chapters );
				ambNums[ 2 ].textContent = fmt( stats.ambassadors );
			}
		},

		/* ============ Ambassador join ============ */

		toggleJoinForm: function( trigger ) {
			var existing = document.querySelector( '.tehilim-join-form' );
			if ( existing ) {
				existing.remove();
				return;
			}

			var holder = document.querySelector( '[data-campaign-id]' );
			var campaignId = holder ? holder.dataset.campaignId : '';
			if ( ! campaignId ) { return; }

			var form = document.createElement( 'form' );
			form.className = 'form-ambassador-join tehilim-join-form';
			form.dataset.campaignId = campaignId;

			var nameInput = document.createElement( 'input' );
			nameInput.type = 'text';
			nameInput.name = 'name';
			nameInput.required = true;
			nameInput.placeholder = 'השם שלכם';
			nameInput.className = 'reader-name-input';

			var emailInput = document.createElement( 'input' );
			emailInput.type = 'email';
			emailInput.name = 'email';
			emailInput.required = true;
			emailInput.placeholder = 'אימייל';
			emailInput.className = 'reader-name-input';

			var submit = document.createElement( 'button' );
			submit.type = 'submit';
			submit.className = 'btn-reader-said';
			submit.textContent = 'שליחת בקשה';

			form.appendChild( nameInput );
			form.appendChild( emailInput );
			form.appendChild( submit );

			var cta = trigger.closest( '.ambassador-cta' ) || trigger.parentElement;
			cta.parentNode.insertBefore( form, cta.nextSibling );
			nameInput.focus();
		},

		/* ============ Sharing ============ */

		handleShare: function( button ) {
			var text = button.dataset.shareText || 'בואו לומר תהילים איתי';
			var url = button.dataset.shareUrl || window.location.href;
			var type = button.dataset.shareType || '';
			var self = this;

			if ( type === 'whatsapp' ) {
				window.open( 'https://wa.me/?text=' + encodeURIComponent( text + '\n' + url ), '_blank', 'noopener' );
				return;
			}

			if ( type === 'copy' || ! navigator.share ) {
				navigator.clipboard.writeText( url ).then( function() {
					self.showToast( 'הקישור הועתק!' );
				} ).catch( function() {
					window.prompt( 'העתיקו את הקישור:', url );
				} );
				return;
			}

			navigator.share( { title: 'קמפיין תהילים', text: text, url: url } ).catch( function() {} );
		},

		/* ============ Toast ============ */

		showToast: function( message, isError ) {
			var toast = document.querySelector( '.tehilim-toast' );
			if ( ! toast ) {
				toast = document.createElement( 'div' );
				toast.className = 'tehilim-toast';
				toast.setAttribute( 'role', 'status' );
				toast.setAttribute( 'aria-live', 'polite' );
				document.body.appendChild( toast );
			}

			toast.textContent = message;
			toast.style.cssText = [
				'position:fixed', 'bottom:26px', 'right:26px', 'z-index:90',
				'display:flex', 'align-items:center', 'gap:9px',
				'padding:13px 20px', 'border-radius:14px',
				'font-family:inherit', 'font-weight:700', 'font-size:14.5px',
				'color:#FFF7F2', 'direction:rtl',
				'background:' + ( isError ? 'linear-gradient(140deg,#8A3C26,#A03C22)' : 'linear-gradient(140deg,#33261A,#4A3524)' ),
				'box-shadow:0 16px 32px -12px rgba(46,35,24,.5)',
				'animation:pop .34s ease both',
			].join( ';' );

			window.clearTimeout( this._toastTimer );
			this._toastTimer = window.setTimeout( function() {
				toast.remove();
			}, 3200 );
		},

		/* ============ Turnstile ============ */

		loadTurnstile: function() {
			var self = this;
			var script = document.createElement( 'script' );
			script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=tehilimTurnstileReady';
			script.async = true;
			script.defer = true;

			window.tehilimTurnstileReady = function() {
				var footer = document.querySelector( '.reader-footer' );
				if ( ! footer || ! window.turnstile ) { return; }
				var slot = document.createElement( 'div' );
				slot.className = 'tehilim-turnstile';
				footer.parentNode.insertBefore( slot, footer );
				self.turnstileWidgetId = window.turnstile.render( slot, {
					sitekey: self.turnstileSiteKey,
					size: 'flexible',
				} );
			};

			document.head.appendChild( script );
		},
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function() { App.init(); } );
	} else {
		App.init();
	}

	window.TehilimApp = App;
} )();
