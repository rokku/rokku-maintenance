jQuery(document).ready(function($) {
    // Media Uploader
    $('#rokkmamo-upload-logo').on('click', function(e) {
        e.preventDefault();

        const frame = wp.media({
            title: rokkmamoL10n.selectLogo,
            button: {
                text: rokkmamoL10n.useLogo
            },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#rokkmamo_logo_id').val(attachment.id);
            $('#rokkmamo-logo-preview').html(
                $('<img>', {
                    src: attachment.url,
                    style: 'max-width:200px;'
                })
            );
        });

        frame.open();
    });
}); 