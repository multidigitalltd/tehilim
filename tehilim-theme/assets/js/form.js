/**
 * Tehilim Forms — Campaign Creation & Ambassador Signup
 */

( function() {
	'use strict';

	const Forms = {
		apiUrl: window.tehilim?.api_url || '/wp-json/tehilim/v1/',

		/**
		 * Initialize forms
		 */
		init() {
			this.setupEventListeners();
		},

		/**
		 * Setup form event listeners
		 */
		setupEventListeners() {
			document.addEventListener( 'submit', ( e ) => {
				if ( e.target.matches( '.form-ambassador-join' ) ) {
					e.preventDefault();
					this.handleAmbassadorJoin( e.target );
				}
				if ( e.target.matches( '.form-campaign-create' ) ) {
					e.preventDefault();
					this.handleCampaignCreate( e.target );
				}
			} );
		},

		/**
		 * Handle ambassador join form submission
		 */
		async handleAmbassadorJoin( form ) {
			const campaignId = form.dataset.campaignId;
			const nameInput = form.querySelector( 'input[name="name"]' );
			const emailInput = form.querySelector( 'input[name="email"]' );
			const submitBtn = form.querySelector( 'button[type="submit"]' );

			if ( !campaignId || !nameInput?.value || !emailInput?.value ) {
				alert( 'אנא מלאו את כל השדות' );
				return;
			}

			submitBtn.disabled = true;
			submitBtn.innerHTML = 'הצטרפות...';

			try {
				const response = await fetch( this.apiUrl + 'ambassadors/join', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': window.tehilim?.nonce || '',
					},
					body: JSON.stringify( {
						campaign_id: parseInt( campaignId ),
						name: nameInput.value,
						email: emailInput.value,
					} ),
				} );

				if ( !response.ok ) {
					const error = await response.json();
					throw new Error( error.message || 'Failed to join' );
				}

				const data = await response.json();

				// Copy personal URL to clipboard
				navigator.clipboard.writeText( data.personal_url );

				alert( `ברוכים הבאים! הקישור האישי שלכם הועתק:\n${data.personal_url}` );
				form.reset();
			} catch ( error ) {
				console.error( 'Join error:', error );
				alert( `Error: ${error.message}` );
			} finally {
				submitBtn.disabled = false;
				submitBtn.innerHTML = 'הצטרפו כשגריר';
			}
		},

		/**
		 * Handle campaign creation form submission
		 */
		async handleCampaignCreate( form ) {
			const occasionSelect = form.querySelector( 'select[name="occasion"]' );
			const dedicationInput = form.querySelector( 'input[name="dedication_name"]' );
			const organizerInput = form.querySelector( 'input[name="organizer_name"]' );
			const goalInput = form.querySelector( 'input[name="goal_books"]' );
			const submitBtn = form.querySelector( 'button[type="submit"]' );

			if ( !occasionSelect?.value || !dedicationInput?.value || !organizerInput?.value ) {
				alert( 'אנא מלאו את כל השדות הנדרשים' );
				return;
			}

			submitBtn.disabled = true;
			submitBtn.innerHTML = 'יוצרים...';

			try {
				const formData = new FormData( form );
				const response = await wp.apiRequest( {
					path: '/wp/v2/campaign',
					method: 'POST',
					data: {
						title: dedicationInput.value,
						status: 'draft',
						meta: {
							organizer_name: organizerInput.value,
							goal_books: parseInt( goalInput?.value || 1 ),
						},
					},
				} );

				if ( response.id ) {
					// Add occasion taxonomy
					if ( wp?.apiRequest ) {
						await wp.apiRequest( {
							path: `/wp/v2/campaign/${response.id}`,
							method: 'POST',
							data: {
								occasion: [ parseInt( occasionSelect.value ) ],
							},
						} );
					}

					alert( 'קמפיין נוצר! בדקו את הדוא"ל שלכם להמשך השלבים.' );
					window.location.href = `/campaigns/${response.id}`;
				}
			} catch ( error ) {
				console.error( 'Create campaign error:', error );
				alert( 'שגיאה ביצירת קמפיין. נסו שוב.' );
			} finally {
				submitBtn.disabled = false;
				submitBtn.innerHTML = 'צרו קמפיין';
			}
		},
	};

	// Initialize on DOM ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', () => Forms.init() );
	} else {
		Forms.init();
	}

	window.TehilimForms = Forms;
} )();
