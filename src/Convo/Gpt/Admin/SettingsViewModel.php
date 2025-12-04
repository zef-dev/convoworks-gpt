<?php

declare(strict_types=1);

namespace Convo\Gpt\Admin;


use Convo\Core\DataItemNotFoundException;
use Convo\Core\IServiceDataProvider;
use Psr\Log\LoggerInterface;

class SettingsViewModel
{
    private $_basicAuth = false;


    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @var IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    private $_submited;
    private $_errors    =   [];
    private $_notices   =   [];
    public function __construct($logger, $convoServiceDataProvider)
    {
        $this->_logger          =   $logger;
        $this->_convoServiceDataProvider      =   $convoServiceDataProvider;
    }

    public function init()
    {
        if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== SettingsView::ID) {
            // $this->_logger->debug('Not convoworks mcp server call. Exiting ...');
            return;
        }

        $this->_logger->info('Initializing convoworks mcp settings view model');

        $saved = get_transient("convo_mcp_field_errors");
        if ($saved) {
            $this->_errors = $saved;
            delete_transient("convo_mcp_field_errors");
        }

        if (empty($_POST)) {
            $saved = get_transient("convo_mcp_submitted_data");
            if ($saved) {
                $this->_submited = $saved;
                delete_transient("convo_mcp_submitted_data");
            }
        } else {
            $this->_submited = $_POST;
        }

        $saved = get_transient("convo_mcp_settings_errors");
        if ($saved) {
            $this->_notices = $saved;
            delete_transient("convo_mcp_settings_errors");
        }

        // Load basicAuth from submitted data, or from config if available
        if (!empty($this->_submited) && isset($this->_submited['basic_auth'])) {
            $this->_basicAuth = $this->_submited['basic_auth'] ? true : false;
        } else {
            // Try to load from manager/config
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
    }

    public function getSelectedServiceId()
    {
        if (isset($_REQUEST['service_id'])) {
            return $_REQUEST['service_id'];
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
        $url = wp_nonce_url($url);
        return $url;
    }

    public function getBackToConvoworksUrl()
    {
        $url = add_query_arg([
            'page' => 'convo-plugin',
        ], admin_url('admin.php'));
        $url .= '#!/convoworks-editor/' . $this->getSelectedServiceId() . '/configuration';
        return $url;
    }

    public function getFormUrl($params = [])
    {
        $url = add_query_arg(array_merge([
            'page' => $this->getPageId(),
        ], $params), admin_url('admin-post.php'));
        return $url;
    }

    public function isError($field)
    {
        if (isset($this->_errors[$field])) {
            return true;
        }
    }

    public function getError($field)
    {
        if (isset($this->_errors[$field])) {
            return $this->_errors[$field];
        }
    }

    public function getNotices()
    {
        return $this->_notices;
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
