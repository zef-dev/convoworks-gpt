<?php

/**
 * Convoworks GPT
 *
 * Plugin Name: Convoworks GPT
 * Description: GPT related workflow components
 * UID: convoworks-gpt
 * Plugin URI: https://github.com/zef-dev/convoworks-gpt
 * Update URI: https://raw.githubusercontent.com/zef-dev/convoworks-gpt/main/update.json
 * Author: ZEF Development
 * Version: 0.16.1
 * Author URI: https://zef.dev
 * Text Domain: convoworks-gpt
 */

if (! defined('WPINC')) {
    die;
}

define('CONVO_GPT_VERSION', '0.16.0');
define('CONVO_GPT_DIR', __DIR__);
define('CONVO_GPT_URL', plugin_dir_url(__FILE__));
define('CONVO_GPT_PATH', __FILE__);

// Filesystem path where MCP session files are stored (currently FS‑based, moving to DB in a future release)
if (!defined('CONVO_GPT_MCP_SESSION_STORAGE_PATH')) {
    $upload_dir = wp_upload_dir();
    define('CONVO_GPT_MCP_SESSION_STORAGE_PATH', trailingslashit($upload_dir['basedir']) . 'convo-gpt-mcp/');
}

// Session timeout in seconds (how long an inactive session stays alive)
if (!defined('CONVO_GPT_MCP_SESSION_TIMEOUT')) {
    define('CONVO_GPT_MCP_SESSION_TIMEOUT', 60 * 60 * 24 * 30);
}

// Background poll interval (microseconds) for checking new messages
if (!defined('CONVO_GPT_MCP_LISTEN_USLEEP')) {
    define('CONVO_GPT_MCP_LISTEN_USLEEP', 300000);
}

// Ping interval in seconds for background processes (0 to disable)
if (!defined('CONVO_GPT_MCP_PING_INTERVAL')) {
    define('CONVO_GPT_MCP_PING_INTERVAL', 10);
}


require_once __DIR__ . '/vendor/autoload.php';

function run_convoworks_gpt_plugin()
{
    $plugin = new \Convo\Gpt\GptPlugin();
    $plugin->register();
}
run_convoworks_gpt_plugin();

function convoworks_gpt_check_for_updates($update, $plugin_data, $plugin_file)
{
    static $response = false;

    if (empty($plugin_data['UpdateURI']) || !empty($update)) {
        return $update;
    }

    if ($response === false) {
        $response = wp_remote_get($plugin_data['UpdateURI']);
    }

    if (is_a($response, 'WP_Error')) {
        /** @var WP_Error $response */
        error_log('Error updating plugin [Convoworks GPT]: ' . implode("\n", $response->get_error_messages()));
        return $update;
    }

    if (empty($response['body'])) {
        return $update;
    }

    $custom_plugins_data = json_decode($response['body'], true);

    if (!empty($custom_plugins_data[$plugin_file])) {
        $custom_data = $custom_plugins_data[$plugin_file];
        $custom_data['slug'] = $plugin_file; // Add slug property here
        return (object) $custom_data;
    } else {
        return $update;
    }
}
add_filter('update_plugins_raw.githubusercontent.com', 'convoworks_gpt_check_for_updates', 10, 3);
