<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Gpt\ValidationException;

/**
 * @author Tole
 * @deprecated
 */
class ValidationErrorElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    private $_message;
    
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_message = $properties['message'];
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        throw new ValidationException( $this->evaluateString( $this->_message));
    }
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_message.']';
    }

}
