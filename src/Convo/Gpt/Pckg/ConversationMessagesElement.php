<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;

class ConversationMessagesElement extends AbstractWorkflowContainerComponent implements IConversationElement
{

    private $_messages;
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_messages      =   $properties['messages'];
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        /** @var \Convo\Gpt\Pckg\ChatCompletionV2Element $container */
        $container = $this->findAncestor( '\Convo\Gpt\Pckg\ChatCompletionV2Element');
        
        $messages = $this->evaluateString( $this->_messages);
        
        foreach ( $messages as $message) {
            $container->registerMessage( $message);
        }
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_messages.']';
    }
}
