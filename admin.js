jQuery(document).ready(function($){
    $('.select-media').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const target = $(button.data('target'));
        const frame = wp.media({
            title: 'Chọn ảnh',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Sử dụng ảnh này' }
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            target.val(attachment.url);
        });
        frame.open();
    });
});
