/**
 * Tehilim Forms — Campaign Creation & Ambassador Signup
 */

( function() {
	'use strict';

	var Forms = {
		apiUrl: ( window.tehilim && window.tehilim.api_url ) || '/wp-json/tehilim/v1/',
		nonce: ( window.tehilim && window.tehilim.nonce ) || '',

		init: function() {
			this.setupEventListeners();
		},

		setupEventListeners: function() {
			var self = this;
			document.addEventListener( 'submit', function( e ) {
				if ( e.target.matches( '.form-ambassador-join' ) ) {
					e.preventDefault();
					self.handleAmbassadorJoin( e.target );
				}
				if ( e.target.matches( '.form-campaign-create' ) ) {
					e.preventDefault();
					self.handleCampaignCreate( e.target );
				}
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

		/* Inline message box (replaces alert) */
		showMessage: function( form, text, isError ) {
			var box = form.querySelector( '.tehilim-form-msg' );
			if ( ! box ) {
				box = document.createElement( 'div' );
				box.className = 'tehilim-form-msg';
				box.setAttribute( 'role', 'alert' );
				var submitBtn = form.querySelector( 'button[type="submit"]' );
				( submitBtn ? submitBtn.parentNode : form ).insertBefore( box, submitBtn );
			}
			box.style.cssText = [
				'margin:0 0 14px', 'padding:12px 16px', 'border-radius:12px',
				'font-weight:600', 'font-size:14.5px', 'line-height:1.55',
				isError
					? 'background:#FBEAE4;border:1px solid #E8C4B4;color:#A03C22'
					: 'background:#EAF3EC;border:1px solid #CFE6D5;color:#4E8B5E',
			].join( ';' );
			box.textContent = text;
			box.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		},

		restError: function( data, fallback ) {
			var map = {
				rate_limit: 'הגעתם למגבלת הבקשות. נסו שוב בעוד שעה.',
				turnstile_failed: 'אימות האבטחה נכשל. רעננו את העמוד ונסו שוב.',
				invalid_email: 'כתובת האימייל אינה תקינה.',
				invalid_occasion: 'בחרו מטרת קריאה מהרשימה.',
				invalid_params: 'אנא מלאו את כל השדות הנדרשים.',
				invalid_length: 'השם חייב להכיל בין 2 ל-100 תווים.',
				create_failed: 'יצירת הקמפיין נכשלה בשרת. נסו שוב.',
				login_required: 'יש להתחבר כדי לפתוח קמפיין. מעבירים אתכם להתחברות…',
				rest_no_route: 'נקודת הקצה לא נמצאה — ודאו שערכת הנושא פעילה ורעננו קישורים קבועים.',
			};
			if ( data && map[ data.code ] ) {
				return map[ data.code ];
			}
			// Unmapped error: append the code so the problem is diagnosable
			var suffix = data && data.code ? ' [' + data.code + ']' : '';
			return fallback + suffix;
		},

		/* ============ Ambassador join ============ */

		handleAmbassadorJoin: function( form ) {
			var self = this;
			var campaignId = parseInt( form.dataset.campaignId, 10 );
			var nameInput = form.querySelector( 'input[name="name"]' );
			var emailInput = form.querySelector( 'input[name="email"]' );
			var submitBtn = form.querySelector( 'button[type="submit"]' );

			if ( ! campaignId || ! nameInput.value.trim() || ! emailInput.value.trim() ) {
				this.showMessage( form, 'אנא מלאו שם ואימייל.', true );
				return;
			}

			var original = submitBtn.textContent;
			submitBtn.disabled = true;
			submitBtn.textContent = 'שולחים…';

			this.apiPost( 'ambassadors/join', {
				campaign_id: campaignId,
				name: nameInput.value.trim(),
				email: emailInput.value.trim(),
			} )
				.then( function( data ) {
					self.renderJoinSuccess( form, data.personal_url );
				} )
				.catch( function( err ) {
					submitBtn.disabled = false;
					submitBtn.textContent = original;
					self.showMessage( form, self.restError( err, 'ההצטרפות נכשלה. נסו שוב.' ), true );
				} );
		},

		renderJoinSuccess: function( form, personalUrl ) {
			form.textContent = '';
			form.style.cssText = 'display:flex;flex-direction:column;gap:10px';

			var title = document.createElement( 'div' );
			title.style.cssText = 'font-weight:800;font-size:16px;color:#4E8B5E';
			title.textContent = 'ברוכים הבאים! זה הקישור האישי שלכם:';

			var linkBox = document.createElement( 'div' );
			linkBox.style.cssText = 'padding:12px 16px;border-radius:12px;background:#FBF3E4;border:1px dashed #D9C4A3;color:#B9822B;font-weight:700;font-size:13.5px;word-break:break-all;direction:ltr;text-align:left';
			linkBox.textContent = personalUrl;

			var copyBtn = document.createElement( 'button' );
			copyBtn.type = 'button';
			copyBtn.className = 'btn-reader-said';
			copyBtn.textContent = 'העתקת הקישור';
			copyBtn.addEventListener( 'click', function() {
				navigator.clipboard.writeText( personalUrl ).then( function() {
					copyBtn.textContent = '✓ הועתק';
				} );
			} );

			form.appendChild( title );
			form.appendChild( linkBox );
			form.appendChild( copyBtn );
		},

		/* ============ Campaign creation ============ */

		handleCampaignCreate: function( form ) {
			var self = this;
			var occasionSelect = form.querySelector( 'select[name="occasion"]' );
			var dedicationInput = form.querySelector( 'input[name="dedication_name"]' );
			var organizerInput = form.querySelector( 'input[name="organizer_name"]' );
			var goalInput = form.querySelector( 'input[name="goal_books"]' );
			var submitBtn = form.querySelector( 'button[type="submit"]' );

			if ( ! occasionSelect.value ) {
				this.showMessage( form, 'בחרו את מטרת הקריאה.', true );
				return;
			}
			if ( ! dedicationInput.value.trim() || ! organizerInput.value.trim() ) {
				this.showMessage( form, 'אנא מלאו את כל השדות הנדרשים.', true );
				return;
			}

			var body = {
				occasion: occasionSelect.value,
				dedication_name: dedicationInput.value.trim(),
				organizer_name: organizerInput.value.trim(),
				goal_books: parseInt( goalInput && goalInput.value, 10 ) || 1,
			};

			// Optional campaign image (data URL captured by page-create.php)
			if ( form.dataset.imageData ) {
				body.image_data = form.dataset.imageData;
			}

			// Turnstile token (when the widget is rendered on the page)
			var turnstileInput = form.querySelector( '[name="cf-turnstile-response"]' );
			if ( turnstileInput && turnstileInput.value ) {
				body.cf_turnstile_response = turnstileInput.value;
			}

			var original = submitBtn.textContent;
			submitBtn.disabled = true;
			submitBtn.textContent = 'יוצרים את הקמפיין…';

			this.apiPost( 'campaigns', body )
				.then( function( data ) {
					self.showMessage( form, 'הקמפיין נוצר בהצלחה! מעבירים אתכם לעמוד הקמפיין…' );
					window.setTimeout( function() {
						window.location.href = data.campaign_url;
					}, 900 );
				} )
				.catch( function( err ) {
					submitBtn.disabled = false;
					submitBtn.textContent = original;
					self.showMessage( form, self.restError( err, 'שגיאה ביצירת הקמפיין. נסו שוב.' ), true );
					if ( err && err.code === 'login_required' && window.tehilim && window.tehilim.login_url ) {
						window.setTimeout( function() {
							window.location.href = window.tehilim.login_url;
						}, 1400 );
					}
				} );
		},
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function() { Forms.init(); } );
	} else {
		Forms.init();
	}

	window.TehilimForms = Forms;
} )();
