<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IMessages;

class SystemMessageGroupElement extends AbstractWorkflowContainerComponent implements IConversationElement, IMessages
{

    private $_content;
    private $_trimChildren;

    /**
     * @var IConversationElement[]
     */
    private $_messagesDefinition = [];

    private $_messages = [];

    public function __construct( $properties)
    {
        parent::__construct( $properties);

        $this->_content            =   $properties['content'];
        $this->_trimChildren       =   $properties['trim_children'];
        foreach ( $properties['message_provider'] as $element) {
            $this->_messagesDefinition[] = $element;
            $this->addChild($element);
        }
    }

    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        // collect inner messages first
        $this->_messages = [];

        foreach ( $this->_messagesDefinition as $elem)   {
            $elem->read( $request, $response);
        }

        /** @var \Convo\Gpt\IMessages $container */
        $container = $this->findAncestor( '\Convo\Gpt\IMessages');

        $container->registerMessage( $this->_getCompleteMessage());
    }

        /**
     * @param array $message
     */
    public function registerMessage( $message) {
        $this->_messages[] = $message;
    }

    /**
     * Returns all messages
     * @return array
     */
    public function getMessages() {
        return [$this->_getCompleteMessage()];
    }

    private function _getCompleteMessage() {
        return [
            'role' => 'system',
            'transient' => true,
            'content' => $this->_getCompleteContent()
        ];
    }


    private function _getCompleteContent() {
        $content = $this->evaluateString( $this->_content);
        $trim = $this->evaluateString( $this->_trimChildren);
        if ( $trim) {
            foreach ( $this->_messages as $message) {
                $content .= trim( $message['content']);
            }
        } else {
            foreach ( $this->_messages as $message) {
                $content .= "\n".$message['content'];
            }
        }

        return $content;
    }


    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_content.']['.count( $this->_messagesDefinition).']';
    }
}
