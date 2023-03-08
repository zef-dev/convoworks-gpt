<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\GptApi;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Gpt\GptApiFactory;

class CompletionElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    
    
    /**
     * @var string
     */
    private $_apiKey;
    
    /**
     * @var string
     */
    private $_prompt;
    
    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    public function __construct( $properties, $gptApiFactory)
    {
        parent::__construct( $properties);
        
        $this->_gptApiFactory  =	$gptApiFactory;
        $this->_prompt         =	$properties['prompt'];
        $this->_apiKey         =	$properties['api_key'];
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $prompt     =   $this->evaluateString( $this->_prompt);
        $api_key    =   $this->evaluateString( $this->_apiKey);
        
        $api        =   $this->_gptApiFactory->getApi( $api_key);
        $response   =   $api->completion( $prompt);
        
        $params        =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( 'response', $response);
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
