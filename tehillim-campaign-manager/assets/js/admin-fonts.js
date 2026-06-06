/**
 * Tehillim Campaign Manager — admin font picker.
 *
 * Opens the WordPress media library so an admin can choose an uploaded font
 * file (woff2/woff/ttf/otf) and have its URL filled into the settings field —
 * no need to copy URLs or hand-write @font-face.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.tcm-font-pick');
		if (!btn || !window.wp || !window.wp.media) {
			return;
		}
		e.preventDefault();

		var target = document.getElementById(btn.getAttribute('data-target'));
		var frame = window.wp.media({
			title: btn.getAttribute('data-title') || 'Choose font file',
			button: { text: btn.getAttribute('data-button') || 'Use this file' },
			multiple: false
		});

		frame.on('select', function () {
			var file = frame.state().get('selection').first().toJSON();
			if (target && file && file.url) {
				target.value = file.url;
			}
		});

		frame.open();
	});
})();
