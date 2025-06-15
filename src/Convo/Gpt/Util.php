<?php

declare(strict_types=1);

namespace Convo\Gpt;

abstract class Util
{

    /**
     * Serializes an array of messages into a formatted string.
     *
     * Filters messages to include only those with roles 'user' or 'assistant' and non-empty content by default.
     * If $includeSystem is true, 'system' messages are also included.
     * The output format is "Role: Content" for each message, separated by two newlines.
     *
     * @param array $messages The array of messages to serialize. Each message should be an associative array
     *                        with 'role' (string) and 'content' (string) keys.
     * @param bool $includeSystem Whether to include 'system' messages in the output. Default is false.
     * @return string The serialized string of messages in the format "Role: Content", with entries separated by two newlines.
     */
    public static function serializeMessages(array $messages, bool $includeSystem = false)
    {
        // Include 'user' and 'assistant' messages, and optionally 'system' messages
        $validRoles = ['user', 'assistant'];
        if ($includeSystem) {
            $validRoles[] = 'system';
        }

        $filteredMessages = array_filter($messages, function ($message) use ($validRoles) {
            return in_array($message['role'], $validRoles, true) && !empty(trim($message['content'] ?? ''));
        });

        return implode("\n\n", array_map(function ($message) {
            $role = ucfirst($message['role']);
            return sprintf("%s: %s", $role, $message['content']);
        }, $filteredMessages));
    }



    /**
     * Unserializes a formatted string into an array of messages.
     *
     * The input string should be in the format "Role: Content" for each message, with entries separated by two or more newlines.
     * Extracts the 'role' and 'content' for each message and converts the role to lowercase.
     *
     * @param string $string The serialized string of messages to unserialize.
     * @return array The unserialized array of messages. Each message is an associative array with 'role' (string) and 'content' (string) keys.
     */
    public static function unserializeMessages(string $string)
    {
        $messages = [];

        $entries = preg_split("/\n\n+/", trim($string));

        foreach ($entries as $entry) {
            if (preg_match('/^([A-Za-z]+):\s*(.*)$/s', $entry, $matches)) {
                $role = strtolower($matches[1]); // Convert role back to lowercase
                $content = $matches[2];

                $messages[] = [
                    'role' => $role,
                    'content' => $content,
                ];
            }
        }

        return $messages;
    }

    /**
     * Truncates conversation messages while preserving logical message structure.
     *
     * Groups messages as follows:
     * - A 'user' message followed by an 'assistant' message (max size 2)
     * - A 'tool_calls' message followed by all subsequent 'tool' messages
     *
     * When reducing, it removes groups from the start until it cannot remove any more without going below 'truncateTo'.
     *
     * @param array $messages Array of messages to potentially truncate.
     * @param int $maxMessages Maximum allowed length of the array.
     * @param int $truncateTo Minimum number of items to keep in the array if truncation is necessary.
     * @return array Truncated or original array.
     */
    public static function truncate(array $messages, int $maxMessages, int $truncateTo): array
    {
        // Initialize variables
        $groups = [];
        $currentGroup = [];
        $previousRole = null;

        // Group messages based on conversation turns
        foreach ($messages as $message) {
            $role = $message['role'];

            if (empty($currentGroup)) {
                $currentGroup[] = $message;
            } else {
                if ($role === 'user' && $previousRole !== 'user') {
                    // Start a new group
                    $groups[] = $currentGroup;
                    $currentGroup = [$message];
                } else {
                    // Add to current group
                    $currentGroup[] = $message;
                }
            }
            $previousRole = $role;
        }

        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }

        // Calculate total messages
        $totalMessages = array_sum(array_map('count', $groups));

        // Truncate groups from the beginning while keeping constraints
        $startIndex = 0;
        while (($totalMessages > $maxMessages || $totalMessages > $truncateTo) && $startIndex < count($groups)) {
            $groupSize = count($groups[$startIndex]);
            if (($totalMessages - $groupSize) >= $truncateTo) {
                $totalMessages -= $groupSize;
                $startIndex++;
            } else {
                break;
            }
        }

