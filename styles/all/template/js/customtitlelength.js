(function($) {
	'use strict';

	$(function() {
		$('[data-customtitlelength-target]').each(function() {
			var $el = $(this);
			var target = $el.data('customtitlelength-target');
			var max = parseInt($el.data('customtitlelength-max'), 10);

			if (target && !isNaN(max) && max > 0) {
				$(target).attr('maxlength', max);
			}
		});
	});
})(jQuery);
