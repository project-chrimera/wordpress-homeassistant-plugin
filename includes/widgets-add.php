<?php
if (!defined('ABSPATH')) exit;

class HASS_Widgets_Add {
    public static function render() {
        if (!current_user_can('manage_options')) return;

        $widgets = get_option('hass_widgets_widgets', []);
        $items   = get_option('hass_widgets_items', []);

        // Check if editing an existing widget
        $editing_id = $_GET['widget'] ?? '';
        $edit_widget = $editing_id && isset($widgets[$editing_id]) ? $widgets[$editing_id] : null;
        $title = $edit_widget['title'] ?? '';
        $template = $edit_widget['template'] ?? '';
        $refresh = $edit_widget['refresh'] ?? 30;

        ?>
        <div class="wrap">
            <h1><?php echo $edit_widget ? 'Edit Widget' : 'Add New Widget'; ?></h1>

            <form id="hass-add-widget-form">
                <table class="form-table">
                    <tr>
                        <th><label for="widget_title">Widget Title</label></th>
                        <td>
                            <input type="text" id="widget_title" name="widget_title" class="regular-text" value="<?php echo esc_attr($title); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="widget_template">Widget Template</label></th>
                        <td>
                            <?php
                            $editor_id = 'widget_template';
                            $settings = array(
                                'textarea_name' => 'widget_template',
                                'media_buttons' => false,
                                'teeny' => true,
                                'textarea_rows' => 10,
                            );
                            wp_editor($template, $editor_id, $settings);
                            ?>
                            <p>Select an item to insert:</p>

<select id="hass-item-dropdown">
    <option value="">-- Select Item --</option>
    <?php foreach ($items as $id => $item): ?>
        <option value="<?php echo esc_attr($id); ?>">
            <?php echo esc_html($id . ' - ' . $item['entity']); ?>
            <?php if (!empty($item['attribute'])): ?>
                (<?php echo esc_html($item['attribute']); ?>)
            <?php endif; ?>
        </option>
    <?php endforeach; ?>
</select>


                            <button type="button" id="insert-item-btn" class="button">Insert Item</button>
                            <button type="button" id="insert-conditional-btn" class="button">Insert Conditional</button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="widget_refresh">Auto Refresh (seconds)</label></th>
                        <td>
                            <input type="number" id="widget_refresh" name="widget_refresh" value="<?php echo esc_attr($refresh); ?>" min="1">
                        </td>
                    </tr>
                </table>

                <input type="hidden" name="widget_id" value="<?php echo esc_attr($editing_id); ?>">
                <button type="submit" class="button button-primary"><?php echo $edit_widget ? 'Update Widget' : 'Create Widget'; ?></button>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($){
// Insert single item shortcode
$('#insert-item-btn').on('click', function(){
    var itemId = $('#hass-item-dropdown').val();
    if(!itemId) return;
    var shortcode = '[hass_item id="' + itemId + '"]';
    
    if (tinymce.get('widget_template')) {
        // Use insertContent instead of execCommand to prevent double insertion
        tinymce.get('widget_template').insertContent(shortcode);
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
            // Insert conditional shortcode block
            $('#insert-conditional-btn').on('click', function(){
                var itemId = $('#hass-item-dropdown').val();
                if(!itemId) return;

                var conditionalTemplate =
'[hass_if id="' + itemId + '" operator==" value="value1"]\n' +
'    <!-- content if value1 -->\n' +
'[hass_else_if id="' + itemId + '" operator==" value="value2"]\n' +
'    <!-- content if value2 -->\n' +
'[hass_else]\n' +
'    <!-- content if none match -->\n' +
'[/hass_if]\n';

                if (tinymce.get('widget_template')) {
                    tinymce.get('widget_template').execCommand('mceInsertContent', false, conditionalTemplate);
                } else {
                    var textarea = $('#widget_template');
                    var start = textarea[0].selectionStart;
                    var end = textarea[0].selectionEnd;
                    var val = textarea.val();
                    textarea.val(val.substring(0,start) + conditionalTemplate + val.substring(end));
                }
            });

// Submit form
$('#hass-add-widget-form').on('submit', function(e){
    e.preventDefault();
    var form = $(this);
    var submitButton = form.find(':submit');
    submitButton.prop('disabled', true).text('<?php echo $edit_widget ? 'Updating...' : 'Creating...'; ?>');

    // Get the TinyMCE content if available
    var widgetTemplate = '';
    if (typeof tinymce !== 'undefined' && tinymce.get('widget_template')) {
        widgetTemplate = tinymce.get('widget_template').getContent();
    } else {
        widgetTemplate = $('#widget_template').val();
    }

    // Get other form values
    var widgetTitle = $('#widget_title').val();
    var widgetRefresh = $('#widget_refresh').val();
    var widgetId = $('input[name="widget_id"]').val();

    // If creating new widget and no ID provided, generate one
    if (!widgetId && !<?php echo $edit_widget ? 'true' : 'false'; ?>) {
        widgetId = 'wid_' + Date.now();
    }

    $.post(ajaxurl, {
        action: 'hass_add_widget',
        widget_title: widgetTitle,
        widget_template: widgetTemplate,
        widget_refresh: widgetRefresh,
        widget_id: widgetId,
        nonce: hass_widgets.nonce
    }, function(response){
        if(response.success){
            alert('Widget <?php echo $edit_widget ? 'updated' : 'created'; ?> successfully');
            if(!<?php echo $edit_widget ? 'true' : 'false'; ?>) {
                form[0].reset();
                // Clear TinyMCE editor if it exists
                if (typeof tinymce !== 'undefined' && tinymce.get('widget_template')) {
                    tinymce.get('widget_template').setContent('');
                }
            } else {
                location.reload();
            }
        } else {
            alert('Error: ' + response.data);
        }
        submitButton.prop('disabled', false).text('<?php echo $edit_widget ? 'Update Widget' : 'Create Widget'; ?>');
    }).fail(function(){
        alert('Request failed');
        submitButton.prop('disabled', false).text('<?php echo $edit_widget ? 'Update Widget' : 'Create Widget'; ?>');
    });
});
        </script>
        <?php
    }
}

