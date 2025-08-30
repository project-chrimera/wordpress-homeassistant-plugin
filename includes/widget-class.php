<?php
// includes/widget-class.php

class HASS_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'hass_widget',
            'Home Assistant Widget',
            array('description' => 'Display Home Assistant entity values')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        if (!empty($instance['widget_id'])) {
            echo do_shortcode('[hass_widget id="' . $instance['widget_id'] . '"]');
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $widgets = get_option('hass_widgets_widgets', array());
        $title = !empty($instance['title']) ? $instance['title'] : '';
if (!empty($instance['widget_id'])) {
    $content = do_shortcode('[hass_widget id="'.$instance['widget_id'].'"]');
    echo wpautop($content); // Line breaks and HTML parsed
}
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('widget_id'); ?>">Select Widget:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('widget_id'); ?>" name="<?php echo $this->get_field_name('widget_id'); ?>">
                <option value="">Select a widget</option>
                <?php foreach ($widgets as $id => $widget) : ?>
                <option value="<?php echo esc_attr($id); ?>" <?php selected($widget_id, $id); ?>><?php echo esc_html($widget['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['widget_id'] = (!empty($new_instance['widget_id'])) ? strip_tags($new_instance['widget_id']) : '';
        return $instance;
    }
}
