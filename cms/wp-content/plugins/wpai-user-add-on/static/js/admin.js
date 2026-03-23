/**
 * plugin admin area javascript
 */
(function($){$(function () {
	
	if ( ! $('body.wpallimport-plugin').length) return; // do not execute any code if we are not on plugin page
	
	// [ WC Customers View ]
	// swither show/hide logic
	$('select.switcher').on('change', function (e) {

		var $targets = $('.switcher-target-' + $(this).attr('id'));

		var is_show = $(this).val() == 'xpath'; if ($(this).is('.switcher-reversed')) is_show = ! is_show;
		if (is_show) {
			$targets.slideDown();
		} else {
			$targets.slideUp().find('.clear-on-switch').add($targets.filter('.clear-on-switch')).val('');
		}

	}).change();

});})(jQuery);
