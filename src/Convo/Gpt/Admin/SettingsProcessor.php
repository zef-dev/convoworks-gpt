<?php

declare(strict_types=1);

namespace Convo\Gpt\Admin;


class SettingsProcessor
{
    const ACTION_ENABLE   =   'convo_mcp_enable';
    const ACTION_UPDATE   =   'convo_mcp_update';


    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var SettingsViewModel
     */
    private $_viewModel;


    /**
     * @var McpConvoworksManager
     */
    private $_mcpManager;


    public function __construct($logger, $viewModel, $mcpManager)
    {
        $this->_logger          =   $logger;
        $this->_viewModel       =   $viewModel;
        $this->_mcpManager   =   $mcpManager;
    }

    public function accepts()
    {
        $this->_logger->debug('Checkng action [' . $this->getAction() . ']');
        if ($this->getAction() === self::ACTION_ENABLE) {
            return true;
        }
        if ($this->getAction() === self::ACTION_UPDATE) {
            return true;
        }
    }

    public function getAction()
    {
        if (isset($_REQUEST['action'])) {
            return $_REQUEST['action'];
        }
    }

    public function run()
    {
        $this->_logger->debug('Submitted page [' . print_r($_POST, true) . ']');

        $this->_logger->info('Processing action [' . $this->getAction() . '] with service [' . $this->_viewModel->getSelectedServiceId() . '] ..');
        try {
            if ($this->getAction() === self::ACTION_ENABLE) {
                $this->_processCreate($this->_viewModel->getSelectedServiceId());
                add_settings_error('convo_mcp_settings', 'convo_mcp_settings', 'Mcp service configuration been successfully created!', 'success');
            } else if ($this->getAction() === self::ACTION_UPDATE) {
                $this->_processUpdate($this->_viewModel->getSelectedServiceId());
                add_settings_error('convo_mcp_settings', 'convo_mcp_settings', 'Mcp service configuration been successfully updated!', 'success');
            } else {
                throw new \Exception('Unexpected action [' . $this->getAction() . ']');
            }
        } catch (\Exception $e) {
            $this->_logger->error($e);
            add_settings_error('convo_mcp_settings', 'convo_mcp_settings', $e->getMessage(), 'error');
            //                 add_settings_error( 'convo_mcp_settings', 'convo_mcp_settings', 'Unexpected error while processing your request', 'error');

        }

        set_transient('convo_mcp_settings_errors', get_settings_errors(), 30);

        $base_url = $this->_viewModel->getBaseUrl($this->_viewModel->getSelectedServiceId());
        wp_safe_redirect($base_url);
        $this->_logger->info('Redirecting to [' . $base_url . ']');
        exit;
    }

    private function _processCreate($serviceId)
    {
        $this->_logger->info('Creatng mcp service for [' . $serviceId . ']');
        $this->_mcpManager->enableMcp($serviceId);
    }

    private function _processUpdate($serviceId)
    {
        // update amazon config
        $this->_logger->info('Updating mcp service for [' . $serviceId . ']');
        $this->_mcpManager->updateMcp($serviceId);
    }
}
