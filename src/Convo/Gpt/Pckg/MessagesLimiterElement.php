<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Gpt\GptApiFactory;
use Convo\Gpt\IMessages;

class MessagesLimiterElement extends AbstractWorkflowContainerComponent implements IConversationElement, IMessages
{

    /**
     * @var GptApiFactory
     */
    private $_gptApiFactory;



    private $_messages = [];

    private $_includeFunctions;


    /**
     * @var IConversationElement[]
     */
    private $_messagesDefinition = [];

    public function __construct( $properties, $gptApiFactory)
    {
        parent::__construct( $properties);

        $this->_gptApiFactory  =	$gptApiFactory;

        if ( isset( $properties['message_provider'])) {
            foreach ( $properties['message_provider'] as $element) {
                $this->_messagesDefinition[] = $element;
                $this->addChild($element);
            }
        }

        $this->_includeFunctions = $properties['include_functions'] ?? '';
    }

    public function registerMessage( $message)
    {
        $this->_messages[] = $message;
    }

    public function getMessages()
    {
        return $this->_messages;
    }

    public function read( IConvoRequest $request, IConvoResponse $response)
    {
        $this->_messages = [];
        foreach ( $this->_messagesDefinition as $elem)   {
            $elem->read( $request, $response);
        }

        // TRUNCATE
        $truncated = $this->_truncate(
            $this->getMessages(),
            $this->evaluateString( $this->_properties['max_count']),
            $this->evaluateString( $this->_properties['truncate_to']));

        $this->_logger->debug( 'Got messages after truncation ['.print_r( $truncated, true).']');

        /** @var \Convo\Gpt\IMessages $container */
        $container = $this->findAncestor( '\Convo\Gpt\IMessages');

        foreach ( $truncated as $message) {
            $container->registerMessage( $message);
        }
    }

    private function _truncate( $messages, $max, $to)
    {
        $this->_logger->debug( 'Truncating messages ['.count( $messages).'] to ['.$to.'] max ['.$max.']');
        $count = count( $messages);
        if ( $count < $max) {
            return $messages;
        }

        $include = boolval( $this->evaluateString( $this->_includeFunctions));

        // TRUNCATE
        $new_messages = [];
        $truncated = [];
        $count_to_trim = $count - $to;

        $this->_logger->debug( 'Count to trim ['.$count_to_trim.']');

        for ( $i=0; $i< $count; $i++)
        {
            $message = $messages[$i];

            if ( $i < $count_to_trim)
            {
                if ( $include) {
                    if ( isset( $message['function_call'])) {
                        continue;
                    }
                } else {
                    if ( $message['role'] === 'function' || isset( $message['function_call'])) {
                        continue;
                    }
                }

                $truncated[] = $message;
            }
            else
            {
                $new_messages[] = $message;
            }
        }

        $new_messages = array_merge(
            $this->_removeEndingUserMessages( $truncated),
            $new_messages
        );

        $this->_logger->debug( 'Truncated messages ['.print_r( $truncated, true).']');

        // SUMMARIZE
        $summarized = $this->_sumarize( $truncated);
        $new_messages = array_merge( [['role'=>'system', 'content' => $summarized]], $new_messages);

        return $new_messages;
    }

    private function _removeEndingUserMessages( &$truncated)
    {
        if ( count( $truncated))
        {
            if ( $truncated[count( $truncated) - 1]['role'] === 'user')
            {
                return array_merge(
                    [array_pop( $truncated)],
                    $this->_removeEndingUserMessages( $truncated)
                );
            }
        }
        return [];
    }

    private function _sumarize( $conversation)
    {
        $messages   = [[
            'role' => 'system',
            'content' => $this->evaluateString( $this->_properties['system_message'])
        ]];

        $temp = [];
        foreach ( $conversation as $message) {
            if ( isset( $message['name'])) {
                $temp[] = ucfirst($message['role']).' - '.$message['name'].'(): '.$message['content'];
            } else {
                $temp[] = ucfirst($message['role']).': '.$message['content'];
            }

        }

        $messages[] =   [
            'role' => 'user',
            'content' => implode( "\n\n", $temp)
        ];

        $api_key    =   $this->evaluateString( $this->_properties['api_key']);
        $api        =   $this->_gptApiFactory->getApi( $api_key);

        $http_response   =   $api->chatCompletion( $this->_buildApiOptions( $messages));

        return $http_response['choices'][0]['message']['content'];
    }

    private function _buildApiOptions( $messages)
    {
        $options = $this->getService()->evaluateArgs( $this->_properties['apiOptions'], $this);

        $options['messages'] = $messages;

        return $options;
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }
}
