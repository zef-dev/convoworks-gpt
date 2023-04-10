<?php declare(strict_types=1);

namespace Convo\Gpt\Pckg;

/**
 * @author Tole
 * @deprecated
 */
class ActionsPromptElement extends SimplePromptElement
{
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
    }
    
//     public function getPromptContent()
//     {
//         $prompt =   parent::getPromptContent();
        
//         $app    =   $this->findAncestor( '\Convo\Gpt\IChatApp');
//         /* @var \Convo\Gpt\IChatApp $app */
        
//         foreach ( $app->getActions() as $action) {
//             $prompt .= "\n\n\n";
//             $prompt .= $action->getPromptContent();
//         }
        
//         return $prompt;
//     }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }






}
