<?php
if (!defined('ABSPATH')) exit;

class HASS_Admin_Settings {
    public static function render() {
        if (!current_user_can('manage_options')) return;

        $servers = get_option('hass_widgets_servers', []);
        ?>
        <div class="wrap">
            <h1>Home Assistant Servers</h1>
            
            <h2>Add New Server</h2>
            <form id="hass-add-server-form" method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="server_name">Server Name</label></th>
                        <td>
                            <input type="text" id="server_name" name="server_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="server_url">Server URL</label></th>
                        <td>
                            <input type="url" id="server_url" name="server_url" class="regular-text" placeholder="https://your-home-assistant:8123" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="server_token">API Token</label></th>
                        <td>
                            <input type="password" id="server_token" name="server_token" class="regular-text" required>
                            <p class="description">Long-lived access token from your Home Assistant profile</p>
                        </td>
                    </tr>
                </table>
                <?php wp_nonce_field('hass_add_server_nonce', 'hass_server_nonce'); ?>
                <button type="submit" class="button button-primary">Add Server</button>
            </form>

            <h2>Existing Servers</h2>
            <?php if (empty($servers)): ?>
                <p>No servers configured yet.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servers as $id => $server): ?>
                            <tr>
                                <td><?php echo esc_html($server['name']); ?></td>
                                <td><?php echo esc_url($server['url']); ?></td>
                                <td>
                                    <span class="connection-status">Not tested</span>
                                    <button type="button" class="button hass-test-connection" data-server-id="<?php echo esc_attr($id); ?>">Test Connection</button>
                                </td>
                                <td>
                                    <button type="button" class="button button-danger hass-delete-server" data-server-id="<?php echo esc_attr($id); ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Add server form
            $('#hass-add-server-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitButton = form.find(':submit');
                
                submitButton.prop('disabled', true).text('Adding...');
                
                $.post(ajaxurl, {
                    action: 'hass_add_server',
                    name: $('#server_name').val(),
                    url: $('#server_url').val(),
                    token: $('#server_token').val(),
                    nonce: '<?php echo wp_create_nonce('hass_widgets_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Server added successfully!');
                        form[0].reset();
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                    submitButton.prop('disabled', false).text('Add Server');
                }).fail(function() {
                    alert('Request failed');
                    submitButton.prop('disabled', false).text('Add Server');
                });
            });

            // Delete server
            $('.hass-delete-server').on('click', function() {
                var button = $(this);
                var serverId = button.data('server-id');
                
                if (!confirm('Are you sure you want to delete this server?')) return;
                
                $.post(ajaxurl, {
                    action: 'hass_delete_server',
                    id: serverId,
                    nonce: '<?php echo wp_create_nonce('hass_widgets_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting server');
                    }
                });
            });

            // Test connection
            $('.hass-test-connection').on('click', function() {
                var button = $(this);
                var serverId = button.data('server-id');
                var statusElement = button.siblings('.connection-status');
                
                button.prop('disabled', true).text('Testing...');
                statusElement.text('Testing...').removeClass('connected error');
                
                $.post(ajaxurl, {
                    action: 'hass_test_connection',
                    server_id: serverId,
                    nonce: '<?php echo wp_create_nonce('hass_widgets_nonce'); ?>'
                }, function(response) {
                    button.prop('disabled', false).text('Test Connection');
                    
                    if (response.success) {
                        statusElement.text('Connected').addClass('connected');
                    } else {
                        statusElement.text('Error: ' + response.data).addClass('error');
                    }
                }).fail(function() {
                    button.prop('disabled', false).text('Test Connection');
                    statusElement.text('Request failed').addClass('error');
                });
            });
        });
        </script>
        <?php
    }

    // Test connection method
    public static function test_connection($server_id) {
        $servers = get_option('hass_widgets_servers', []);
        $server = $servers[$server_id] ?? null;
        
        if (!$server) {
            return new WP_Error('server_not_found', 'Server not found');
        }

        $url = rtrim($server['url'], '/') . '/api/';
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $server['token'],
                'Content-Type' => 'application/json'
            ],
            'timeout' => 10
        ];

        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code === 200 && isset($data['message']) && $data['message'] === 'API running.') {
            return true;
        }

        return new WP_Error('connection_failed', 'Connection failed: ' . $body);
    }
}
