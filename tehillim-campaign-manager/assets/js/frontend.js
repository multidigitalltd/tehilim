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

	// After the join/done redirect lands on #tcm-read, move focus to the reader
	// so screen-reader and keyboard users get the chapter context immediately.
	function focusReader() {
		var reader = document.getElementById('tcm-read');
		if (reader && window.location.hash === '#tcm-read') {
			var heading = reader.querySelector('h3') || reader;
			heading.setAttribute('tabindex', '-1');
			heading.focus();
		}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', focusReader);
	} else {
		focusReader();
	}

	// Count-up animation for [data-tcm-count] when it scrolls into view.
	function initCountUp() {
		var els = document.querySelectorAll('[data-tcm-count]');
		if (!els.length) {
			return;
		}
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var run = function (el) {
			var target = parseInt(el.getAttribute('data-tcm-count'), 10) || 0;
			if (reduce || target <= 0) {
				return;
			}
			var start = null;
			var dur = 1300;
			var step = function (now) {
				if (!start) {
					start = now;
				}
				var p = Math.min(1, (now - start) / dur);
				var eased = 1 - Math.pow(1 - p, 3);
				el.textContent = Math.round(target * eased).toLocaleString();
				if (p < 1) {
					requestAnimationFrame(step);
				}
			};
			requestAnimationFrame(step);
		};
		if (!('IntersectionObserver' in window)) {
			Array.prototype.forEach.call(els, run);
			return;
		}
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) {
					run(e.target);
					io.unobserve(e.target);
				}
			});
		}, { threshold: 0.3 });
		Array.prototype.forEach.call(els, function (el) {
			io.observe(el);
		});
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initCountUp);
	} else {
		initCountUp();
	}

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
		var select = form.querySelector('[name="choice"]');
		if (submit) {
			submit.setAttribute('disabled', 'disabled');
		}
		if (select) {
			select.removeAttribute('aria-invalid');
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
				if (select && errBox && errBox.id) {
					select.setAttribute('aria-invalid', 'true');
					select.setAttribute('aria-describedby', errBox.id);
				}
				if (errBox) {
					errBox.textContent = err.message || errorText();
					errBox.hidden = false;
					if (errBox.focus) {
						errBox.focus();
					}
				}
				announce(err.message || errorText());
			});
	});

	// Subscribe form submission.
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('form[data-tcm-subscribe]');
		if (!form) {
			return;
		}
		e.preventDefault();
		var body = {
			list: form.getAttribute('data-tcm-list') || 'daily_chapter',
			name: (form.querySelector('[name="name"]') || {}).value || '',
			email: (form.querySelector('[name="email"]') || {}).value || '',
			phone: (form.querySelector('[name="phone"]') || {}).value || '',
			channel: (form.querySelector('[name="channel"]') || {}).value || 'email',
			consent: (form.querySelector('[name="consent"]') || {}).checked ? 1 : 0
		};
		var submit = form.querySelector('[type="submit"]');
		var errBox = form.querySelector('.tcm-form-error');
		var okBox = form.querySelector('.tcm-form-success');
		if (submit) {
			submit.setAttribute('disabled', 'disabled');
		}
		post('/subscribe', body)
			.then(function (result) {
				if (!result.ok || !result.json || result.json.ok !== true) {
					throw new Error((result.json && result.json.message) || errorText());
				}
				form.reset();
				if (okBox) {
					okBox.hidden = false;
				}
				if (errBox) {
					errBox.hidden = true;
				}
			})
			.catch(function (err) {
				if (errBox) {
					errBox.textContent = err.message || errorText();
					errBox.hidden = false;
				}
			})
			.then(function () {
				if (submit) {
					submit.removeAttribute('disabled');
				}
			});
	});

	// Create campaign.
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('form[data-tcm-create]');
		if (!form) {
			return;
		}
		e.preventDefault();
		var submit = form.querySelector('[type="submit"]');
		var errBox = form.querySelector('.tcm-form-error');
		if (submit) {
			submit.setAttribute('disabled', 'disabled');
		}
		post('/campaigns', {
			title: (form.querySelector('[name="title"]') || {}).value || '',
			target: (form.querySelector('[name="target"]') || {}).value || 1,
			bonus: (form.querySelector('[name="bonus"]') || {}).value || 0,
			dedicated_to: (form.querySelector('[name="dedicated_to"]') || {}).value || '',
			content: (form.querySelector('[name="content"]') || {}).value || ''
		}).then(function (result) {
			if (!result.ok || !result.json || !result.json.permalink) {
				throw new Error((result.json && result.json.message) || errorText());
			}
			window.location.href = result.json.permalink;
		}).catch(function (err) {
			if (submit) {
				submit.removeAttribute('disabled');
			}
			if (errBox) {
				errBox.textContent = err.message || errorText();
				errBox.hidden = false;
			}
		});
	});

	// Owner: update campaign.
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('form[data-tcm-update]');
		if (!form) {
			return;
		}
		e.preventDefault();
		var id = form.getAttribute('data-tcm-id');
		var okBox = form.querySelector('.tcm-form-success');
		var errBox = form.querySelector('.tcm-form-error');
		post('/campaigns/' + encodeURIComponent(id) + '/update', {
			title: (form.querySelector('[name="title"]') || {}).value || '',
			content: (form.querySelector('[name="content"]') || {}).value || '',
			target: (form.querySelector('[name="target"]') || {}).value || 1
		}).then(function (result) {
			if (!result.ok || !result.json || result.json.ok !== true) {
				throw new Error(errorText());
			}
			if (okBox) {
				okBox.hidden = false;
			}
			if (errBox) {
				errBox.hidden = true;
			}
		}).catch(function () {
			if (errBox) {
				errBox.textContent = errorText();
				errBox.hidden = false;
			}
		});
	});

	// Owner: add a bonus book.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-tcm-bonus]');
		if (!btn) {
			return;
		}
		e.preventDefault();
		var id = btn.getAttribute('data-tcm-id');
		btn.setAttribute('disabled', 'disabled');
		post('/campaigns/' + encodeURIComponent(id) + '/bonus', {})
			.then(function (result) {
				if (!result.ok) {
					throw new Error(errorText());
				}
				window.location.reload();
			})
			.catch(function () {
				btn.removeAttribute('disabled');
				announce(errorText());
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

/* Create-campaign wizard: step navigation, validation and review. */
(function () {
	'use strict';
	function each(list, fn) { Array.prototype.forEach.call(list, fn); }

	function currentStep(wizard) {
		var step = 1;
		each(wizard.querySelectorAll('[data-tcm-pane]'), function (p) {
			if (!p.hidden) { step = parseInt(p.getAttribute('data-tcm-pane'), 10); }
		});
		return step;
	}

	function validate(wizard, step) {
		var pane = wizard.querySelector('[data-tcm-pane="' + step + '"]');
		if (!pane) { return true; }
		var required = pane.querySelectorAll('[required]');
		for (var i = 0; i < required.length; i++) {
			if (!String(required[i].value).trim()) {
				required[i].setAttribute('aria-invalid', 'true');
				required[i].focus();
				return false;
			}
			required[i].removeAttribute('aria-invalid');
		}
		return true;
	}

	function buildReview(wizard) {
		var review = wizard.querySelector('[data-tcm-review]');
		if (!review) { return; }
		review.innerHTML = '';
		each(['title', 'dedicated_to', 'content', 'target', 'bonus'], function (name) {
			var input = wizard.querySelector('[name="' + name + '"]');
			if (!input) { return; }
			var val = String(input.value).trim();
			if (!val) { return; }
			var label = input.id ? wizard.querySelector('label[for="' + input.id + '"]') : null;
			var li = document.createElement('li');
			var strong = document.createElement('strong');
			strong.textContent = (label ? label.textContent.replace(/\s+/g, ' ').trim() : name) + ': ';
			li.appendChild(strong);
			li.appendChild(document.createTextNode(val));
			review.appendChild(li);
		});
	}

	function show(wizard, step) {
		var panes = wizard.querySelectorAll('[data-tcm-pane]');
		var total = panes.length;
		step = Math.min(total, Math.max(1, step));
		each(panes, function (p) {
			p.hidden = parseInt(p.getAttribute('data-tcm-pane'), 10) !== step;
		});
		each(wizard.querySelectorAll('.tcm-wizard-bars span'), function (b, i) {
			b.classList.toggle('is-active', i < step);
		});
		var num = wizard.querySelector('[data-tcm-stepnum]');
		if (num) { num.textContent = String(step); }
		var prev = wizard.querySelector('[data-tcm-prev]');
		var next = wizard.querySelector('[data-tcm-next]');
		var pub = wizard.querySelector('[data-tcm-publish]');
		if (prev) { prev.hidden = step === 1; }
		if (next) { next.hidden = step === total; }
		if (pub) { pub.hidden = step !== total; }
		if (step === total) { buildReview(wizard); }
		var heading = wizard.querySelector('[data-tcm-pane="' + step + '"] h3');
		if (heading) { heading.setAttribute('tabindex', '-1'); heading.focus(); }
	}

	document.addEventListener('click', function (e) {
		var nav = e.target.closest('[data-tcm-next],[data-tcm-prev]');
		if (!nav) { return; }
		var wizard = nav.closest('[data-tcm-wizard]');
		if (!wizard) { return; }
		var step = currentStep(wizard);
		if (nav.hasAttribute('data-tcm-next')) {
			if (!validate(wizard, step)) { return; }
			show(wizard, step + 1);
		} else {
			show(wizard, step - 1);
		}
	});
})();
