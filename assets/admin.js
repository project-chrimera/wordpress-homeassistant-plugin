// assets/admin.js

jQuery(document).ready(function($) {

    // Show toast notifications
    function showNotice(message, type='success') {
        const notice = $('<div class="hass-notice ' + type + '">' + message + '</div>');
        $('body').append(notice);
        notice.fadeIn(200).delay(2000).fadeOut(400, function(){ $(this).remove(); });
    }

    // Test server connection
    $('.hass-test-connection').on('click', function() {
        var button = $(this);
        var serverId = button.data('server-id');
        var statusElement = button.siblings('.connection-status');
        
        button.addClass('loading');
        statusElement.text('Testing...');
        
        $.post(ajaxurl, {
            action: 'hass_test_connection',
            server_id: serverId,
            nonce: hass_widgets.nonce
        }, function(response) {
            button.removeClass('loading');
            
            if (response.success) {
                statusElement.text('Connected').addClass('connected').removeClass('error');
            } else {
                statusElement.text('Error').addClass('error').removeClass('connected');
            }
        }).fail(function() {
            button.removeClass('loading');
            statusElement.text('Request failed').addClass('error').removeClass('connected');
        });
    });

    // Refresh item value
    $('.hass-refresh-item').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var itemId = button.data('item-id');
        var valueElement = $('.item-value[data-item-id="' + itemId + '"]');
        
        valueElement.html('<span class="dashicons dashicons-update"></span> Loading...');
        
        $.post(ajaxurl, {
            action: 'hass_refresh_item',
            item_id: itemId,
            nonce: hass_widgets.nonce
        }, function(response) {
            if (response.success) {
                valueElement.text(response.data);
            } else {
                valueElement.text('Error');
            }
        }).fail(function() {
            valueElement.text('Request failed');
        });
    });

    // Add item form
    $('#hass-add-item-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitButton = form.find(':submit');
        
        submitButton.prop('disabled', true).text('Adding...');
        
        $.post(ajaxurl, {
            action: 'hass_add_item',
            item_data: form.serialize(),
            nonce: hass_widgets.nonce
        }, function(response) {
            if(response.success){
                showNotice('Item added successfully');
                form[0].reset();
                location.reload();
            } else {
                showNotice(response.data, 'error');
            }
            submitButton.prop('disabled', false).text('Add Item');
        }).fail(function() {
            showNotice('Request failed', 'error');
            submitButton.prop('disabled', false).text('Add Item');
        });
    });

    // Add widget form
    $('#hass-add-widget-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitButton = form.find(':submit');
        
        submitButton.prop('disabled', true).text('Creating...');
        
        $.post(ajaxurl, {
            action: 'hass_add_widget',
            widget_data: form.serialize(),
            nonce: hass_widgets.nonce
        }, function(response) {
            if(response.success){
                showNotice('Widget saved successfully');
                form[0].reset();
                location.reload();
            } else {
                showNotice(response.data, 'error');
            }
            submitButton.prop('disabled', false).text('Create Widget');
        }).fail(function() {
            showNotice('Request failed', 'error');
            submitButton.prop('disabled', false).text('Create Widget');
        });
    });

    // Load item values on page load
    $('.item-value').each(function() {
        var element = $(this);
        var itemId = element.data('item-id');
        
        $.post(ajaxurl, {
            action: 'hass_refresh_item',
            item_id: itemId,
            nonce: hass_widgets.nonce
        }, function(response) {
            if(response.success){
                element.text(response.data);
            } else {
                element.text('Error');
            }
        }).fail(function() {
            element.text('Request failed');
        });
    });

    // Copy shortcode button
    $('.copy-shortcode-btn').on('click', function(){
        var text = $(this).data('clip');
        navigator.clipboard.writeText(text).then(function(){
            showNotice('Copied: ' + text);
        });
    });

// In widgets-add.php - keep this version and improve it:
$('#insert-item-btn').on('click', function(){
    var itemId = $('#hass-item-dropdown').val();
    if(!itemId) return;
    var shortcode = '[hass_item id="' + itemId + '"]';
    
    if (typeof tinymce !== 'undefined' && tinymce.get('widget_template')) {
        var editor = tinymce.get('widget_template');
        editor.insertContent(shortcode);
    } else {
        var textarea = $('#widget_template');
        var start = textarea[0].selectionStart;
        var end = textarea[0].selectionEnd;
        var val = textarea.val();
        textarea.val(val.substring(0, start) + shortcode + val.substring(end));
        
        // Set cursor position after inserted content
        textarea[0].selectionStart = start + shortcode.length;
        textarea[0].selectionEnd = start + shortcode.length;
        textarea.focus();
    }
});


});
