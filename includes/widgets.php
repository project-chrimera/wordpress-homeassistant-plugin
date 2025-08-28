<?php
if (!defined('ABSPATH')) exit;

class HASS_Widgets {

    public static function render() {
        if (!current_user_can('manage_options')) return;

        $items = get_option('hass_widgets_items', []);
        $widgets = get_option('hass_widgets_widgets', []);

        ?>
        <div class="wrap">
            <h1>HASS Widgets</h1>

            <table class="widefat fixed striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($widgets)): ?>
                    <?php foreach ($widgets as $id => $widget): ?>
                        <tr>
                            <td><?php echo esc_html($id); ?></td>
                            <td><?php echo esc_html($widget['title']); ?></td>
                            <td>
                                <input type="text" readonly class="hass-widget-shortcode" 
                                    style="width:200px;" 
                                    value='[hass_widget id="<?php echo esc_attr($id); ?>"]'>
                                <button class="button copy-shortcode-btn" data-clip="[hass_widget id='<?php echo esc_attr($id); ?>']">Copy</button>
                                <a href="<?php echo admin_url('admin.php?page=hass-widgets-add&widget=' . urlencode($id)); ?>">Edit</a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=hass_delete_widget&widget=' . urlencode($id)), 'hass_delete_widget'); ?>" 
                                   onclick="return confirm('Are you sure you want to delete this widget?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No widgets found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <p>
                <a class="button button-primary" href="<?php echo admin_url('admin.php?page=hass-widgets-add'); ?>">Add New Widget</a>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($){
            $('.copy-shortcode-btn').on('click', function(){
                var text = $(this).data('clip');
                navigator.clipboard.writeText(text).then(function(){
                    alert('Copied: ' + text);
                });
            });
        });
        </script>
        <?php
    }
}

