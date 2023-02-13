<?php

/**
 * Convoworks GPT
 *
 * Plugin Name: Convoworks GPT
 * Description: GPT related workflow components
 * UID: convoworks-gpt
 * Plugin URI: https://github.com/zef-dev/convoworks-gpt
 * Update URI: https://wpdemo.convoworks.com/wp-content/uploads/deploy/convoworks-gpt/info.json
 * Author: ZEF Development
 * Version: 1.00.00
 * Author URI: https://zef.dev
 * Text Domain: convoworks-gpt
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'CONVO_GPT_VERSION', '1.0.0' );
define( 'CONVO_GPT_DIR', __DIR__);
define( 'CONVO_GPT_URL' , plugin_dir_url( __FILE__));
define( 'CONVO_GPT_PATH' , __FILE__);

require_once __DIR__.'/vendor/autoload.php';

function run_convoworks_gpt_plugin() {
    $plugin = new \Convo\Gpt\GptPlugin();
    $plugin->register();
}
run_convoworks_gpt_plugin();


function convoworks_gpt_check_for_updates($update, $plugin_data, $plugin_file) {
    static $response = false;
    
    if (empty($plugin_data['UpdateURI']) || !empty($update)) {
        return $update;
    }
    
    if ($response === false) {
        $response = wp_remote_get($plugin_data['UpdateURI']);
    }
    
    if ($response instanceof \WP_Error || empty($response['body'])) {
        return $update;
    }
    
    $custom_plugin_data = json_decode($response['body'], true);
    
    if (!empty($custom_plugin_data[$plugin_file])) {
        return $custom_plugin_data[$plugin_file];
    }
    
    return $update;
}

add_filter( 'update_plugins_wpdemo.convoworks.com', 'convoworks_gpt_check_for_updates', 10, 3);


