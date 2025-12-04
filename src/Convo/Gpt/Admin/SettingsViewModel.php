<?php

declare(strict_types=1);

namespace Convo\Gpt\Admin;


use Convo\Core\DataItemNotFoundException;
use Convo\Core\IServiceDataProvider;
use Psr\Log\LoggerInterface;

class SettingsViewModel
{
    const NONCE_ACTION = 'convo_mcp_settings_action';
    const NONCE_NAME = '_wpnonce';

    private $_basicAuth = false;


    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var IServiceDataProvider
     */
    private $_convoServiceDataProvider;
    public function __construct($logger, $convoServiceDataProvider)
    {
        $this->_logger          =   $logger;
        $this->_convoServiceDataProvider      =   $convoServiceDataProvider;
    }

    public function init()
    {
        $page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';
        if ($page !== SettingsView::ID) {
            // $this->_logger->debug('Not convoworks mcp server call. Exiting ...');
            return;
        }

        $this->_logger->info('Initializing convoworks mcp settings view model');

        // Load basicAuth from config
        try {
            $svcId = $this->getSelectedServiceId();
            $user = new \Convo\Wp\AdminUser(wp_get_current_user());
            $config = $this->_convoServiceDataProvider->getServicePlatformConfig(
                $user,
                $svcId,
                \Convo\Core\Publish\IPlatformPublisher::MAPPING_TYPE_DEVELOP
            );
            $platformId = \Convo\Gpt\Mcp\McpServerPlatform::PLATFORM_ID;
            if (isset($config[$platformId]['basic_auth'])) {
                $this->_basicAuth = $config[$platformId]['basic_auth'] ? true : false;
            }
        } catch (\Throwable $e) {
            /** @phpstan-ignore-next-line */
            $this->_logger->error($e);
        }
    }

    public function getSelectedServiceId()
    {
        if (isset($_REQUEST['service_id'])) {
            return sanitize_text_field(wp_unslash($_REQUEST['service_id']));
        }
        throw new DataItemNotFoundException('No selected service found');
    }

    public function hasServiceSelection()
    {
        try {
            $this->getSelectedServiceId();
            return true;
        } catch (DataItemNotFoundException $e) {
        }
        return false;
    }

    public function getPageId()
    {
        return SettingsView::ID;
    }

    public function getBaseUrl($serviceId = null)
    {
        $args = [
            'page' => $this->getPageId(),
        ];
        if ($serviceId) {
            $args['service_id'] = $serviceId;
        }
        $url = add_query_arg($args, admin_url('admin.php'));
        return $url;
    }

    public function getActionUrl($action, $params = [])
    {
        $url = add_query_arg(array_merge([
            'page' => $this->getPageId(),
            'action' => $action,
        ], $params), admin_url('admin-post.php'));
        $url = wp_nonce_url($url, self::NONCE_ACTION, self::NONCE_NAME);
        return $url;
    }

    public function getBackToConvoworksUrl()
    {
        $url = add_query_arg([
            'page' => 'convo-plugin',
        ], admin_url('admin.php'));
        $url .= '#!/convoworks-editor/' . $this->getSelectedServiceId() . '/configuration/platforms';
        return $url;
    }

    public function getFormUrl($params = [])
    {
        $url = add_query_arg(array_merge([
            'page' => $this->getPageId(),
        ], $params), admin_url('admin-post.php'));
        return $url;
    }

    public function getBasicAuth()
    {
        return $this->_basicAuth;
    }

    public function setBasicAuth($value)
    {
        $this->_basicAuth = $value ? true : false;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
