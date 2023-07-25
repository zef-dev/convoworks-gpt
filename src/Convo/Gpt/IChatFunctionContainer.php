<?php declare(strict_types=1);

namespace Convo\Gpt;


interface IChatFunctionContainer
{
    
    /**
     * @param IChatFunction $function
     */
    public function registerFunction( $function);
    
    /**
     * @return IChatFunction[]
     */
    public function getFunctions();

    
}
