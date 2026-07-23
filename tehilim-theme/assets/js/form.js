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
				if ( e.target.matches( '.form-campaign-edit' ) ) {
					e.preventDefault();
					self.handleCampaignEdit( e.target );
				}
			} );

			// Personal area: toggle a campaign's edit panel
			document.addEventListener( 'click', function( e ) {
				var toggle = e.target.closest( '[data-edit-toggle]' );
				if ( toggle ) {
					var card = toggle.closest( '.account-camp' );
					var panel = card && card.querySelector( '.form-campaign-edit' );
					if ( panel ) { panel.hidden = ! panel.hidden; }
				}

				var moderate = e.target.closest( '[data-amb-action]' );
				if ( moderate ) {
					self.handleAmbassadorModerate( moderate );
				}

				var ambToggle = e.target.closest( '[data-amblist-toggle]' );
				if ( ambToggle ) {
					var ambCard = ambToggle.closest( '.account-camp' );
					var ambList = ambCard && ambCard.querySelector( '.account-amblist' );
					if ( ambList ) { ambList.hidden = ! ambList.hidden; }
				}
			} );

			// Personal area: image file selection inside an edit panel
			document.addEventListener( 'change', function( e ) {
				if ( e.target.matches( '.edit-image-input' ) ) {
					self.readEditImage( e.target );
				}
			} );
		},

		readEditImage: function( input ) {
			var form = input.closest( 'form' );
			var nameEl = form.querySelector( '.account-edit-imagename' );
			var file = input.files && input.files[ 0 ];
			if ( ! form || ! file ) { return; }
			if ( ! /^image\/(jpeg|png|webp)$/.test( file.type ) || file.size > 3 * 1024 * 1024 ) {
				this.showMessage( form, 'קובץ לא נתמך או גדול מ-3MB (JPG/PNG/WEBP).', true );
				input.value = '';
				return;
			}
			var reader = new FileReader();
			reader.onload = function( ev ) {
				form.dataset.imageData = ev.target.result;
				if ( nameEl ) { nameEl.textContent = file.name; }
			};
			reader.readAsDataURL( file );
		},

		handleCampaignEdit: function( form ) {
			var self = this;
			var campaignId = parseInt( form.dataset.campaignId, 10 );
			var submitBtn = form.querySelector( 'button[type="submit"]' );
			if ( ! campaignId ) { return; }

			var body = {
				dedication_name: ( form.querySelector( '[name="dedication_name"]' ) || {} ).value || '',
				goal_books: parseInt( ( form.querySelector( '[name="goal_books"]' ) || {} ).value, 10 ) || 1,
				occasion: ( form.querySelector( '[name="occasion"]' ) || {} ).value || '',
				description: ( form.querySelector( '[name="description"]' ) || {} ).value || '',
				dedication_text: ( form.querySelector( '[name="dedication_text"]' ) || {} ).value || '',
			};

			var removeBox = form.querySelector( '[name="remove_image"]' );
			if ( removeBox && removeBox.checked ) {
				body.remove_image = true;
			} else if ( form.dataset.imageData ) {
				body.image_data = form.dataset.imageData;
			}

			var original = submitBtn.textContent;
			submitBtn.disabled = true;
			submitBtn.textContent = 'שומרים…';

			this.apiPost( 'campaigns/' + campaignId + '/update', body )
				.then( function() {
					self.showMessage( form, 'השינויים נשמרו! מרעננים…' );
					window.setTimeout( function() { window.location.reload(); }, 900 );
				} )
				.catch( function( err ) {
					submitBtn.disabled = false;
					submitBtn.textContent = original;
					self.showMessage( form, self.restError( err, 'שמירת השינויים נכשלה. נסו שוב.' ), true );
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
				already_requested: 'כבר קיימת בקשה עם האימייל הזה לקמפיין. המתינו לאישור המנהל.',
				db_error: 'שגיאת שרת בשמירה. נסו שוב בעוד רגע.',
				forbidden: 'אין לכם הרשאה לנהל את הקמפיין הזה.',
			};
			if ( data && map[ data.code ] ) {
				return map[ data.code ];
			}
			// Unmapped error: append the code so the problem is diagnosable
			var suffix = data && data.code ? ' [' + data.code + ']' : '';
			return fallback + suffix;
		},

		/* ============ Ambassador moderation (personal area) ============ */

		handleAmbassadorModerate: function( button ) {
			var self = this;
			var ambassadorId = parseInt( button.dataset.ambassadorId, 10 );
			var action = button.dataset.ambAction;
			if ( ! ambassadorId || ! action ) { return; }

			if ( 'reject' === action && ! window.confirm( 'לדחות את בקשת השגריר הזו?' ) ) {
				return;
			}

			var row = button.closest( '.account-pending-row' );
			button.disabled = true;
			button.textContent = 'מעבדים…';

			this.apiPost( 'ambassadors/' + ambassadorId + '/moderate', { action: action } )
				.then( function() {
					if ( row ) {
						row.style.cssText = 'opacity:.55;pointer-events:none';
						row.querySelectorAll( 'button' ).forEach( function( b ) { b.remove(); } );
						var note = document.createElement( 'span' );
						note.style.cssText = 'font-weight:700;font-size:13px;color:' + ( 'approve' === action ? '#4E8B5E' : '#A03C22' );
						note.textContent = 'approve' === action ? '✓ אושר — נשלח מייל לשגריר' : 'נדחה';
						row.appendChild( note );
					}
					window.setTimeout( function() { window.location.reload(); }, 1400 );
				} )
				.catch( function( err ) {
					button.disabled = false;
					button.textContent = 'approve' === action ? 'אישור' : 'דחייה';
					window.alert( self.restError( err, 'הפעולה נכשלה. נסו שוב.' ) );
				} );
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

			var goalField = form.querySelector( 'input[name="goal_books"]' );

			this.apiPost( 'ambassadors/join', {
				campaign_id: campaignId,
				name: nameInput.value.trim(),
				email: emailInput.value.trim(),
				goal_books: goalField ? ( parseInt( goalField.value, 10 ) || 1 ) : 1,
			} )
				.then( function() {
					self.renderJoinPending( form );
				} )
				.catch( function( err ) {
					submitBtn.disabled = false;
					submitBtn.textContent = original;
					self.showMessage( form, self.restError( err, 'ההצטרפות נכשלה. נסו שוב.' ), true );
				} );
		},

		/* Styled "request sent" confirmation replacing the join form */
		renderJoinPending: function( form ) {
			form.textContent = '';
			form.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:8px;background:#EAF3EC;border:1px solid #CFE6D5;border-radius:16px;padding:22px 24px;text-align:center';

			var icon = document.createElement( 'div' );
			icon.style.cssText = 'width:44px;height:44px;border-radius:50%;background:#4E8B5E;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:800';
			icon.textContent = '✓';

			var title = document.createElement( 'div' );
			title.style.cssText = 'font-weight:800;font-size:17px;color:#2E2318';
			title.textContent = 'הבקשה נשלחה למנהל הקמפיין!';

			var sub = document.createElement( 'div' );
			sub.style.cssText = 'font-size:14px;color:#4E8B5E;font-weight:600;line-height:1.6;max-width:420px';
			sub.textContent = 'לאחר שהבקשה תאושר, יישלח אליכם מייל עם הקישור לעמוד האישי שלכם וקישור מוכן לשיתוף.';

			form.appendChild( icon );
			form.appendChild( title );
			form.appendChild( sub );
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

			var dedicationText = form.querySelector( 'input[name="dedication_text"]' );
			if ( dedicationText && dedicationText.value.trim() ) {
				body.dedication_text = dedicationText.value.trim();
			}

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
					if ( data.pending ) {
						self.showMessage( form, 'הקמפיין מוכן וממתין לאישור מנהל! תקבלו מייל ברגע שהוא יאושר ויעלה לאוויר. מעבירים אתכם לאזור האישי…' );
						window.setTimeout( function() {
							window.location.href = data.account_url || '/';
						}, 2600 );
						return;
					}
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
