<?php
/*
Plugin Name: Home Assistant Integration
Description: Manage Home Assistant servers, items, and widgets with shortcodes.
Version: 1.0
Author: You
*/

if (!defined('ABSPATH')) exit;
// Include all required classes
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/widgets.php';
require_once plugin_dir_path(__FILE__) . 'includes/widgets-add.php';
require_once plugin_dir_path(__FILE__) . 'includes/hass-items.php';
require_once plugin_dir_path(__FILE__) . 'includes/api-client.php';
require_once plugin_dir_path(__FILE__) . 'includes/widget-class.php';

class HomeAssistantPlugin {

    public function __construct() {
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        // Admin menu
        add_action('admin_menu', [$this, 'register_menus']);

        // Admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        // Shortcodes
        add_shortcode('hass_widget', [$this, 'shortcode_widget']);
        add_shortcode('hass_item', [$this, 'shortcode_item']);
        add_shortcode('hass_if', [$this, 'shortcode_hass_if']);

        // Ajax handlers
        add_action('wp_ajax_hass_add_server', [$this, 'ajax_add_server']);
        add_action('wp_ajax_hass_add_item', [$this, 'ajax_add_item']);
        add_action('wp_ajax_hass_delete_server', [$this, 'ajax_delete_server']);
        add_action('wp_ajax_hass_delete_item', [$this, 'ajax_delete_item']);
        add_action('wp_ajax_hass_add_widget', [$this, 'ajax_add_widget']);
        add_action('wp_ajax_hass_delete_widget', [$this, 'ajax_delete_widget']);
        add_action('wp_ajax_hass_refresh_item', [$this, 'ajax_refresh_item']);

        // Widgets
        add_action('widgets_init', function() {
            register_widget('HASS_Widget');
        });

// Add this with your other AJAX actions in the constructor
add_action('wp_ajax_hass_test_connection', [$this, 'ajax_test_connection']);
}
// Add this method with your other AJAX methods
public function ajax_test_connection() {
    check_ajax_referer('hass_widgets_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

    $server_id = sanitize_text_field($_POST['server_id']);
    $result = HASS_Admin_Settings::test_connection($server_id);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success();
    }
}
    // ==== Admin Pages ====
    public function register_menus() {
        add_menu_page(
            'Home Assistant Widgets',
            'HASS Widgets',
            'manage_options',
            'hass-widgets',
            [$this, 'render_widgets_page'],
            'dashicons-admin-generic'
        );
        add_submenu_page('hass-widgets', 'Add Widget', 'Add Widget', 'manage_options', 'hass-widgets-add', [$this, 'render_widgets_add_page']);
        add_submenu_page('hass-widgets', 'Items', 'Items', 'manage_options', 'hass-items', [$this, 'render_items_page']);
        add_submenu_page('hass-widgets', 'Servers', 'Servers', 'manage_options', 'hass-servers', [$this, 'render_servers_page']);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'hass') === false) return;

        wp_enqueue_style('hass-admin', plugin_dir_url(__FILE__) . 'assets/admin.css');
        wp_enqueue_script('hass-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], null, true);

        wp_localize_script('hass-admin', 'hass_widgets', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('hass_widgets_nonce')
        ]);
    }
    public static function cleanup_plugin_data() {
        // Remove all plugin options
        delete_option('hass_widgets_servers');
        delete_option('hass_widgets_items');
        delete_option('hass_widgets_widgets');
        
        // Remove any transients created by the plugin
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hass_item_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hass_item_%'");
        
        // Remove any scheduled events
        wp_clear_scheduled_hook('hass_refresh_items_event');
        
        // Optional: Remove any custom database tables if you created them
        // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hass_custom_table");
    }

