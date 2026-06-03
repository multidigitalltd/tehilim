/**
 * Tehillim Campaign Manager — front-end script (v3.0).
 *
 * Progressive enhancement only: copy-to-clipboard with an accessible
 * announcement, and reader actions (done / take-more / release) via the REST
 * API so marking a chapter never requires a full page reload. All endpoints
 * are token-authorised server-side; this script only sends the token it was
 * given in the page.
 */
(function () {
	'use strict';

	var data = window.tcmData || {};

	function announce(message) {
		var region = document.getElementById('tcm-live');
		if (!region) {
			region = document.createElement('div');
			region.id = 'tcm-live';
			region.setAttribute('aria-live', 'polite');
			region.style.position = 'absolute';
			region.style.width = '1px';
			region.style.height = '1px';
			region.style.overflow = 'hidden';
			region.style.clip = 'rect(1px, 1px, 1px, 1px)';
			document.body.appendChild(region);
		}
		region.textContent = message;
	}

	// Copy-to-clipboard buttons.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-tcm-copy]');
		if (!btn) {
			return;
		}
		e.preventDefault();
		var value = btn.getAttribute('data-tcm-copy') || '';
		var done = function () {
			announce((data.i18n && data.i18n.copied) || 'Copied');
		};
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(value).then(done).catch(function () {
				window.prompt('', value);
			});
		} else {
			window.prompt('', value);
		}
	});

	// Reader actions via REST.
	function post(path, body) {
		return fetch(data.restUrl + path, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': data.nonce || ''
			},
			body: JSON.stringify(body || {})
		}).then(function (res) {
			return res.json().then(function (json) {
				return { ok: res.ok, json: json };
			});
		});
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-tcm-action]');
		if (!btn) {
			return;
		}
		e.preventDefault();
		var action = btn.getAttribute('data-tcm-action');
		var id = btn.getAttribute('data-tcm-id');
		var token = btn.getAttribute('data-tcm-token');
		btn.setAttribute('disabled', 'disabled');

		post('/assignments/' + encodeURIComponent(id) + '/' + action, { token: token })
			.then(function (result) {
				if (!result.ok || !result.json || result.json.ok === false) {
					throw new Error('action_failed');
				}
				// Let the page decide how to react (navigate / re-render).
				document.dispatchEvent(
					new CustomEvent('tcm:action', { detail: { action: action, result: result.json } })
				);
			})
			.catch(function () {
				btn.removeAttribute('disabled');
				announce((data.i18n && data.i18n.error) || 'Error');
			});
	});
})();
