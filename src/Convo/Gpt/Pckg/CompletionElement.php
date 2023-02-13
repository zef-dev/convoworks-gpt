<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\GptApi;
use Convo\Core\Params\IServiceParamsScope;

class CompletionElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    
    
    /**
     * @var GptApi
     */
    private $_gptApi;

    /**
     * @var IPromptBuilder
     */
    private $_prompt;

    /**
     * @var IResponseParser
     */
    private $_response;
    
    public function __construct( $properties, $gptApi)
    {
        parent::__construct( $properties);
        
        $this->_gptApi         =	$gptApi;
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $prompt     =   $this->_prompt->build();
        
        $response   =   $this->_gptApi->completion( $prompt);
        
        $parsed     =   $this->_response->parse( $response);
        
        $params        =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( 'response', $parsed);
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
