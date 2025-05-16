jQuery(document).ready(function($) {
    // Media Uploader
    $('#mm-upload-logo').on('click', function(e) {
        e.preventDefault();

        const frame = wp.media({
            title: rokkuMM.l10n.selectLogo,
            button: {
                text: rokkuMM.l10n.useLogo
            },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#mm_logo_id').val(attachment.id);
            $('#mm-logo-preview').html(
                $('<img>', {
                    src: attachment.url,
                    style: 'max-width:200px;'
                })
            );
        });

        frame.open();
    });
}); 