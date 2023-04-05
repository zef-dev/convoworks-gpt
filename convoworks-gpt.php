<?php

/**
 * Convoworks GPT
 *
 * Plugin Name: Convoworks GPT
 * Description: GPT related workflow components
 * UID: convoworks-gpt
 * Plugin URI: https://github.com/zef-dev/convoworks-gpt
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

