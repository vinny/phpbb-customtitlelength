(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		const markers = document.querySelectorAll('[data-customtitlelength-max]');

		markers.forEach(function(marker) {
			const max = parseInt(marker.getAttribute('data-customtitlelength-max'), 10);
			if (Number.isNaN(max) || max <= 0) {
				return;
			}

			const form = marker.closest('form');
			const subjectInput = form
				? form.querySelector('input[name="subject"]')
				: document.getElementById('subject');

			if (!subjectInput) {
				return;
			}

			subjectInput.setAttribute('maxlength', String(max));

			if (marker.getAttribute('data-customtitlelength-counter') !== '1') {
				return;
			}

			if (subjectInput.dataset.customtitlelengthBound === '1') {
				return;
			}
			subjectInput.dataset.customtitlelengthBound = '1';

			const counter = document.createElement('span');
			counter.className = 'customtitlelength-counter';
			counter.setAttribute('aria-atomic', 'true');
			counter.setAttribute('aria-live', 'off');

			const updateCounter = function() {
				const length = subjectInput.value.length;
				const atLimit = length >= max;

				counter.textContent = length + ' / ' + max;
				counter.classList.toggle('customtitlelength-counter--limit', atLimit);
				counter.setAttribute('aria-live', atLimit ? 'polite' : 'off');
			};

			subjectInput.insertAdjacentElement('afterend', counter);
			subjectInput.addEventListener('input', updateCounter);
			updateCounter();
		});
	});
})();
