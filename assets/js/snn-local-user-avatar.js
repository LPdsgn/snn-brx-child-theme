(function($) {
    'use strict';

    var inputName    = snn_lua_obj.input_name,
        defaultSrc   = snn_lua_obj.default_avatar_src,
        defaultSrcSet = snn_lua_obj.default_avatar_srcset,
        mediaTitle   = snn_lua_obj.media_title,
        buttonText   = snn_lua_obj.button_text,
        wpMediaSizes = ['full', 'large', 'medium', 'thumbnail'];

    function updateAttachment(src, srcset, id) {
        $('.snn-avatar-preview').attr({ 'src': src, 'srcset': srcset });
        $('input[name="' + inputName + '"]').val(id === undefined ? '' : parseInt(id));

        if (src === defaultSrc) {
            $('#snn-avatar-description').removeClass('hidden');
            $('#snn-btn-media-remove').addClass('hidden');
        } else {
            $('#snn-avatar-description').addClass('hidden');
            $('#snn-btn-media-remove').removeClass('hidden');
        }
    }

    $(function() {
        // Move our <tr> into the existing profile table, replacing the default WP avatar row
        var $wpRow = $('tr.user-profile-picture');
        var $snnRow = $('#snn-local-user-avatar');
        if ($wpRow.length && $snnRow.length) {
            $snnRow.insertAfter($wpRow);
            $wpRow.remove();
        }
        // Remove the now-empty wrapper table
        $('#snn-local-user-avatar-wrap').remove();

        $(document)
            .on('click', '#snn-btn-media-add', function(e) {
                e.preventDefault();

                wp.media.editor.open();

                // Add scoping class to the media modal for CSS
                setTimeout(function() {
                    $('.media-modal').addClass('snn-lua-media-frame');
                }, 50);

                $('.media-frame-title h1').html(mediaTitle);
                $('.media-button-insert').html(buttonText);

                wp.media.editor.send.attachment = function(props, attachment) {
                    var attachmentSrc = attachment.url;
                    for (var i = 0; i < wpMediaSizes.length; i++) {
                        if (attachment.sizes[wpMediaSizes[i]] && attachment.sizes[wpMediaSizes[i]].url) {
                            attachmentSrc = attachment.sizes[wpMediaSizes[i]].url;
                        }
                    }
                    updateAttachment(attachmentSrc, attachmentSrc, attachment.id);
                };

                wp.Uploader.queue.on('reset', function() {
                    $('.media-button-insert').html(buttonText);
                });
            })
            .on('click', '#snn-btn-media-remove', function(e) {
                e.preventDefault();
                updateAttachment(defaultSrc, defaultSrcSet);
            })
            .on('click', '.snn-avatar-preview', function() {
                $('#snn-btn-media-add').trigger('click');
            })
            .on('click', '.attachments-wrapper', function() {
                $('.media-button-insert').html(buttonText);
            });
    });
})(jQuery);
