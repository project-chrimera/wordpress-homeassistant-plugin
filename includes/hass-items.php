<?php
// includes/hass-items.php

class HASS_Items {

    public static function init() {
        // nothing needed here for now
    }

    public static function render() {
        $items   = get_option('hass_widgets_items', array());
        $servers = get_option('hass_widgets_servers', array());
        ?>
        <div class="wrap">
            <h1>Home Assistant Items</h1>

            <!-- Add New Item -->
            <div class="hass-widgets-card">
                <h2>Add New Item</h2>
                <form id="hass-add-item-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="item_name">Item Name</label></th>
                            <td>
                                <input type="text" id="item_name" name="item_name" class="regular-text" required>
                                <p class="description">A friendly name for this item</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="item_server">Server</label></th>
                            <td>
                                <select id="item_server" name="item_server" required>
                                    <option value="">Select a server</option>
                                    <?php foreach ($servers as $id => $server) : ?>
                                        <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($server['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="item_entity">Entity ID</label></th>
                            <td>
                                <input type="text" id="item_entity" name="item_entity" class="regular-text" required>
                                <p class="description">The entity ID (e.g., light.living_room)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="item_attribute">Attribute (optional)</label></th>
                            <td>
                                <input type="text" id="item_attribute" name="item_attribute" class="regular-text">
                                <p class="description">Specific attribute to retrieve (leave blank for state)</p>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" class="button button-primary">Add Item</button>
                </form>
            </div>

            <!-- Existing Items -->
            <?php if (!empty($items)) : ?>
            <div class="hass-widgets-card">
                <h2>Configured Items</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Entity</th>
                            <th>Server</th>
                            <th>Current Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $id => $item) :
                            $server_name = isset($servers[$item['server']]['name']) ? $servers[$item['server']]['name'] : 'Unknown';
                            $value = HASS_API_Client::get_entity_state($item['server'], $item['entity'], $item['attribute']);
                            if (is_wp_error($value)) $value = 'Error: ' . $value->get_error_message();
                        ?>
                        <tr>
                            <td><?php echo esc_html($item['name']); ?></td>
                            <td><?php echo esc_html($item['entity']); ?><?php echo !empty($item['attribute']) ? '.' . esc_html($item['attribute']) : ''; ?></td>
                            <td><?php echo esc_html($server_name); ?></td>
                            <td class="item-value" data-item-id="<?php echo esc_attr($id); ?>">
                                <?php echo esc_html($value); ?>
                            </td>
                            <td>
                                <a href="#" class="button hass-refresh-item" data-item-id="<?php echo esc_attr($id); ?>">Refresh</a>
                                <a href="#" class="button button-danger hass-delete-item" data-item-id="<?php echo esc_attr($id); ?>">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($){
            // Add item
            $('#hass-add-item-form').on('submit', function(e){
                e.preventDefault();
                var form = $(this);
                $.post(ajaxurl, {
                    action: 'hass_add_item',
                    item_data: form.serialize(),
                    nonce: '<?php echo wp_create_nonce("hass_widgets_nonce"); ?>'
                }, function(response){
                    if(response.success){
                        alert('Item added successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }).fail(function(){ alert('Request failed'); });
            });

            // Refresh item value
            $('.hass-refresh-item').on('click', function(e){
                e.preventDefault();
                var itemId = $(this).data('item-id');
                var valueEl = $('.item-value[data-item-id="'+itemId+'"]');
                valueEl.html('<span class="dashicons dashicons-update"></span> Loading...');
                $.post(ajaxurl, {
                    action: 'hass_refresh_item',
                    item_id: itemId,
                    nonce: '<?php echo wp_create_nonce("hass_widgets_nonce"); ?>'
                }, function(response){
                    if(response.success) valueEl.text(response.data);
                    else valueEl.text('Error: ' + response.data);
                }).fail(function(){ valueEl.text('Request failed'); });
            });

            // Delete item
            $('.hass-delete-item').on('click', function(e){
                e.preventDefault();
                if(!confirm('Are you sure you want to delete this item?')) return;
                var itemId = $(this).data('item-id');
                $.post(ajaxurl, {
                    action: 'hass_delete_item',
                    item_id: itemId,
                    nonce: '<?php echo wp_create_nonce("hass_widgets_nonce"); ?>'
                }, function(response){
                    if(response.success) location.reload();
                    else alert('Error: ' + response.data);
                }).fail(function(){ alert('Request failed'); });
            });
        });
        </script>
        <?php
    }
}
