<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Gpt\GptApiFactory;

class ModerationApiElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    
    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;

    /**
     * @var IConversationElement[]
     */
    private $_ok = [];
    
    public function __construct( $properties, $gptApiFactory)
    {
        parent::__construct( $properties);
        
        $this->_gptApiFactory  =	$gptApiFactory;
        
        foreach ( $properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $input          =   $this->evaluateString( $this->_properties['input']);
        $api_key        =   $this->evaluateString( $this->_properties['api_key']);
        
        $api            =   $this->_gptApiFactory->getApi( $api_key);
        $http_response  =   $api->moderations( $this->_getApiOptions( $input));
        
        $params         =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_properties['result_var']), $http_response);
        
        foreach ( $this->_ok as $elem)   {
            $elem->read( $request, $response);
        }
    }
    
    private function _getApiOptions( $input)
    {
        $options = $this->getService()->evaluateArgs( $this->_properties['apiOptions'], $this);
        $options['input'] = $input;
        return $options;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }


}
