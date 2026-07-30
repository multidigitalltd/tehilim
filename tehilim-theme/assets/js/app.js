/**
 * Tehilim App — Reader, Live Stats, Sharing, Ambassador Join
 */

( function() {
	'use strict';

	var CHAPTERS = 150;

	var App = {
		apiUrl: ( window.tehilim && window.tehilim.api_url ) || '/wp-json/tehilim/v1/',
		nonce: ( window.tehilim && window.tehilim.nonce ) || '',
		textUrl: ( window.tehilim && window.tehilim.text_url ) || '',
		turnstileSiteKey: ( window.tehilim && window.tehilim.turnstile_site_key ) || '',
		turnstileWidgetId: null,
		currentChapter: 0,
		textData: null,
		textPromise: null,
		available: null,
		pollTimer: null,

		init: function() {
			this.setupEventListeners();
			this.initReader();
			this.startStatsPolling();
			this.initPraiseVerses();
			this.initFaq();
			this.initCarousel();
			this.initSiteStats();
			if ( this.turnstileSiteKey && document.querySelector( '.btn-say-chapter' ) ) {
				this.loadTurnstile();
			}
		},

		/* Homepage counters: count-up animation + live refresh every 12s */
		initSiteStats: function() {
			var els = document.querySelectorAll( '[data-site-stat]' );
			if ( ! els.length ) { return; }
			var self = this;
			var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

			function currentValue( el ) {
				return parseInt( String( el.textContent ).replace( /[^\d]/g, '' ), 10 ) || 0;
			}

			// Smooth count-up from the element's current value to the target
			function countTo( el, target, duration ) {
				var from = currentValue( el );
				if ( from === target ) { return; }
				if ( reduced ) {
					el.textContent = self.formatNumber( target );
					return;
				}
				if ( el._countRaf ) { window.cancelAnimationFrame( el._countRaf ); }
				var start = null;
				function step( ts ) {
					if ( ! start ) { start = ts; }
					var p = Math.min( 1, ( ts - start ) / duration );
					var eased = 1 - Math.pow( 1 - p, 3 ); // easeOutCubic
					el.textContent = self.formatNumber( Math.round( from + ( target - from ) * eased ) );
					if ( p < 1 ) {
						el._countRaf = window.requestAnimationFrame( step );
					}
				}
				el._countRaf = window.requestAnimationFrame( step );
			}

			// Entry animation: run up from 0 when a counter first scrolls into view
			var pending = [];
			els.forEach( function( el ) {
				el.dataset.statTarget = String( currentValue( el ) );
				el.textContent = '0';
				pending.push( el );
			} );

			function reveal( el ) {
				countTo( el, parseInt( el.dataset.statTarget, 10 ) || 0, 1600 );
			}

			if ( 'IntersectionObserver' in window ) {
				var io = new IntersectionObserver( function( entries ) {
					entries.forEach( function( entry ) {
						if ( entry.isIntersecting ) {
							reveal( entry.target );
							io.unobserve( entry.target );
						}
					} );
				}, { threshold: 0.4 } );
				pending.forEach( function( el ) { io.observe( el ); } );
			} else {
				pending.forEach( reveal );
			}

			function apply( stats ) {
				els.forEach( function( el ) {
					var key = el.dataset.siteStat;
					if ( stats && typeof stats[ key ] !== 'undefined' ) {
						el.dataset.statTarget = String( stats[ key ] );
						countTo( el, stats[ key ], 900 );
					}
				} );
			}

			function refresh() {
				var url = self.apiUrl + 'site-stats';
				url += ( url.indexOf( '?' ) !== -1 ? '&' : '?' ) + '_=' + new Date().getTime();
				fetch( url, { cache: 'no-store' } )
					.then( function( r ) { return r.ok ? r.json() : null; } )
					.then( apply )
					.catch( function() {} );
			}

			window.setTimeout( refresh, 2200 ); // let the entry animation finish first
			window.setInterval( function() {
				if ( ! document.hidden ) { refresh(); }
			}, 12000 );
		},

		/* FAQ accordion: one item open at a time, first open by default */
		initFaq: function() {
			var items = document.querySelectorAll( '.faq-item' );
			if ( ! items.length ) { return; }
			items[ 0 ].classList.add( 'open' );
			document.addEventListener( 'click', function( e ) {
				var q = e.target.closest( '.faq-question' );
				if ( ! q ) { return; }
				var item = q.closest( '.faq-item' );
				var wasOpen = item.classList.contains( 'open' );
				items.forEach( function( it ) { it.classList.remove( 'open' ); } );
				if ( ! wasOpen ) { item.classList.add( 'open' ); }
			} );
		},

		/* Testimonials carousel arrows */
		initCarousel: function() {
			var wrap = document.querySelector( '.testimonials-grid' );
			var btns = document.querySelectorAll( '.carousel-buttons .carousel-btn' );
			if ( ! wrap || btns.length < 2 ) { return; }

			var idx = 0;
			function goTo( delta ) {
				var cards = wrap.children;
				if ( ! cards.length ) { return; }
				idx = Math.max( 0, Math.min( cards.length - 1, idx + delta ) );
				var offset = cards[ idx ].getBoundingClientRect().left - wrap.getBoundingClientRect().left;
				wrap.scrollBy( { left: offset, behavior: 'smooth' } );
			}

			// RTL: the right-pointing arrow goes back, left-pointing goes forward
			btns[ 0 ].addEventListener( 'click', function() { goTo( 1 ); } );
			btns[ 1 ].addEventListener( 'click', function() { goTo( -1 ); } );
		},

		/* Rotate the "praise of Tehilim" verses in the no-image hero */
		initPraiseVerses: function() {
			var verses = document.querySelectorAll( '.hero-verse' );
			if ( verses.length < 2 ) { return; }
			if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				return; // keep the first verse static
			}
			var idx = 0;
			window.setInterval( function() {
				if ( document.hidden ) { return; }
				verses[ idx ].classList.remove( 'active' );
				idx = ( idx + 1 ) % verses.length;
				verses[ idx ].classList.add( 'active' );
			}, 6000 );
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

		// Stable anonymous visitor id — lets unnamed reciters count as unique people
		visitorKey: function() {
			try {
				var k = window.localStorage.getItem( 'tehilim_vk' );
				if ( ! k ) {
					var bytes = new Uint8Array( 16 );
					( window.crypto || window.msCrypto ).getRandomValues( bytes );
					k = Array.prototype.map.call( bytes, function( b ) {
						return ( '0' + b.toString( 16 ) ).slice( -2 );
					} ).join( '' );
					window.localStorage.setItem( 'tehilim_vk', k );
				}
				return k;
			} catch ( e ) {
				return '';
			}
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

				var shareModal = e.target.closest( '.btn-share-modal' );
				if ( shareModal ) {
					e.preventDefault();
					self.openShareModal( shareModal.dataset.shareUrl || window.location.href, shareModal.dataset.shareText || '' );
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
					self.loadChapter( self.randomAvailable() );
					return;
				}

				var next = e.target.closest( '.btn-next' );
				if ( next && document.querySelector( '.reader-card' ) ) {
					e.preventDefault();
					self.loadChapter( self.nextAvailable( self.currentChapter ) );
					return;
				}

				var pick = e.target.closest( '.btn-pick' );
				if ( pick ) {
					e.preventDefault();
					self.pickChapter();
					return;
				}

				var joinScroll = e.target.closest( '.btn-amb-join' );
				if ( joinScroll ) {
					e.preventDefault();
					var reader = document.querySelector( '.reader-card' );
					if ( reader ) {
						reader.scrollIntoView( { behavior: 'smooth', block: 'start' } );
						var nameField = reader.querySelector( '.reader-name-input' );
						if ( nameField ) { window.setTimeout( function() { nameField.focus(); }, 600 ); }
					}
					return;
				}

				var join = e.target.closest( '.btn-ambassador-cta' );
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

			// Warm the local Psalms text in parallel with the next-chapter request
			this.loadTextData();

			// Ask the server which chapters are still open in the current book
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

		/* Chapters still open in the current communal book */
		setAvailable: function( list ) {
			if ( Array.isArray( list ) && list.length ) {
				this.available = list.map( Number );
			}
		},

		nextAvailable: function( after ) {
			var list = ( this.available && this.available.length ) ? this.available : null;
			if ( ! list ) { return ( after % CHAPTERS ) + 1; }
			for ( var i = 0; i < list.length; i++ ) {
				if ( list[ i ] > after ) { return list[ i ]; }
			}
			return list[ 0 ]; // wrap around
		},

		randomAvailable: function() {
			var list = ( this.available && this.available.length ) ? this.available : null;
			if ( ! list ) { return 1 + Math.floor( Math.random() * CHAPTERS ); }
			var pool = list.length > 1 ? list.filter( function( c ) { return c !== this.currentChapter; }, this ) : list;
			return pool[ Math.floor( Math.random() * pool.length ) ];
		},

		/* Styled chapter picker: a grid of all 150 chapters in Hebrew numerals,
		   open ones highlighted */
		pickChapter: function() {
			var self = this;
			var existing = document.querySelector( '.tehilim-modal-overlay' );
			if ( existing ) { existing.remove(); }

			var openSet = {};
			if ( this.available && this.available.length ) {
				this.available.forEach( function( c ) { openSet[ c ] = true; } );
			}

			var overlay = document.createElement( 'div' );
			overlay.className = 'tehilim-modal-overlay';

			var modal = document.createElement( 'div' );
			modal.className = 'tehilim-modal tehilim-picker';
			modal.setAttribute( 'role', 'dialog' );
			modal.setAttribute( 'aria-label', 'בחירת פרק' );

			var close = document.createElement( 'button' );
			close.type = 'button';
			close.className = 'tehilim-modal-close';
			close.setAttribute( 'aria-label', 'סגירה' );
			close.textContent = '✕';

			var title = document.createElement( 'div' );
			title.className = 'tehilim-modal-title';
			title.textContent = 'בחירת פרק';

			var sub = document.createElement( 'div' );
			sub.className = 'tehilim-modal-sub';
			sub.textContent = 'הפרקים בזהב כבר נאמרו בספר הנוכחי · השאר עדיין פנויים';

			var grid = document.createElement( 'div' );
			grid.className = 'tehilim-picker-grid';

			function shut() {
				overlay.remove();
				document.removeEventListener( 'keydown', onKey );
			}
			function onKey( ev ) {
				if ( 'Escape' === ev.key ) { shut(); }
			}

			for ( var n = 1; n <= CHAPTERS; n++ ) {
				( function( num ) {
					var cell = document.createElement( 'button' );
					cell.type = 'button';
					cell.className = 'tehilim-picker-cell';
					// A chapter NOT in the open set has already been said this cycle
					if ( ! openSet[ num ] ) { cell.classList.add( 'is-said' ); }
					if ( num === self.currentChapter ) { cell.classList.add( 'is-current' ); }
					cell.textContent = self.hebrewNumeral( num );
					cell.title = 'פרק ' + self.hebrewNumeral( num );
					cell.addEventListener( 'click', function() {
						shut();
						self.loadChapter( num );
						var rb = document.querySelector( '.reader-body' );
						if ( rb ) { rb.scrollIntoView( { behavior: 'smooth', block: 'start' } ); }
					} );
					grid.appendChild( cell );
				} )( n );
			}

			modal.appendChild( close );
			modal.appendChild( title );
			modal.appendChild( sub );
			modal.appendChild( grid );
			overlay.appendChild( modal );
			document.body.appendChild( overlay );

			close.addEventListener( 'click', shut );
			overlay.addEventListener( 'click', function( ev ) {
				if ( ev.target === overlay ) { shut(); }
			} );
			document.addEventListener( 'keydown', onKey );
		},

		/* Local bundled Psalms text (all 150 chapters, menukad) */
		loadTextData: function() {
			var self = this;
			if ( this.textPromise ) { return this.textPromise; }
			this.textPromise = fetch( this.textUrl )
				.then( function( r ) {
					if ( ! r.ok ) { throw new Error( 'HTTP ' + r.status ); }
					return r.json();
				} )
				.then( function( data ) {
					if ( ! Array.isArray( data ) || data.length !== CHAPTERS ) {
						throw new Error( 'bad data' );
					}
					self.textData = data;
					return data;
				} )
				.catch( function( err ) {
					self.textPromise = null; // allow retry
					throw err;
				} );
			return this.textPromise;
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

			if ( this.textData ) {
				this.renderVerses( container, this.textData[ n - 1 ] );
				return;
			}

			container.textContent = 'טוען את הפרק…';

			this.loadTextData()
				.then( function( data ) {
					if ( self.currentChapter === n ) {
						self.renderVerses( container, data[ n - 1 ] );
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

			// Only send fields that carry a value — null/empty optional params
			// can trip REST validation on some setups
			var body = {
				campaign_id: campaignId,
				chapter_number: chapterNumber,
			};
			if ( ambassadorId ) {
				body.ambassador_id = ambassadorId;
			}
			var reciterName = nameInput ? nameInput.value.trim() : '';
			if ( reciterName ) {
				body.reciter_name = reciterName;
			}
			var vk = this.visitorKey();
			if ( vk ) {
				body.visitor_key = vk;
			}

			if ( this.turnstileSiteKey && window.turnstile && this.turnstileWidgetId !== null ) {
				body.cf_turnstile_response = window.turnstile.getResponse( this.turnstileWidgetId ) || '';
			}

			this.apiPost( 'recitations', body )
				.then( function( data ) {
					// Apply fresh stats first — this also refreshes the open-chapters list
					if ( data.stats ) {
						self.updateStatsUI( data.stats );
					}

					// Next chapter: RANDOM from the chapters still open in this book
					var next = self.randomAvailable();
					if ( ! next || next === chapterNumber ) {
						next = data.chapter_number || ( ( chapterNumber % CHAPTERS ) + 1 );
					}

					self.showToast( 'פרק ' + self.hebrewNumeral( chapterNumber ) + ' נרשם — תודה! הפרק הבא שלכם: פרק ' + self.hebrewNumeral( next ) );
					if ( window.turnstile && self.turnstileWidgetId !== null ) {
						window.turnstile.reset( self.turnstileWidgetId );
					}
					button.innerHTML = original;

					// Verification refresh shortly after (beats any proxy-level caching)
					window.setTimeout( function() { self.fetchStats(); }, 1500 );

					// Load the fresh chapter immediately and make the swap obvious:
					// scroll the reader back into view and pulse it
					self.loadChapter( next );
					var readerBody = document.querySelector( '.reader-body' );
					if ( readerBody ) {
						var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
						if ( ! reduced ) {
							readerBody.scrollIntoView( { behavior: 'smooth', block: 'start' } );
							readerBody.classList.remove( 'chapter-swap' );
							void readerBody.offsetWidth; // restart the animation
							readerBody.classList.add( 'chapter-swap' );
						}
					}
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
					} else if ( code === 'db_error' ) {
						msg = 'שגיאת שרת בשמירת האמירה. נסו שוב בעוד רגע.';
					} else if ( code ) {
						msg += ' [' + code + ( err && err.message ? ': ' + err.message : '' ) + ']';
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

		/* One fresh, cache-busted stats fetch (used by polling and after recitations) */
		fetchStats: function() {
			var holder = document.querySelector( '[data-campaign-id]' );
			if ( ! holder ) { return; }
			var self = this;

			var path = 'campaigns/' + holder.dataset.campaignId + '/stats';
			var url = this.apiUrl + path;
			var sep = url.indexOf( '?' ) !== -1 ? '&' : '?';
			url += sep + '_=' + new Date().getTime();

			var ambBtn = document.querySelector( '.btn-say-chapter[data-ambassador-id]' );
			if ( ambBtn ) {
				url += '&ambassador_id=' + ambBtn.dataset.ambassadorId;
			}

			fetch( url, { cache: 'no-store' } )
				.then( function( r ) { return r.ok ? r.json() : null; } )
				.then( function( stats ) {
					if ( stats ) { self.updateStatsUI( stats ); }
				} )
				.catch( function() {} );
		},

		startStatsPolling: function() {
			if ( ! document.querySelector( '[data-campaign-id]' ) || ! document.querySelector( '.reader-card' ) ) {
				return;
			}
			var self = this;
			this.pollTimer = window.setInterval( function() {
				if ( document.hidden ) { return; }
				self.fetchStats();
			}, 10000 );
		},

		updateStatsUI: function( stats ) {
			if ( ! stats ) { return; }
			this.setAvailable( stats.available );
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

			// Ambassador page — personal tiles (פרקים גויסו / אמרו דרכי / דירוג) + ring
			if ( stats.ambassador ) {
				var amb = stats.ambassador;
				var tiles = document.querySelectorAll( '.amb-stat-num' );
				if ( tiles.length >= 3 ) {
					tiles[ 0 ].textContent = fmt( amb.chapters );
					tiles[ 1 ].textContent = fmt( amb.books || 0 );
					tiles[ 2 ].textContent = '';
					tiles[ 2 ].appendChild( document.createTextNode( String( amb.rank ) ) );
					var sm = document.createElement( 'small' );
					sm.textContent = '/' + amb.total_ambassadors;
					tiles[ 2 ].appendChild( sm );
				}

				var ring = document.querySelector( '.amb-ring' );
				if ( ring ) {
					var deg = Math.min( 360, amb.ring_percent * 3.6 );
					ring.style.background = 'conic-gradient(#D9A441 0deg,#C05A3A ' + deg + 'deg,#EFE3CF ' + deg + 'deg)';
					var rp = ring.querySelector( '.amb-ring-percent' );
					if ( rp ) { rp.textContent = amb.ring_percent + '%'; }
				}

				var goalCount = document.querySelector( '.amb-goal-count' );
				if ( goalCount ) {
					goalCount.textContent = fmt( amb.chapters ) + ' / ' + fmt( amb.goal_chapters ) + ' פרקים';
				}
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

			var goalInput = document.createElement( 'input' );
			goalInput.type = 'number';
			goalInput.name = 'goal_books';
			goalInput.required = true;
			goalInput.min = '1';
			goalInput.max = '100';
			goalInput.value = '1';
			goalInput.placeholder = 'יעד ספרים';
			goalInput.title = 'כמה ספרי תהילים שלמים תגייסו';
			goalInput.className = 'reader-name-input tehilim-join-goal';

			var submit = document.createElement( 'button' );
			submit.type = 'submit';
			submit.className = 'btn-reader-said';
			submit.textContent = 'שליחת בקשה';

			form.appendChild( nameInput );
			form.appendChild( emailInput );
			form.appendChild( goalInput );
			form.appendChild( submit );

			var cta = trigger.closest( '.ambassador-cta' ) || trigger.parentElement;
			cta.parentNode.insertBefore( form, cta.nextSibling );
			nameInput.focus();
		},

		/* ============ Share modal (copy / email / WhatsApp) ============ */

		openShareModal: function( url, text ) {
			var self = this;
			var existing = document.querySelector( '.tehilim-modal-overlay' );
			if ( existing ) { existing.remove(); }

			var overlay = document.createElement( 'div' );
			overlay.className = 'tehilim-modal-overlay';

			var modal = document.createElement( 'div' );
			modal.className = 'tehilim-modal';
			modal.setAttribute( 'role', 'dialog' );
			modal.setAttribute( 'aria-label', 'שיתוף' );

			var close = document.createElement( 'button' );
			close.type = 'button';
			close.className = 'tehilim-modal-close';
			close.setAttribute( 'aria-label', 'סגירה' );
			close.textContent = '✕';

			var title = document.createElement( 'div' );
			title.className = 'tehilim-modal-title';
			title.textContent = 'שתפו את העמוד';

			var sub = document.createElement( 'div' );
			sub.className = 'tehilim-modal-sub';
			sub.textContent = 'כל מי שייכנס דרך הקישור מצטרף למניין שלכם';

			function makeBtn( className, label, svgPath, fill ) {
				var b = document.createElement( 'button' );
				b.type = 'button';
				b.className = className;
				var svg = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
				svg.setAttribute( 'width', '18' );
				svg.setAttribute( 'height', '18' );
				svg.setAttribute( 'viewBox', '0 0 24 24' );
				svg.setAttribute( 'fill', 'none' );
				var p = document.createElementNS( 'http://www.w3.org/2000/svg', 'path' );
				p.setAttribute( 'd', svgPath );
				p.setAttribute( 'stroke', fill );
				p.setAttribute( 'stroke-width', '1.9' );
				p.setAttribute( 'stroke-linecap', 'round' );
				p.setAttribute( 'stroke-linejoin', 'round' );
				svg.appendChild( p );
				b.appendChild( svg );
				b.appendChild( document.createTextNode( ' ' + label ) );
				return b;
			}

			var waBtn = makeBtn( 'btn-share-whatsapp', 'שיתוף ב-WhatsApp', 'M12 20a8 8 0 1 0-6.9-4L4 20l4-1.1A8 8 0 0 0 12 20z', '#EFC978' );
			waBtn.addEventListener( 'click', function() {
				window.open( 'https://wa.me/?text=' + encodeURIComponent( text + '\n' + url ), '_blank', 'noopener' );
			} );

			var mailBtn = makeBtn( 'btn-share-copy', 'שיתוף במייל', 'M3 6h18v12H3zM3 7l9 6 9-6', '#A94B2E' );
			mailBtn.addEventListener( 'click', function() {
				window.location.href = 'mailto:?subject=' + encodeURIComponent( text || 'הזמנה לאמירת תהילים' ) + '&body=' + encodeURIComponent( text + '\n' + url );
			} );

			var copyBtn = makeBtn( 'btn-share-copy', 'העתקת קישור', 'M9 9h12v12H9zM5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1', '#A94B2E' );
			copyBtn.addEventListener( 'click', function() {
				navigator.clipboard.writeText( url ).then( function() {
					copyBtn.textContent = '✓ הקישור הועתק!';
					window.setTimeout( function() { overlay.remove(); }, 900 );
				} ).catch( function() {
					window.prompt( 'העתיקו את הקישור:', url );
				} );
			} );

			modal.appendChild( close );
			modal.appendChild( title );
			modal.appendChild( sub );
			modal.appendChild( waBtn );
			modal.appendChild( mailBtn );
			modal.appendChild( copyBtn );
			overlay.appendChild( modal );
			document.body.appendChild( overlay );

			function shut() {
				overlay.remove();
				document.removeEventListener( 'keydown', onKey );
			}
			function onKey( ev ) {
				if ( 'Escape' === ev.key ) { shut(); }
			}
			close.addEventListener( 'click', shut );
			overlay.addEventListener( 'click', function( ev ) {
				if ( ev.target === overlay ) { shut(); }
			} );
			document.addEventListener( 'keydown', onKey );
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
