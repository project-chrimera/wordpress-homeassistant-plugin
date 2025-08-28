<?php
// includes/api-client.php

class HASS_API_Client {
    
public static function test_connection($server_id) {
    $servers = get_option('hass_widgets_servers', array());
    
    if (!isset($servers[$server_id])) {
        return new WP_Error('server_not_found', 'Server not found');
    }
    
    $server = $servers[$server_id];
    $url = rtrim($server['url'], '/') . '/api/config';
    
    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $server['token'],
            'Content-Type' => 'application/json'
        ),
        'timeout' => 15
    );
    
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    
    if ($code === 200) {
        return true;
    }
    
    return new WP_Error('connection_failed', 'Connection failed with code: ' . $code);
}

    public static function get_entity_state($server_id, $entity_id, $attribute = null) {
        $servers = get_option('hass_widgets_servers', array());
        
        if (!isset($servers[$server_id])) {
            return new WP_Error('server_not_found', 'Server not found');
        }
        
        $server = $servers[$server_id];
        $url = rtrim($server['url'], '/') . '/api/states/' . $entity_id;
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $server['token'],
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($attribute) {
            return isset($data['attributes'][$attribute]) ? $data['attributes'][$attribute] : null;
        }
        
        return $data['state'];
    
        
}
}
