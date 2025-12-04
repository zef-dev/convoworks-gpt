<?php
/**
 * PHPStan bootstrap file
 * Defines constants and stubs for static analysis
 */

// Plugin constants (from convoworks-gpt.php)
if (!defined('CONVO_GPT_VERSION')) {
    define('CONVO_GPT_VERSION', '0.16.0');
}
if (!defined('CONVO_GPT_DIR')) {
    define('CONVO_GPT_DIR', __DIR__);
}
if (!defined('CONVO_GPT_URL')) {
    define('CONVO_GPT_URL', 'https://example.com/wp-content/plugins/convoworks-gpt/');
}
if (!defined('CONVO_GPT_PATH')) {
    define('CONVO_GPT_PATH', __FILE__);
}

// Optional MCP configuration constants (with defaults)
if (!defined('CONVO_GPT_MCP_SESSION_STORAGE_PATH')) {
    define('CONVO_GPT_MCP_SESSION_STORAGE_PATH', '/tmp/mcp-sessions/');
}
if (!defined('CONVO_GPT_MCP_SESSION_TIMEOUT')) {
    define('CONVO_GPT_MCP_SESSION_TIMEOUT', 60 * 60 * 24 * 30); // 30 days
}
if (!defined('CONVO_GPT_MCP_LISTEN_USLEEP')) {
    define('CONVO_GPT_MCP_LISTEN_USLEEP', 300000); // 300ms
}
if (!defined('CONVO_GPT_MCP_PING_INTERVAL')) {
    define('CONVO_GPT_MCP_PING_INTERVAL', 10); // 10 seconds
}

