<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\IChatFunction;

class ChatFunctionElement extends AbstractWorkflowContainerComponent implements IChatFunction, IConversationElement
{

    private $_name;
    private $_description;
    private $_parameters;
    private $_required;
    
    private $_requestData;
    private $_resultData;
    
    /**
     * @var IConversationElement[]
     */
    private $_ok = [];
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_name            =   $properties['name'];
        $this->_description     =   $properties['description'];
        $this->_parameters      =   $properties['parameters'];
        $this->_required        =   $properties['required'];
        $this->_requestData     =   $properties['request_data'];
        $this->_resultData      =   $properties['result_data'];
        
        foreach ( $properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
    }
    
    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        /** @var \Convo\Gpt\IChatFunctionContainer $container */
        $container = $this->findAncestor( '\Convo\Gpt\IChatFunctionContainer');
        $container->registerFunction( $this);
    }

    
    public function execute( IConvoRequest $request, IConvoResponse $response, $data)
    {
        $this->_logger->debug( 'Got data ['.print_r( $data, true).']');
        $data = json_decode( $data);
        $this->_logger->debug( 'Got data 2 ['.print_r( $data, true).']');
        $params        =    $this->getService()->getComponentParams( IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $data_var      =    $this->evaluateString( $this->_requestData);
        $params->setServiceParam( $data_var, $data);
        
        $this->_logger->debug( 'Executing function ['.$this->getName().']. Arguments available as ['.$data_var.']');
        
        foreach ( $this->_ok as $elem) {
            $elem->read( $request, $response);
        }
        
        $params        =    $this->getService()->getServiceParams( IServiceParamsScope::SCOPE_TYPE_REQUEST);
        return $this->evaluateString( $this->_resultData);
    }
    
    public function accepts( $functionName)
    {
        return $this->getName() === $functionName;
    }
    
    public function getName()
    {
        return $this->evaluateString( $this->_name);
    }
    
    public function getDefinition()
    {
        $parameters = $this->getService()->evaluateArgs( $this->_parameters, $this);
        if ( empty( $parameters)) {
            $parameters = new \stdClass();
        }
        return [
            'name' => $this->getName(),
            'description' => $this->evaluateString( $this->_description),
            'parameters' => [
                'type' => 'object',
                'properties' => $parameters,
                'required' => $this->evaluateString( $this->_required),
            ],
        ];
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_name.']';
    }

}