        // Merge the remaining groups
        $truncatedMessages = [];
        for ($i = $startIndex; $i < count($groups); $i++) {
            $truncatedMessages = array_merge($truncatedMessages, $groups[$i]);
        }

        return $truncatedMessages;
    }

    /**
     * Truncates conversation messages to a specified size while preserving logical message structure.
     *
     * Groups messages as follows:
     * - A 'user' message followed by an 'assistant' message (max size 2)
     * - A 'tool_calls' message followed by all subsequent 'tool' messages
     *
     * When truncating, it removes groups from the start until the total size is less than or equal to the specified size.
     *
     * @param array $messages Array of messages to truncate.
     * @param int $size The target size to truncate the messages to.
     * @return array The truncated array of messages.
     */
    public static function truncateToSize(array $messages, int $size): array
    {
        // Group messages as in the original function
        $groups = [];
        $currentGroup = [];
        $previousRole = null;

        foreach ($messages as $message) {
            $role = $message['role'];

            if (empty($currentGroup)) {
                $currentGroup[] = $message;
            } else {
                if ($role === 'user' && $previousRole !== 'user') {
                    $groups[] = $currentGroup;
                    $currentGroup = [$message];
                } else {
                    $currentGroup[] = $message;
                }
            }
            $previousRole = $role;
        }

        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }

        // Calculate total messages
        $totalMessages = array_sum(array_map('count', $groups));

        // Truncate groups from the beginning until totalMessages <= $size
        $startIndex = 0;
        while ($totalMessages > $size && $startIndex < count($groups)) {
            $groupSize = count($groups[$startIndex]);
            if (($totalMessages - $groupSize) >= $size) {
                $totalMessages -= $groupSize;
                $startIndex++;
            } else {
                break;
            }
        }

        // Merge the remaining groups
        $truncatedMessages = [];
        for ($i = $startIndex; $i < count($groups); $i++) {
            $truncatedMessages = array_merge($truncatedMessages, $groups[$i]);
        }

        return $truncatedMessages;
    }





    /**
     * Returns the truncated part of the original messages.
     *
     * @param array $originalMessages The original array of messages.
     * @param array $truncatedMessages The truncated array of messages.
     * @return array The truncated part of the messages.
     */
    public static function getTruncatedPart(array $originalMessages, array $truncatedMessages): array
    {
        $originalCount = count($originalMessages);
        $truncatedCount = count($truncatedMessages);

        // Calculate the number of messages truncated
        $truncatedSize = $originalCount - $truncatedCount;

        // Return the truncated part from the start of the original array
        return array_slice($originalMessages, 0, $truncatedSize);
    }

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

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $json;
        }

        $data = self::resolveStringConstantValues($data);

        return json_encode($data);
    }

    /**
     * Resolves eventual PHP constants passed as string values.
     * @param array $data
     * @return array
     */
    public static function resolveStringConstantValues($data)
    {
        foreach ($data as $key => $val) {
            if (is_string($val) && defined($val)) {
                $data[$key] = constant($val);
            } else if (is_array($val)) {
                $data[$key] = self::resolveStringConstantValues($val);
            }
        }
        return $data;
    }

    public static function estimateTokens($content)
    {
        $word_count = str_word_count($content);
        $char_count = mb_strlen($content);
        $tokens_count_word_est = intval($word_count / 0.75);
        $tokens_count_char_est = intval($char_count / 4);
        $result = intval(($tokens_count_word_est + $tokens_count_char_est) / 2);
        return $result;
    }

    public static function splitTextIntoChunks($text, $maxChar, $margin)
    {
        $chunks = [];
        if (empty($text)) {
            return $chunks;
        }
        $currentChunk = "";

        $parts = preg_split('/(\.|\?|!)\s+/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            if (strlen($currentChunk . $part) > $maxChar) {
                $chunks[] = $currentChunk;
                $currentChunk = $part;
            } else {
                $currentChunk .= $part;
            }
        }

        if (!empty(trim($currentChunk))) {
            if (strlen($currentChunk) > $margin) {
                $chunks[] = $currentChunk;
            } else {
                // append to the last one if it is a small chunk
                $last_index = count($chunks) - 1;
                $chunks[$last_index] .= $currentChunk;
            }
        }

        return $chunks;
    }
}
