<?php

declare(strict_types=1);

namespace Convo\Gpt\Admin;

use Psr\Log\LoggerInterface;

class SettingsView
{
    const ID = 'convo-gpt.mcp-server.settings';


    /**
     * @var LoggerInterface
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
        $this->_logger       =   $logger;
        $this->_viewModel    =   $viewModel;
        $this->_mcpManager   =   $mcpManager;
    }

    public function register()
    {
        $this->_logger->debug('Registering CONVO_MCP view');

        add_submenu_page(
            null,
            'Convoworks Mcp Server',
            'Convoworks Mcp Server',
            'manage_convoworks',
            self::ID,
            array($this, 'displayView')
        );

        //         add_filter( 'submenu_file', function($submenu_file){
        //             $screen = get_current_screen();
        //             if($screen->id === self::ID){
        //                 $submenu_file = 'convo-plugin';
        //             }
        //             return $submenu_file;
        //         });
    }

    public function displayView()
    {
        $action = SettingsProcessor::ACTION_ENABLE;

        $this->_includeStyle();

        echo '<div id="pagewrap" class="wrap" style="margin-right:10px !important;">';

        if (!$this->_viewModel->hasServiceSelection()) {
            echo '<h1>';
            echo get_admin_page_title() . 'Mcp Server - no Convoworks service selected';
            echo '</h1>';
            echo '</div>';
            return;
        }

        $submit = true;
        if ($this->_mcpManager->isServiceEnabled($this->_viewModel->getSelectedServiceId())) {
            $action = SettingsProcessor::ACTION_UPDATE;
            $btn_title = 'Save Changes';
            $title = 'Connected to Mcp Server';
            $desc = 'There is nothing to update right now.';
            $submit = false;
        } else {
            $action = SettingsProcessor::ACTION_ENABLE;
            $btn_title = 'Enable';
            $title = 'Your Conovowrks service is not connected to the Mcp Server yet';
            $desc = 'Click enable to setup Mcp Server';
        }

        echo '<h1>';
        echo get_admin_page_title() . 'Mcp Server - ' . $this->_mcpManager->getServiceName($this->_viewModel->getSelectedServiceId());
        echo '</h1>';

        $this->_displayNotices($this->_viewModel->getNotices());

        echo '<br>';

?>

        <form method="post" id="convo_mcp_settings_form" action="<?php echo $this->_viewModel->getActionUrl($action, ['service_id' => $this->_viewModel->getSelectedServiceId()]) ?>">
            <?php echo wp_nonce_field() ?>
            <?php echo wp_referer_field() ?>

            <div id="wrapbox" class="postbox" style="line-height:2em;">
                <div class="inner" style="padding-left:20px;">
                    <div id="general-tab" class="settings-tab active">
                        <table cellspacing="0" cellpadding="0">
                            <tbody>
                                <tr class="setting-section">
                                    <td colspan="5">
                                        <h3><?php echo $title ?></h3>
                                    </td>
                                </tr>
                                <tr class="setting-section">
                                    <td colspan="5"><?php echo $desc ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="submit">
                            <?php if ($submit) : ?>
                                <input id="convo_mcp_submit" type="submit" value="<?php echo $btn_title ?>" class="button-primary" name="Submit">
                            <?php endif; ?>
                            <a href="<?php echo $this->_viewModel->getBackToConvoworksUrl(); ?>" class="button-secondary">Back</a>
                        </p>
                    </div>
                </div>
            </div>
        </form>



    <?php

        echo '</div>';
    }


    private function _getSelectOption($value, $text, $selected)
    {
        $str_selected = $value === $selected ? ' selected' : '';
        return '<option value="' . $value . '"' . $str_selected . '>' . $text . '</option>';
    }

    private function _displayNotices($form_errors)
    {
        if (!empty($form_errors)) {
            $this->_logger->debug('Got form erros [' . print_r($form_errors, true) . ']');
            echo '<div>';
            foreach ($form_errors as $error) {
                echo '<div class="notice notice-' . $error['type'] . ' is-dismissible">
                <p>
                <strong>' . $error['message'] . '</strong>
                </p>
                </div>';
            }
            echo '</div>';
        }

        //         if ( !$this->_alexaManager->isSkillCreated())
        //         {
        //              if ( !$this->_viewModel->canSave()) {
        //                  echo '<div class="notice notice-warning is-dismissible">
        //       <p>Please connect your Convoworks WP installation with your Amazon developer account first. <a href="'.admin_url('admin.php?page=convo-settings').'">Convoworks WP Settings</a></p>
        //       </div>';
        //              } else {
        //                  echo '<div class="notice notice-warning is-dismissible">
        //       <p>Your Alexa skill is not enabled yet. Click save to create it.</p>
        //       </div>';
        //              }
        //         }
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
