<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;

class SystemMessageElement extends AbstractWorkflowContainerComponent implements IConversationElement
{

    private $_content;
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_content            =   $properties['content'];
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        /** @var \Convo\Gpt\Pckg\ChatCompletionV2Element $container */
        $container = $this->findAncestor( '\Convo\Gpt\Pckg\ChatCompletionV2Element');
        $container->registerMessage( [
            'role' => 'system',
            'transient' => true,
            'content' => $this->evaluateString( $this->_content)
        ]);
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_content.']';
    }
}
