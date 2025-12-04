<?php

declare(strict_types=1);

namespace Convo\Gpt\Admin;


class SettingsProcessor
{
    const ACTION_ENABLE    = 'convo_mcp_enable';
    const ACTION_UPDATE    = 'convo_mcp_update';
    const ACTION_DISABLE   = 'convo_mcp_disable';
    const NONCE_ACTION     = 'convo_mcp_settings_action';
    const NONCE_NAME       = '_wpnonce';

    /** @var \Psr\Log\LoggerInterface */
    private $_logger;

    /** @var SettingsViewModel */
    private $_viewModel;

    /** @var McpConvoworksManager */
    private $_mcpManager;



    public function __construct($logger, $viewModel, $mcpManager)
    {
        $this->_logger          =   $logger;
        $this->_viewModel       =   $viewModel;
        $this->_mcpManager      =   $mcpManager;
    }

    public function accepts()
    {
        $action = $this->getAction();
        return in_array($action, [
            self::ACTION_ENABLE,
            self::ACTION_UPDATE,    // if you ever want to support updates
            self::ACTION_DISABLE,   // ← now accepted
        ], true);
    }

    public function getAction()
    {
        if (isset($_REQUEST['action'])) {
            return sanitize_text_field(wp_unslash($_REQUEST['action']));
        }
        return '';
    }

    public function run()
    {
        // Verify user has permission
        if (!current_user_can('manage_convoworks')) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', 'convoworks-gpt'),
                esc_html__('Permission Denied', 'convoworks-gpt'),
                ['response' => 403]
            );
        }

        // Verify nonce
        if (!check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME)) {
            wp_die(
                esc_html__('Security check failed. Please try again.', 'convoworks-gpt'),
                esc_html__('Security Error', 'convoworks-gpt'),
                ['response' => 403]
            );
        }

        $svc = $this->_viewModel->getSelectedServiceId();
        $this->_logger->info("Processing action [{$this->getAction()}] for service [{$svc}]");

        try {
            switch ($this->getAction()) {
                case self::ACTION_ENABLE:
                    $this->_processCreate($svc);
                    $msg = __('MCP service configuration has been successfully created!', 'convoworks-gpt');
                    $type = 'success';
                    break;
                case self::ACTION_DISABLE:
                    $this->_processDisable($svc);
                    $msg = __('MCP service configuration has been successfully disabled!', 'convoworks-gpt');
                    $type = 'success';
                    break;
                case self::ACTION_UPDATE:
                    $this->_processUpdate($svc);
                    $msg = __('MCP service configuration has been successfully updated!', 'convoworks-gpt');
                    $type = 'success';
                    break;
                default:
                    throw new \Exception('Unexpected action [' . $this->getAction() . ']');
            }
            add_settings_error('convo_mcp_settings', 'convo_mcp_settings', $msg, $type);
        } catch (\Exception $e) {
            /** @phpstan-ignore-next-line */
            $this->_logger->error($e);
            add_settings_error('convo_mcp_settings', 'convo_mcp_settings', $e->getMessage(), 'error');
        }

        set_transient('convo_mcp_settings_errors', get_settings_errors('convo_mcp_settings'), 30);
        wp_safe_redirect($this->_viewModel->getBaseUrl($svc));
        exit;
    }

    private function _processCreate($serviceId)
    {
        $this->_logger->info('Creating mcp service for [' . $serviceId . ']');
        $basicAuth = isset($_POST['basic_auth']) && sanitize_text_field($_POST['basic_auth']) === '1';
        $this->_mcpManager->enableMcp($serviceId, $basicAuth);
    }

    private function _processDisable($serviceId)
    {
        // update amazon config
        $this->_logger->info('Disabling mcp service for [' . $serviceId . ']');
        $this->_mcpManager->disableMcp($serviceId);
    }

    private function _processUpdate($serviceId)
    {
        $this->_logger->info('Updating mcp service for [' . $serviceId . ']');
        $basicAuth = isset($_POST['basic_auth']) && sanitize_text_field($_POST['basic_auth']) === '1';
        $this->_mcpManager->updateMcp($serviceId, $basicAuth);
    }
}
