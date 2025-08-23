<?php

declare(strict_types=1);

namespace Convo\Gpt\Admin;

use Psr\Log\LoggerInterface;

class SettingsView
{
    const ID = 'convo-gpt.mcp-server.settings';

    /** @var LoggerInterface */     private $_logger;
    /** @var SettingsViewModel */   private $_viewModel;
    /** @var McpConvoworksManager */ private $_mcpManager;

    public function __construct($logger, $viewModel, $mcpManager)
    {
        $this->_logger     = $logger;
        $this->_viewModel  = $viewModel;
        $this->_mcpManager = $mcpManager;
    }

    public function register()
    {
        $this->_logger->debug('Registering CONVO_MCP view');
        add_submenu_page(
            null,
            'Convoworks MCP Server',
            'Convoworks MCP Server',
            'manage_convoworks',
            self::ID,
            [$this, 'displayView']
        );
    }

    public function displayView()
    {
        $svcId = $this->_viewModel->getSelectedServiceId();
        $enabled = $this->_mcpManager->isServiceEnabled($svcId);

        // include styles & header
        $this->_includeStyle();
        echo '<div id="pagewrap" class="wrap" style="margin-right:10px !important;">';

        if (!$this->_viewModel->hasServiceSelection()) {
            echo '<h1>' . get_admin_page_title() . ' – no Convoworks service selected</h1>';
            echo '</div>';
            return;
        }

        // choose action and form state based on enabled state
        if ($enabled) {
            $action   = SettingsProcessor::ACTION_UPDATE;
            $btnTitle = 'Update';
            $title    = 'Connected to MCP Server platform';
            $desc     = 'Update MCP Server platform options below, or disable the platform.';
            $showDisable = true;
        } else {
            $action   = SettingsProcessor::ACTION_ENABLE;
            $btnTitle = 'Enable';
            $title    = 'Your Convoworks service is not connected to the MCP Server yet';
            $desc     = 'Set up the MCP Server integration below.';
            $showDisable = false;
        }

        echo '<h1>' . get_admin_page_title() . ' – '
            . $this->_mcpManager->getServiceName($svcId)
            . '</h1>';

        $this->_displayNotices($this->_viewModel->getNotices());
        echo '<br>';

        // render form
        $actionUrl = $this->_viewModel->getActionUrl($action, ['service_id' => $svcId]);
?>
        <form method="post" id="convo_mcp_settings_form" action="<?php echo esc_url($actionUrl) ?>">
            <?php wp_nonce_field();
            wp_referer_field(); ?>
            <div id="wrapbox" class="postbox" style="line-height:2em;">
                <div class="inner" style="padding-left:20px;">
                    <h3><?php echo esc_html($title) ?></h3>
                    <p><?php echo esc_html($desc) ?></p>
                    <table class="form-table">
                        <tr>
                            <th class="settings-label" scope="row">
                                <label for="basic_auth">Require Basic Auth</label>
                            </th>
                            <td class="settings-input">
                                <input
                                    type="checkbox"
                                    id="basic_auth"
                                    name="basic_auth"
                                    value="1"
                                    <?php if ($this->_viewModel->getBasicAuth()) echo 'checked'; ?> />
                                <span class="settings-helper">If checked, the MCP endpoint will require HTTP Basic Authentication.</span>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input
                            id="convo_mcp_submit"
                            type="submit"
                            value="<?php echo esc_attr($btnTitle) ?>"
                            class="button-primary"
                            name="Submit" />
                        <?php if ($showDisable): ?>
                            <button
                                type="submit"
                                name="action"
                                value="<?php echo esc_attr(SettingsProcessor::ACTION_DISABLE); ?>"
                                class="button-primary"
                                style="background-color:#dc3232;border-color:#dc3232;margin-left:10px;"
                                onclick="return confirm('Are you sure you want to disable the MCP Server?');">Disable</button>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($this->_viewModel->getBackToConvoworksUrl()); ?>" class="button-secondary">
                            Back
                        </a>
                    </p>
                </div>
            </div>
        </form>
    <?php
        echo '</div>';
    }

    private function _displayNotices($form_errors)
    {
        if (empty($form_errors)) {
            return;
        }
        $this->_logger->debug('Form errors: ' . print_r($form_errors, true));
        foreach ($form_errors as $error) {
            printf(
                '<div class="notice notice-%1$s is-dismissible"><p><strong>%2$s</strong></p></div>',
                esc_attr($error['type']),
                esc_html($error['message'])
            );
        }
    }

    private function _includeStyle()
    {
    ?>
        <style>
            .settings-label {
                vertical-align: top;
                font-weight: bold;
                min-width: 100px;
                max-width: 200px;
            }

            .settings-input input.setting-text {
                width: 100%;
            }

            #wrapbox {
                margin-right: 25px;
            }

            .settings-spacer {
                height: 7px;
            }

            .form-invalid.form-required textarea {
                border-color: #d63638 !important;
                box-shadow: 0 0 2px rgba(214, 54, 56, .8);
            }

            .settings-row .settings-input textarea,
            .settings-row .settings-input select {
                width: 100%;
                max-width: 100%;
            }

            .settings-helper {
                vertical-align: top;
                font-style: italic;
                min-width: 200px;
                max-width: 500px;
                line-height: 16px;
            }

            .settings-input {
                vertical-align: top;
                min-width: 100px;
                max-width: 300px;
            }
        </style>
<?php
    }
}
