<?php
class HASS_Admin_Settings {
    public static function render() {
        $servers = get_option('hass_widgets_servers', array());
        ?>
        <div class="wrap">
            <h1>Home Assistant Settings</h1>

            <div class="hass-widgets-card">
                <h2>Add New Server</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="hass_add_server">
                    <?php wp_nonce_field('hass_add_server', 'hass_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="server_name">Server Name</label></th>
                            <td>
                                <input type="text" id="server_name" name="hass_widgets_servers[new][name]" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="server_url">Server URL</label></th>
                            <td>
                                <input type="url" id="server_url" name="hass_widgets_servers[new][url]" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="server_token">Access Token</label></th>
                            <td>
                                <input type="password" id="server_token" name="hass_widgets_servers[new][token]" class="regular-text" required>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Add Server'); ?>
                </form>
            </div>

            <?php if (!empty($servers)) : ?>
            <div class="hass-widgets-card">
                <h2>Configured Servers</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servers as $id => $server) : ?>
                        <tr>
                            <td><?php echo esc_html($server['name']); ?></td>
                            <td><?php echo esc_html($server['url']); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin-post.php?action=hass_delete_server&server=' . $id); ?>" class="button button-danger">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
