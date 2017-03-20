/*
	Copyright: Deux Huit Huit 2017
	License: MIT, see the LICENCE file
*/

/**
 * JS for entry relationship field clean up page
 */

(function ($, S) {
	'use strict';
	
	var init = function () {
		$('.js-table-section').click(function (e) {
			if ($(e.target).is('a, button')) {
				return;
			}
			var t = $(this);
			t.next('.js-table-entries').toggleClass('irrelevant');
		});
	};
	
	$(init);
})(jQuery, Symphony);
