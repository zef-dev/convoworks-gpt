<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Gpt\GptApiFactory;

class CompletionElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    
    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    public function __construct( $properties, $gptApiFactory)
    {
        parent::__construct( $properties);
        
        $this->_gptApiFactory  =	$gptApiFactory;
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $api_key    =   $this->evaluateString( $this->_properties['api_key']);
        
        $api        =   $this->_gptApiFactory->getApi( $api_key);
        $response   =   $api->completion( [
            'model' => $this->evaluateString( $this->_properties['model']),
            'prompt' => $this->evaluateString( $this->_properties['prompt']),
            'temperature' => $this->evaluateString( $this->_properties['temperature']),
            'max_tokens' => $this->evaluateString( $this->_properties['max_tokens']),
            'top_p' => $this->evaluateString( $this->_properties['top_p']),
            'frequency_penalty' => $this->evaluateString( $this->_properties['frequency_penalty']),
            'presence_penalty' => $this->evaluateString( $this->_properties['presence_penalty']),
        ]);
        
        $params        =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_properties['result_var']), $response);
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
