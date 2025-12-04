### Simple Messages Limiter

Limits the size of the conversation by trimming old messages based on token count. Unlike **GPT Messages Limiter**, this element does not summarize – it simply removes the oldest messages when the conversation exceeds the specified limit.

### When to use

Use **Simple Messages Limiter** when you need to:

- Keep conversation context within model token limits
- Remove old messages without summarization (faster, no API cost)
- Implement a sliding window conversation approach
- Prevent context length overflow errors
- Manage long-running conversations efficiently

For conversations where context from old messages is important, consider using **GPT Messages Limiter** instead, which summarizes removed messages.

### Properties

#### Max Tokens to Keep

The maximum estimated token count allowed in the conversation before trimming. When the total token count of messages exceeds this limit, old messages are removed.

Example: `${8192}`

Tokens are estimated using a simple calculation (not exact tokenization).

#### Truncate to Tokens

The estimated token count to retain after trimming. This should be lower than **Max Tokens to Keep** to provide a buffer before the next truncation.

Example: `${4096}`

When truncation is triggered, messages are removed from the beginning until the total is below this value.

#### Max messages to keep (Deprecated)

Legacy parameter for message count-based truncation. Use **Max Tokens to Keep** instead for better control.

#### Truncate message count (Deprecated)

Legacy parameter for count-based truncation. Use **Truncate to Tokens** instead.

#### Result Variable Name

The name of the variable available in the **Truncated Flow**. Default is `status`.

The variable contains:

- `${status.messages}` – Array of remaining messages after truncation
- `${status.truncated}` – Array of messages that were removed

#### Messages (message_provider)

A sub-flow that provides the conversation messages to be checked and potentially truncated. Typically contains **Conversation Messages** or **System Message** elements.

#### Truncated Flow

Flow executed when messages are truncated. This flow runs **after** truncation but **before** messages are passed to the parent container.

Use this flow to:

- Log truncation events
- Update conversation metadata
- Notify the user about removed messages
- Store truncated messages for later reference

### Runtime behavior

When the element executes:

1. The message_provider sub-flow runs, collecting messages
2. Total token count is estimated for all messages
3. If total exceeds **Max Tokens to Keep**:
   - Messages are removed from the beginning (oldest first)
   - Removal continues until total is below **Truncate to Tokens**
   - Truncated Flow executes with result variable containing remaining and removed messages
4. Remaining messages are registered with the parent `IMessages` container

**Truncation strategy**: Messages are removed from the start of the array (oldest messages first). System messages and recent messages are preserved.

### Token estimation

Token count is estimated using a simplified formula:

- Text is split by whitespace and punctuation
- Each word/token is counted
- Overhead for message structure is added

This is not exact GPT tokenization but provides a reasonable approximation for context management.

### Example

**Basic conversation limiting**:

**Simple Messages Limiter**:

- **Max Tokens to Keep**: `${8000}`
- **Truncate to Tokens**: `${4000}`
- **Result Variable Name**: `status`
- **Messages** sub-flow:
  - **Conversation Messages**: `${conversation}`
- **Truncated Flow**:
  - **Log Message**: `Truncated ${count(status.truncated)} messages from conversation`

**How it works**:

1. Conversation starts with 0 messages
2. Messages accumulate in `${conversation}` session variable
3. When total exceeds 8000 estimated tokens, oldest messages are removed
4. Truncation continues until total is below 4000 tokens
5. Log message records truncation event
6. Remaining messages are sent to GPT

### Example: User notification

**Notify user when truncation occurs**:

**Truncated Flow**:

1. **Set Param** (request scope):
   - Name: `show_truncation_notice`
   - Value: `${true}`

2. **Log Message**: `Removed ${count(status.truncated)} old messages (estimated ${estimate_tokens(serialize_gpt_messages(status.truncated))} tokens)`

**OK Flow** (in Chat Completion v2):

1. **If** element: `${show_truncation_notice}`
   - **True flow**: **Text Response**: `Note: I've cleared some older messages from our conversation to free up space for new information.`

### Example: Nested with system messages

**Include system messages in truncation check**:

**Messages flow in Chat Completion v2**:

1. **Simple Messages Limiter**:
   - **Max Tokens to Keep**: `${10000}`
   - **Truncate to Tokens**: `${5000}`
   - **Messages** sub-flow:
     1. **System Message**: `You are a helpful assistant. Today is ${date("Y-m-d")}.`
     2. **Conversation Messages**: `${conversation}`

**Result**: System messages are included in token count but typically preserved since they're at the end of the array.

### Comparison: Simple vs. GPT Messages Limiter

| Feature | Simple Messages Limiter | GPT Messages Limiter |
|---------|------------------------|---------------------|
| **Method** | Removes old messages | Summarizes old messages |
| **API cost** | Free (no API calls) | Costs tokens (uses Chat Completion API) |
| **Speed** | Fast | Slower (waits for API) |
| **Context preservation** | None (messages lost) | Good (summary preserved) |
| **Best for** | Less important conversations, cost optimization | Important conversations requiring context |

### Tips

- Set **Max Tokens to Keep** to about 75% of your model's context window
- Set **Truncate to Tokens** to about 50% to provide headroom before next truncation
- For GPT-4o (128k context), you might use max: 90000, truncate: 60000
- For GPT-4 (8k context), use max: 6000, truncate: 3000
- Always keep at least a few recent messages – don't truncate too aggressively
- Use **Simple Messages Limiter** for cost-sensitive applications
- Use **GPT Messages Limiter** when conversation context is critical
- Monitor `${count(status.truncated)}` in the truncated flow to track truncation frequency
- Consider storing `${status.truncated}` in a log or database for conversation history analysis
- System messages added after this element are not included in truncation (they're added later)
- Nested multiple limiters to implement tiered truncation strategies (e.g., remove then summarize)

