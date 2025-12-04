<?php

declare(strict_types=1);

namespace Convo\Gpt\Admin;

use Psr\Log\LoggerInterface;

class SettingsView
{
    const ID = 'convo-gpt.mcp-server.settings';
    const PAGE_TITLE = 'Convoworks MCP Server';

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
        // Hidden page - null parent means it won't appear in the menu
        /** @phpstan-ignore-next-line */
        add_submenu_page(
            "",
            self::PAGE_TITLE,
            self::PAGE_TITLE,
            'manage_convoworks',
            self::ID,
            [$this, 'displayView']
        );

        // Enqueue styles only on this specific admin page
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
    }

    public function enqueueStyles($hook)
    {
        // For hidden pages (parent=null), the hook is 'admin_page_{page_slug}'
        if ($hook !== 'admin_page_' . self::ID) {
            return;
        }

        wp_enqueue_style(
            'convo-mcp-settings',
            CONVO_GPT_URL . 'assets/mcp-settings.css',
            [],
            CONVO_GPT_VERSION
        );
    }

    public function displayView()
    {
        // Verify user has permission
        if (!current_user_can('manage_convoworks')) {
            wp_die(
                esc_html__('You do not have sufficient permissions to access this page.', 'convoworks-gpt'),
                esc_html__('Permission Denied', 'convoworks-gpt'),
                ['response' => 403]
            );
        }

        $svcId = $this->_viewModel->getSelectedServiceId();
        $enabled = $this->_mcpManager->isServiceEnabled($svcId);

        echo '<div id="pagewrap" class="wrap" style="margin-right:10px !important;">';

        if (!$this->_viewModel->hasServiceSelection()) {
            echo '<h1>' . esc_html(self::PAGE_TITLE) . ' – ' .
                esc_html__('no Convoworks service selected', 'convoworks-gpt') . '</h1>';
            echo '</div>';
            return;
        }

        // choose action and form state based on enabled state
        if ($enabled) {
            $action   = SettingsProcessor::ACTION_UPDATE;
            $btnTitle = __('Update', 'convoworks-gpt');
            $title    = __('Connected to MCP Server platform', 'convoworks-gpt');
            $desc     = __('Update MCP Server platform options below, or disable the platform.', 'convoworks-gpt');
            $showDisable = true;
        } else {
            $action   = SettingsProcessor::ACTION_ENABLE;
            $btnTitle = __('Enable', 'convoworks-gpt');
            $title    = __('Your Convoworks service is not connected to the MCP Server yet', 'convoworks-gpt');
            $desc     = __('Set up the MCP Server integration below.', 'convoworks-gpt');
            $showDisable = false;
        }

        echo '<h1>' . esc_html(self::PAGE_TITLE) . ' – '
            . esc_html($this->_mcpManager->getServiceName($svcId))
            . '</h1>';

        // Display WordPress settings errors/notices
        settings_errors('convo_mcp_settings');
        echo '<br>';

        // render form
        $actionUrl = $this->_viewModel->getActionUrl($action, ['service_id' => $svcId]);
?>
        <form method="post" id="convo_mcp_settings_form" action="<?php echo esc_url($actionUrl) ?>">
            <?php wp_nonce_field(SettingsViewModel::NONCE_ACTION, SettingsViewModel::NONCE_NAME);
            wp_referer_field(); ?>
            <div id="wrapbox" class="postbox" style="line-height:2em;">
                <div class="inner" style="padding-left:20px;">
                    <h3><?php echo esc_html($title) ?></h3>
                    <p><?php echo esc_html($desc) ?></p>
                    <table class="form-table">
                        <tr>
                            <th class="settings-label" scope="row">
                                <label for="basic_auth"><?php esc_html_e('Require Basic Auth', 'convoworks-gpt'); ?></label>
                            </th>
                            <td class="settings-input">
                                <input
                                    type="checkbox"
                                    id="basic_auth"
                                    name="basic_auth"
                                    value="1"
                                    <?php checked($this->_viewModel->getBasicAuth(), true); ?> />
                                <span class="settings-helper"><?php esc_html_e('If checked, the MCP endpoint will require HTTP Basic Authentication.', 'convoworks-gpt'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th class="settings-label" scope="row">
                                <label for="special_role"><?php esc_html_e('Special role', 'convoworks-gpt'); ?></label>
                            </th>
                            <td class="settings-input">
                                <input
                                    type="text"
                                    id="special_role"
                                    name="special_role"
                                    value="mcp-server"
                                    readonly
                                    class="setting-text" />
                                <span class="settings-helper"><?php esc_html_e('The MCP workflow will start from the block with this special role.', 'convoworks-gpt'); ?></span>
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
                                onclick="return confirm('<?php echo esc_js(__('Are you sure you want to disable the MCP Server?', 'convoworks-gpt')); ?>');"><?php esc_html_e('Disable', 'convoworks-gpt'); ?></button>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($this->_viewModel->getBackToConvoworksUrl()); ?>" class="button-secondary">
                            <?php esc_html_e('Back', 'convoworks-gpt'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </form>
    <?php
        echo '</div>';
    }
}
