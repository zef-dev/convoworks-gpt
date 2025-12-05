<?php declare(strict_types=1);

namespace Convo\Gpt;

class FunctionResultTooLargeException extends \RuntimeException
{
    private $_result;
    private $_structure = null;
    private $_responseGenerated = false;

    public function __construct(string $functionName, int $resultSize, int $maxResultTokens, string $result)
    {
        parent::__construct('Function [' . $functionName . '] returned too large result [' . $resultSize . ']. If possible, adjust the function arguments to return less data. Maximum allowed is [' . $maxResultTokens . '] tokens.');
        $this->_result = $result;
    }

    /**
     * Returns structured response including error message and result info:
     * - If JSON: includes 'structure' key with scanned structure
     * - Otherwise: includes 'preview' key with first 250 chars
     *
     * @return array
     */
    public function getResponse()
    {
        if (!$this->_responseGenerated) {
            $this->_generateResponse();
        }

        $response = [
            'error' => $this->getMessage()
        ];

        if ($this->_structure !== null) {
            $response['structure'] = $this->_structure;
        } else {
            $response['preview'] = substr($this->_result, 0, 250);
        }

        return $response;
    }

    private function _generateResponse()
    {
        $this->_responseGenerated = true;

        // Try to parse as JSON
        $result_data = json_decode($this->_result, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Successfully parsed as JSON - scan structure
            $this->_structure = Util::scanStructure($result_data);
        }
        // Otherwise structure stays null and getResponse() returns substring
    }

    /**
     * @deprecated Use getResponse() instead
     */
    public function getStructure() : ?array
    {
        if (!$this->_responseGenerated) {
            $this->_generateResponse();
        }
        return $this->_structure;
    }
}
