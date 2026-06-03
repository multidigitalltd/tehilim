/**
 * Tehillim Campaign Manager — front-end script (v3.0).
 *
 * Progressive enhancement: accessible copy-to-clipboard, join submission and
 * reader actions (done / take-more / release) via the REST API, so nothing
 * requires a full page reload mid-flow. Endpoints are authorised server-side;
 * this script only forwards the token it was handed in the page.
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
			region.className = 'screen-reader-text';
			document.body.appendChild(region);
		}
		region.textContent = message;
	}

	function readUrl(permalink, id, token) {
		var sep = permalink.indexOf('?') > -1 ? '&' : '?';
		return permalink + sep + 'tcm_read=' + encodeURIComponent(id) + '&token=' + encodeURIComponent(token) + '#tcm-read';
	}

	function withMsg(permalink, msg) {
		var sep = permalink.indexOf('?') > -1 ? '&' : '?';
		return permalink + sep + 'tcm_msg=' + encodeURIComponent(msg) + '#tcm';
	}

	function post(path, body) {
		return fetch(data.restUrl + path, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': data.nonce || '' },
			body: JSON.stringify(body || {})
		}).then(function (res) {
			return res.json().then(function (json) {
				return { ok: res.ok, json: json };
			});
		});
	}

	function errorText() {
		return (data.i18n && data.i18n.error) || 'Error';
	}

	// Copy-to-clipboard.
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

	// Join form submission.
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('form[data-tcm-join]');
		if (!form) {
			return;
		}
		e.preventDefault();

		var id = form.getAttribute('data-tcm-id');
		var permalink = form.getAttribute('data-tcm-permalink');
		var choice = (form.querySelector('[name="choice"]') || {}).value || '0';
		var body = {
			name: (form.querySelector('[name="name"]') || {}).value || '',
			email: (form.querySelector('[name="email"]') || {}).value || '',
			phone: (form.querySelector('[name="phone"]') || {}).value || '',
			turnstile: (form.querySelector('[name="cf-turnstile-response"]') || {}).value || ''
		};

		if (choice.indexOf('multi:') === 0) {
			body.mode = 'multi';
			body.count = parseInt(choice.slice(6), 10) || 0;
		} else if (choice.indexOf('book:') === 0) {
			body.mode = 'book';
		} else {
			body.mode = 'single';
			body.chapter = parseInt(choice, 10) || 0;
		}

		var submit = form.querySelector('[type="submit"]');
		var errBox = form.querySelector('.tcm-form-error');
		if (submit) {
			submit.setAttribute('disabled', 'disabled');
		}

		post('/campaigns/' + encodeURIComponent(id) + '/join', body)
			.then(function (result) {
				if (!result.ok || !result.json || !result.json.assignment_id) {
					var msg = (result.json && result.json.message) || errorText();
					throw new Error(msg);
				}
				window.location.href = readUrl(permalink, result.json.assignment_id, result.json.token);
			})
			.catch(function (err) {
				if (submit) {
					submit.removeAttribute('disabled');
				}
				if (errBox) {
					errBox.textContent = err.message || errorText();
					errBox.hidden = false;
				}
				announce(err.message || errorText());
			});
	});

	// Reader actions.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-tcm-action]');
		if (!btn) {
			return;
		}
		e.preventDefault();
		var action = btn.getAttribute('data-tcm-action');
		var id = btn.getAttribute('data-tcm-id');
		var token = btn.getAttribute('data-tcm-token');
		var permalink = btn.getAttribute('data-tcm-permalink');
		btn.setAttribute('disabled', 'disabled');

		post('/assignments/' + encodeURIComponent(id) + '/' + action, { token: token })
			.then(function (result) {
				var json = result.json || {};
				if (!result.ok || json.ok === false) {
					throw new Error(errorText());
				}
				if (action === 'done') {
					window.location.href = json.next
						? readUrl(permalink, json.next.assignment_id, json.next.token)
						: withMsg(permalink, 'done');
				} else if (action === 'take-more') {
					window.location.href = json.assignment_id
						? readUrl(permalink, json.assignment_id, json.token)
						: withMsg(permalink, 'full');
				} else {
					window.location.href = withMsg(permalink, 'released');
				}
			})
			.catch(function () {
				btn.removeAttribute('disabled');
				announce(errorText());
			});
	});
})();
