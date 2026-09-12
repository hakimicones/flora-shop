jQuery(document).ready(function($) {
    'use strict';

    // Pack form: Add product row
    var packProductIndex = 100;
    $('#flora-add-pack-product').on('click', function() {
        var template = $('#flora-pack-product-template').html();
        if (template) {
            var html = template.replace(/\{\{index\}\}/g, packProductIndex++);
            $('#flora-pack-products tbody').append(html);
        }
    });

    // Remove row
    $(document).on('click', '.flora-remove-row', function(e) {
        e.preventDefault();
        $(this).closest('tr').remove();
    });

    // Settings: Add cart discount row
    var cdIndex = 50;
    $('#flora-add-cart-discount').on('click', function() {
        var html = '<tr>' +
            '<td><input type="number" step="0.01" name="cart_discounts[' + cdIndex + '][min_total]" class="regular-text" value="100"></td>' +
            '<td><input type="number" name="cart_discounts[' + cdIndex + '][percent]" class="small-text" min="1" max="100" value="5"></td>' +
            '<td><button type="button" class="button flora-remove-row">Supprimer</button></td>' +
            '</tr>';
        $('#flora-cart-discounts tbody').append(html);
        cdIndex++;
    });

    // Media uploader for images
    var floraUploadFrame = null;

    $(document).on('click', '.flora-upload-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var target = $(this).data('target');

        // On multiple clicks, reuse but reset the frame.
        if (floraUploadFrame) {
            floraUploadFrame.off('select');
            floraUploadFrame.open();
            return;
        }

        floraUploadFrame = wp.media({
            title: 'Choisir une image',
            button: { text: 'Utiliser cette image' },
            multiple: false,
            library: { type: 'image' }
        });

        floraUploadFrame.on('select', function() {
            var attachment = floraUploadFrame.state().get('selection').first().toJSON();
            $('#' + target).val(attachment.url);
        });

        floraUploadFrame.open();
    });
});
