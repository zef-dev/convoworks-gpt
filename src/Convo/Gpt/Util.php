<?php declare(strict_types=1);

namespace Convo\Gpt;

abstract class Util {

    /**
     * Tries to correct invalid JSON data in cases when GPT uses PHP constants in function calls
     * @param string $json
     * @return string
     */
    public static function processJsonWithConstants($json)
    {
        // Placeholder for escaped double quotes
        $PLACEHOLDER = "***convo-json-placeholder***";
        // Pattern representing escaped double quotes in JSON
        $PATTERN = '\\\\"';

        // Temporarily replace escaped double quotes with a placeholder
        $json = str_replace($PATTERN, $PLACEHOLDER, $json);

        $json = preg_replace_callback('/("[^"]*")|(\b[A-Z_]+\b)/', function ($matches) {
            // If part of a string, return as is
            if ($matches[1]) return $matches[1];

            $constantName = $matches[2];

            // Check if it is a defined constant
            if (defined($constantName)) {
                // Get the value of the constant
                $constantValue = constant($constantName);

                // Replace with the actual value of the constant, ensuring it's JSON-encoded
                return is_numeric($constantValue) ? $constantValue : json_encode($constantValue);
            }

            // If it's not a defined constant, leave as is
            return $constantName;
        }, $json);

        // Restore the escaped double quotes
        $json = str_replace($PLACEHOLDER, $PATTERN, $json);

        $data = json_decode( $json, true);

        if ( json_last_error() !== JSON_ERROR_NONE) {
            return $json;
        }

        $data = self::resolveStringConstantValues( $data);

        return json_encode( $data);
    }

    /**
     * Resolves eventual PHP constants passed as string values.
     * @param array $data
     * @return array
     */
    public static function resolveStringConstantValues( $data)
    {
        foreach ( $data as $key=>$val) {
            if ( is_string( $val) && defined( $val)) {
                $data[$key] = constant( $val);
            } else if ( is_array( $val)) {
                $data[$key] = self::resolveStringConstantValues( $val);
            }
        }
        return $data;
    }
}
