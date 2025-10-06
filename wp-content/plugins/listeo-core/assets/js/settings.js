jQuery(document).ready(function($) {

    /***** Colour picker *****/

    $('.colorpicker').hide();
    $('.colorpicker').each( function() {
        $(this).farbtastic( $(this).closest('.color-picker').find('.color') );
    });

    $('.color').click(function() {
        $(this).closest('.color-picker').find('.colorpicker').fadeIn();
    });

    $(document).mousedown(function() {
        $('.colorpicker').each(function() {
            var display = $(this).css('display');
            if ( display == 'block' )
                $(this).fadeOut();
        });
    });


    /***** Uploading images *****/

    var file_frame;

    jQuery.fn.uploadMediaFile = function( button, preview_media ) {
        var button_id = button.attr('id');
        var field_id = button_id.replace( '_button', '' );
        var preview_id = button_id.replace( '_button', '_preview' );

        // If the media frame already exists, reopen it.
        if ( file_frame ) {
          file_frame.open();
          return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
          title: jQuery( this ).data( 'uploader_title' ),
          button: {
            text: jQuery( this ).data( 'uploader_button_text' ),
          },
          multiple: false
        });

        // When an image is selected, run a callback.
        file_frame.on( 'select', function() {
          attachment = file_frame.state().get('selection').first().toJSON();
          jQuery("#"+field_id).val(attachment.id);
          if( preview_media ) {
            jQuery("#"+preview_id).attr('src',attachment.sizes.thumbnail.url);
          }
          file_frame = false;
        });

        // Finally, open the modal
        file_frame.open();
    }

    jQuery('.image_upload_button').click(function() {
        jQuery.fn.uploadMediaFile( jQuery(this), true );
    });

    jQuery('.image_delete_button').click(function() {
        jQuery(this).closest('td').find( '.image_data_field' ).val( '' );
        jQuery(this).closest('td').find( '.image_preview' ).remove();
        return false;
    });

    /***** CAPTCHA Fields Conditional Display *****/
    function toggleCaptchaFields() {
        // Hide all captcha API key fields
        $('input[name="listeo_recaptcha_sitekey"], input[name="listeo_recaptcha_secretkey"]').closest('tr').hide();
        $('input[name="listeo_recaptcha_sitekey3"], input[name="listeo_recaptcha_secretkey3"]').closest('tr').hide();
        $('input[name="listeo_hcaptcha_sitekey"], input[name="listeo_hcaptcha_secretkey"]').closest('tr').hide();
        $('input[name="listeo_turnstile_sitekey"], input[name="listeo_turnstile_secretkey"]').closest('tr').hide();
        
        // Get selected captcha version
        var selectedCaptcha = $('select[name="listeo_recaptcha_version"]').val();
        
        // Show relevant fields based on selection
        switch(selectedCaptcha) {
            case 'v2':
                $('input[name="listeo_recaptcha_sitekey"], input[name="listeo_recaptcha_secretkey"]').closest('tr').show();
                break;
            case 'v3':
                $('input[name="listeo_recaptcha_sitekey3"], input[name="listeo_recaptcha_secretkey3"]').closest('tr').show();
                break;
            case 'hcaptcha':
                $('input[name="listeo_hcaptcha_sitekey"], input[name="listeo_hcaptcha_secretkey"]').closest('tr').show();
                break;
            case 'turnstile':
                $('input[name="listeo_turnstile_sitekey"], input[name="listeo_turnstile_secretkey"]').closest('tr').show();
                break;
        }
    }
    
    // Initial toggle on page load
    toggleCaptchaFields();
    
    // Toggle when captcha version changes
    $('select[name="listeo_recaptcha_version"]').on('change', function() {
        toggleCaptchaFields();
    });

});