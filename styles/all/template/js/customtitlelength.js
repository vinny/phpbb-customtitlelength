(function($) {
	'use strict';

	$(function() {
		$('[data-customtitlelength-max]').each(function() {
			var max = parseInt($(this).attr('data-customtitlelength-max'), 10);

			if (!isNaN(max) && max > 0) {
				$('#subject').attr('maxlength', max);
			}
		});
	});
})(jQuery);
