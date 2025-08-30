<?php
if (!defined('ABSPATH')) exit;

class HASS_Items {
    public static function render() {
        if (!current_user_can('manage_options')) return;

        $items = get_option('hass_widgets_items', []);
        $servers = get_option('hass_widgets_servers', []);
        ?>
        <div class="wrap">
            <h1>Home Assistant Items</h1>
            
            <h2>Add New Item</h2>
            <form id="hass-add-item-form" method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="item_id">Item ID</label></th>
                        <td>
                            <input type="text" id="item_id" name="item_id" class="regular-text" required>
                            <p class="description">Unique identifier for this item (e.g., living_room_temperature)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="item_server">Server</label></th>
                        <td>
                            <select id="item_server" name="item_server" required>
                                <option value="">-- Select Server --</option>
                                <?php foreach ($servers as $id => $server): ?>
                                    <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($server['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="item_entity">Entity ID</label></th>
                        <td>
                            <input type="text" id="item_entity" name="item_entity" class="regular-text" placeholder="sensor.living_room_temperature" required>
                            <p class="description">Home Assistant entity ID (e.g., sensor.temperature, light.living_room)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="item_attribute">Attribute (Optional)</label></th>
                        <td>
                            <input type="text" id="item_attribute" name="item_attribute" class="regular-text" placeholder="unit_of_measurement">
                            <p class="description">Optional attribute name if you want to display a specific attribute instead of the state</p>
                        </td>
                    </tr>
<tr>
    <th><label for="item_cache_time">Cache Time (seconds)</label></th>
    <td>
        <input type="number" id="item_cache_time" name="item_cache_time" class="regular-text" value="30" min="1" max="3600">
        <p class="description">How long to cache this item's value (default: 30 seconds)</p>
    </td>
</tr>
                </table>
                <button type="submit" class="button button-primary">Add Item</button>
            </form>

            <h2>Existing Items</h2>
            <?php if (empty($items)): ?>
                <p>No items configured yet.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Entity</th>
                            <th>Server</th>
                            <th>Attribute</th>
                            th>Cache Time</th> 
                            <th>Current Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $id => $item): 
                            $server_name = isset($servers[$item['server']]) ? $servers[$item['server']]['name'] : 'Unknown Server';
                        ?>
                            <tr>
                                <td><?php echo esc_html($id); ?></td>
                                <td><?php echo esc_html($item['entity']); ?></td>
                                <td><?php echo esc_html($server_name); ?></td>
                                <td><?php echo esc_html($item['attribute'] ?? 'state'); ?></td>
                                <td><?php echo esc_html($item['cache_time'] ?? 30); ?>s</td>
                                <td class="item-value" data-item-id="<?php echo esc_attr($id); ?>">
                                    <span class="dashicons dashicons-update"></span> Loading...
                                </td>
                                <td>
                                    <button type="button" class="button hass-refresh-item" data-item-id="<?php echo esc_attr($id); ?>">Refresh</button>
                                    <button type="button" class="button button-danger hass-delete-item" data-item-id="<?php echo esc_attr($id); ?>">Delete</button>
                                    <button type="button" class="button copy-shortcode-btn" data-clipboard-text='[hass_item id="<?php echo esc_attr($id); ?>"]'>Copy Shortcode</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Add item form
            $('#hass-add-item-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitButton = form.find(':submit');
                
                submitButton.prop('disabled', true).text('Adding...');
                
                $.post(ajaxurl, {
                    action: 'hass_add_item',
                    id: $('#item_id').val(),
                    server: $('#item_server').val(),
                    entity: $('#item_entity').val(),
                    attribute: $('#item_attribute').val(),
                    nonce: hass_widgets.nonce
                }, function(response) {
                    if (response.success) {
                        alert('Item added successfully!');
                        form[0].reset();
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                    submitButton.prop('disabled', false).text('Add Item');
                }).fail(function() {
                    alert('Request failed');
                    submitButton.prop('disabled', false).text('Add Item');
                });
            });

            // Delete item
            $('.hass-delete-item').on('click', function() {
                var button = $(this);
                var itemId = button.data('item-id');
                
                if (!confirm('Are you sure you want to delete this item?')) return;
                
                $.post(ajaxurl, {
                    action: 'hass_delete_item',
                    id: itemId,
                    nonce: hass_widgets.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting item');
                    }
                });
            });

            // Refresh item value
            $('.hass-refresh-item').on('click', function() {
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

            // Load item values on page load
            $('.item-value').each(function() {
                var element = $(this);
                var itemId = element.data('item-id');
                
                $.post(ajaxurl, {
                    action: 'hass_refresh_item',
                    item_id: itemId,
                    nonce: hass_widgets.nonce
                }, function(response) {
                    if (response.success) {
                        element.text(response.data);
                    } else {
                        element.text('Error');
                    }
                }).fail(function() {
                    element.text('Request failed');
                });
            });

            // Copy shortcode
            $('.copy-shortcode-btn').on('click', function(){
                var text = $(this).data('clipboard-text');
                navigator.clipboard.writeText(text).then(function(){
                    alert('Copied: ' + text);
                });
            });
        });
        </script>
        <?php
    }
}
