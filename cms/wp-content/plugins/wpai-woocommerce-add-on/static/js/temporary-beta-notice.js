jQuery(document).ready(function($) {

    if ( ! $('body.wpallimport-plugin').length) return; // do not execute any code if we are not on plugin page

    // Temporary notice code until WP All Import's updates are in wide use.
    $('#wpai-wooco-beta-notice').parent('div:first').find('button.notice-dismiss').on('click', (function(){

        let addon = $(this).parent('div:first').attr('rel');
        if( typeof addon === 'undefined'){
            addon = $('#wpai-wooco-beta-notice').attr('rel');
        }

        var request = {
            action: 'dismiss_notifications',
            security: wp_all_import_security,
            addon: addon
        };

        $(this).parent('div:first').hide();

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: request,
            success: function(response) {

            },
            dataType: "json"
    });
    }));

});