public static function deactivate() {
    // Remove all plugin data on deactivation
    self::cleanup_plugin_data();
}

    // ==== Pages ====
    public function render_widgets_page() {
        echo '<div class="wrap"><h1>Home Assistant Integration</h1>';
        echo '<p>Welcome! This plugin lets you connect WordPress with your Home Assistant setup.</p>';
        echo '<ul>
                <li><strong>Servers</strong> – Add your Home Assistant instances.</li>
                <li><strong>Items</strong> – Link to entities (lights, sensors, etc.).</li>
                <li><strong>Widgets</strong> – Create shortcodes that display entity values.</li>
              </ul>';
        echo '<p>Use <code>[hass_widget id="..."]</code> in posts/pages to embed a widget.</p></div>';

        if (class_exists('HASS_Widgets')) {
            HASS_Widgets::render();
        }
    }

    public function render_widgets_add_page() {
        if (class_exists('HASS_Widgets_Add')) {
            HASS_Widgets_Add::render();
        }
    }

    public function render_items_page() {
        if (class_exists('HASS_Items')) {
            HASS_Items::render();
        }
    }

    public function render_servers_page() {
        if (class_exists('HASS_Admin_Settings')) {
            HASS_Admin_Settings::render();
        }
    }




    // ==== Pages ====
    // ==== Ajax Handlers ====
    public function ajax_add_server() {
        check_ajax_referer('hass_widgets_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

        $servers = get_option('hass_widgets_servers', []);
        $id = uniqid('srv_');
        $servers[$id] = [
            'name' => sanitize_text_field($_POST['name']),
            'url'  => esc_url_raw($_POST['url']),
            'token'=> sanitize_text_field($_POST['token'])
        ];
        update_option('hass_widgets_servers', $servers);
        wp_send_json_success(['id' => $id]);
    }

public function ajax_add_item() {
    check_ajax_referer('hass_widgets_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

    // Validate required fields
    if (empty($_POST['id']) || empty($_POST['server']) || empty($_POST['entity'])) {
        wp_send_json_error('Missing required fields');
    }

    $items = get_option('hass_widgets_items', []);
    $id = sanitize_key($_POST['id']);
    
    // Check if ID already exists
    if (isset($items[$id])) {
        wp_send_json_error('Item ID already exists');
    }

    $items[$id] = [
        'server'    => sanitize_text_field($_POST['server']),
        'entity'    => sanitize_text_field($_POST['entity']),
        'attribute' => sanitize_text_field($_POST['attribute'] ?? ''),
        'cache_time' => intval($_POST['cache_time'] ?? 30)
];
    
    update_option('hass_widgets_items', $items);
    wp_send_json_success(['id' => $id]);
}

    public function ajax_delete_server() {
        check_ajax_referer('hass_widgets_nonce', 'nonce');
        $servers = get_option('hass_widgets_servers', []);
        unset($servers[$_POST['id']]);
        update_option('hass_widgets_servers', $servers);
        wp_send_json_success();
    }

    public function ajax_delete_item() {
        check_ajax_referer('hass_widgets_nonce', 'nonce');
        $items = get_option('hass_widgets_items', []);
        unset($items[$_POST['id']]);
        update_option('hass_widgets_items', $items);
        wp_send_json_success();
    }


public function ajax_add_widget() {
    check_ajax_referer('hass_widgets_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Permission denied');

    error_log('Widget data received: ' . print_r($_POST, true)); // Debug

    // Check if we have widget_data (serialized form) or individual fields
    if (isset($_POST['widget_data'])) {
        // Parse the serialized form data
        parse_str($_POST['widget_data'], $form_data);
        error_log('Parsed form data: ' . print_r($form_data, true)); // Debug
    } else {
        // Use individual POST fields directly
        $form_data = $_POST;
    }

    // Validate required fields with proper fallbacks
    $widget_title = sanitize_text_field($form_data['widget_title'] ?? '');
    $widget_template = wp_kses_post($form_data['widget_template'] ?? '');
    $widget_refresh = intval($form_data['widget_refresh'] ?? 30);
    
    // Generate ID if not provided or empty
    $widget_id = !empty($form_data['widget_id']) ? sanitize_key($form_data['widget_id']) : uniqid('wid_');
    

    error_log('Generated widget ID: ' . $widget_id); // Debug

    if (empty($widget_title) || empty($widget_template)) {
        wp_send_json_error('Title and template are required');
    }

    $widgets = get_option('hass_widgets_widgets', []);
    $widgets[$widget_id] = [
        'title' => $widget_title,
        'template' => $widget_template,
        'refresh' => $widget_refresh
    ];
    
    error_log('Saving widgets: ' . print_r($widgets, true)); // Debug
    
    update_option('hass_widgets_widgets', $widgets);
    wp_send_json_success(['id' => $widget_id]);
}


    public function ajax_delete_widget() {
        check_ajax_referer('hass_widgets_nonce', 'nonce');
        $widgets = get_option('hass_widgets_widgets', []);
        unset($widgets[$_POST['id']]);
        update_option('hass_widgets_widgets', $widgets);
        wp_send_json_success();
    }

    public function ajax_refresh_item() {
        check_ajax_referer('hass_widgets_nonce', 'nonce');
        $id = sanitize_key($_POST['item_id']);
        $value = $this->get_item_value($id);
        wp_send_json_success($value);
    }

public function get_item_value($id) {
    $cache_key = 'hass_item_' . $id;
    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached;

    $items = get_option('hass_widgets_items', []);
    $servers = get_option('hass_widgets_servers', []);
    if (!isset($items[$id])) return '';

    $item = $items[$id];
    $server = $servers[$item['server']] ?? null;
    if (!$server) return '';

    $url = rtrim($server['url'], '/') . '/api/states/' . $item['entity'];
    $args = ['headers'=>['Authorization'=>'Bearer '.$server['token']],'timeout'=>10];
    $resp = wp_remote_get($url, $args);
    if (is_wp_error($resp)) return 'Error';
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    $value = $item['attribute'] ? ($data['attributes'][$item['attribute']] ?? '') : ($data['state'] ?? '');
    
    // Use per-item cache time or default to 30 seconds
    $cache_time = $item['cache_time'] ?? 30;
    set_transient($cache_key, $value, $cache_time);
    
    return $value;
}


    // ==== Shortcodes ====
public function shortcode_widget($atts) {
    $atts = shortcode_atts(['id'=>''], $atts);
    $widgets = get_option('hass_widgets_widgets', []);
    $wid = $widgets[$atts['id']] ?? null;
    if (!$wid) return '';
    
    // Parse shortcodes inside template and convert line breaks to HTML
    $content = do_shortcode($wid['template']);
    return wpautop($content); // <br> or <p> inserted automatically
}

public function shortcode_item($atts) {
    $atts = shortcode_atts(['id'=>''], $atts);
    $value = $this->get_item_value($atts['id']);
    
    // Allow HTML and line breaks
    return wp_kses_post($value); // allows safe HTML
}


    public function shortcode_hass_if($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => '',
            'operator' => '==',
            'value' => ''
        ], $atts);

        $id = $atts['id'];
        $op = $atts['operator'];
        $val = $atts['value'];

        $item_value = $this->get_item_value($id);

        $inner = do_shortcode($content);

        // parse inner for [hass_else_if] / [hass_else]
        preg_match_all('/\[hass_else_if[^\]]*\](.*?)((?=\[hass_else_if)|(?=\[hass_else\])|$)/s', $inner, $elseif_matches, PREG_SET_ORDER);
        preg_match('/\[hass_else\](.*)$/s', $inner, $else_match);

        // Evaluate main if
        $ok = false;
        switch($op){
            case '==': $ok = ($item_value == $val); break;
            case '!=': $ok = ($item_value != $val); break;
            case '>':  $ok = ($item_value > $val); break;
            case '<':  $ok = ($item_value < $val); break;
        }
        if($ok) return preg_replace('/\[hass_else_if[^\]]*\].*|\[hass_else\].*/s','',$inner);

        // Evaluate else_if
        foreach($elseif_matches as $m){
            preg_match('/id="([^"]+)" operator="([^"]+)" value="([^"]+)"/', $m[0], $em);
            if(!$em) continue;
            $eid=$em[1]; $eop=$em[2]; $eval=$em[3];
            $evalue = $this->get_item_value($eid);
            $ok=false;
            switch($eop){
                case '==': $ok = ($evalue == $eval); break;
                case '!=': $ok = ($evalue != $eval); break;
                case '>':  $ok = ($evalue > $eval); break;
                case '<':  $ok = ($evalue < $eval); break;
            }
            if($ok) return do_shortcode($m[1]);
        }

        // Else
        if(isset($else_match[1])) return do_shortcode($else_match[1]);

        return '';
    }
}


new HomeAssistantPlugin();
