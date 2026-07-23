/**
 * Tehilim App — Reader & Campaign Logic
 */

( function() {
	'use strict';

	const App = {
		apiUrl: window.tehilim?.api_url || '/wp-json/tehilim/v1/',
		turnstileSiteKey: window.tehilim?.turnstile_site_key || '',

		/**
		 * Initialize app
		 */
		init() {
			this.setupEventListeners();
			if ( this.turnstileSiteKey ) {
				this.loadTurnstileScript();
			}
		},

		/**
		 * Setup global event listeners
		 */
		setupEventListeners() {
			document.addEventListener( 'click', ( e ) => {
				if ( e.target.matches( '.btn-say-chapter' ) ) {
					e.preventDefault();
					this.handleRecitation( e.target );
				}
				if ( e.target.matches( '.btn-share' ) ) {
					e.preventDefault();
					this.handleShare( e.target );
				}
			} );
		},

		/**
		 * Handle recitation (I said this)
		 */
		async handleRecitation( button ) {
			const campaignId = button.dataset.campaignId;
			const chapterNumber = button.dataset.chapterNumber;
			const ambassadorId = button.dataset.ambassadorId || null;

			if ( !campaignId || !chapterNumber ) {
				console.error( 'Missing campaign or chapter data' );
				return;
			}

			try {
				const response = await fetch( this.apiUrl + 'recitations', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': window.tehilim?.nonce || '',
					},
					body: JSON.stringify( {
						campaign_id: parseInt( campaignId ),
						chapter_number: parseInt( chapterNumber ),
						ambassador_id: ambassadorId ? parseInt( ambassadorId ) : null,
					} ),
				} );

				if ( !response.ok ) {
					throw new Error( `HTTP ${response.status}` );
				}

				const data = await response.json();

				if ( data.success ) {
					button.innerHTML = '✓ Said!';
					button.disabled = true;
					this.pollCampaignStats( campaignId );
				}
			} catch ( error ) {
				console.error( 'Recitation error:', error );
				alert( 'Failed to record recitation. Please try again.' );
			}
		},

		/**
		 * Poll campaign stats every 10 seconds
		 */
		async pollCampaignStats( campaignId ) {
			try {
				const response = await fetch( this.apiUrl + `campaigns/${campaignId}/stats` );
				const data = await response.json();

				const statsEl = document.querySelector( `[data-campaign-id="${campaignId}"] .campaign-stats` );
				if ( statsEl ) {
					statsEl.innerHTML = `
						<div class="stat">
							<strong>${data.books_done}</strong>
							<span>${data.books_done === 1 ? 'Book' : 'Books'}</span>
						</div>
						<div class="stat">
							<strong>${data.chapters_done}</strong>
							<span>Chapters</span>
						</div>
						<div class="stat">
							<strong>${data.participants}</strong>
							<span>Participants</span>
						</div>
						<div class="stat">
							<strong>${data.ambassadors}</strong>
							<span>Ambassadors</span>
						</div>
					`;
				}
			} catch ( error ) {
				console.error( 'Failed to fetch stats:', error );
			}
		},

		/**
		 * Handle sharing
		 */
		handleShare( button ) {
			const text = button.dataset.shareText || 'Join me in saying Tehilim';
			const url = button.dataset.shareUrl || window.location.href;

			if ( navigator.share ) {
				navigator.share( {
					title: 'Tehilim Campaign',
					text,
					url,
				} );
			} else if ( button.dataset.shareType === 'whatsapp' ) {
				const whatsappUrl = `https://wa.me/?text=${encodeURIComponent( text + ' ' + url )}`;
				window.open( whatsappUrl, '_blank' );
			} else {
				navigator.clipboard.writeText( url );
				button.innerHTML = '✓ Copied!';
			}
		},

		/**
		 * Load Turnstile script if needed
		 */
		loadTurnstileScript() {
			const script = document.createElement( 'script' );
			script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
			script.async = true;
			script.defer = true;
			document.head.appendChild( script );
		},
	};

	// Initialize on DOM ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', () => App.init() );
	} else {
		App.init();
	}

	window.TehilimApp = App;
} )();
