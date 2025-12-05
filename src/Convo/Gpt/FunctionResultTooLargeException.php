<?php declare(strict_types=1);

namespace Convo\Gpt;

class FunctionResultTooLargeException extends \RuntimeException
{
    private $_structure;
    public function __construct(string $functionName, int $resultSize, int $maxResultTokens, array $structure = null)
    {
        parent::__construct('Function [' . $functionName . '] returned too large result [' . $resultSize . ']. If possible, adjust the function arguments to return less data. Maximum allowed is [' . $maxResultTokens . '] tokens.');
        $this->_structure = $structure;
    }

    public function getStructure() : array
    {
        return $this->_structure;
    }
}
