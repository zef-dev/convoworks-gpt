<?php

declare(strict_types=1);

namespace Convo\Gpt;

abstract class Util
{

    /**
     * Truncates conversation messages while preserving logical message structure.
     *
     * Groups messages as follows:
     * - A 'user' message followed by an 'assistant' message (max size 2)
     * - A 'tool_calls' message followed by all subsequent 'tool' messages
     *
     * When reducing, it removes groups from the start until reaching the desired size.
     * If a group is too large to remove without going below 'truncateTo', it stops.
     *
     * @param array $messages Array of messages to potentially truncate.
     * @param int $maxMessages Maximum allowed length of the array.
     * @param int $truncateTo Minimum number of items to keep in the array if truncation is necessary.
     * @return array Truncated or original array.
     */
    public static function truncate(array $messages, int $maxMessages, int $truncateTo): array
    {
        $totalMessages = count($messages);

        // If the number of messages is within the allowed max, return them as-is
        if ($totalMessages <= $maxMessages) {
            return $messages;
        }

        // Group messages
        $groups = [];
        $i = 0;
        $count = count($messages);

        while ($i < $count) {
            $group = [];

            if ($messages[$i]['role'] === 'user') {
                $group[] = $messages[$i];
                $i++;

                if ($i < $count && $messages[$i]['role'] === 'assistant') {
                    $group[] = $messages[$i];
                    $i++;
                }
            } elseif ($messages[$i]['role'] === 'tool_calls') {
                $group[] = $messages[$i];
                $i++;

                while ($i < $count && $messages[$i]['role'] === 'tool') {
                    $group[] = $messages[$i];
                    $i++;
                }
            } else {
                // Any other role, group it individually
                $group[] = $messages[$i];
                $i++;
            }

            $groups[] = $group;
        }

        // Now, reduce groups from the start until total messages <= maxMessages and >= truncateTo
        $currentTotalMessages = $totalMessages;
        $groupIndex = 0;

        while ($currentTotalMessages > $maxMessages && $groupIndex < count($groups)) {
            $groupSize = count($groups[$groupIndex]);

            if ($currentTotalMessages - $groupSize >= $truncateTo) {
                // Remove this group
                $currentTotalMessages -= $groupSize;
                $groupIndex++;
            } else {
                // Cannot remove this group without falling below truncateTo
                break;
            }
        }

        // Now assemble the remaining messages from the remaining groups
        $remainingGroups = array_slice($groups, $groupIndex);
        $truncatedMessages = [];

        foreach ($remainingGroups as $group) {
            $truncatedMessages = array_merge($truncatedMessages, $group);
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
}
