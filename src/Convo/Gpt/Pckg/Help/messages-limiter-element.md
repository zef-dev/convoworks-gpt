### GPT Messages Limiter

Limits the size of the conversation by summarizing the oldest messages using the Chat Completion API. When the conversation exceeds the specified token limit, this element removes old messages and replaces them with an AI-generated summary, preserving conversation context.

### When to use

Use **GPT Messages Limiter** when you need to:

- Maintain conversation context while managing token limits
- Preserve important information from older messages
- Build long-running conversations that exceed model context windows
- Implement intelligent context compression
- Prevent context length overflow while maintaining conversation quality

For simpler use cases where old messages can be discarded without summarization, consider **Simple Messages Limiter** instead (faster, no API cost).

### Properties

#### System message

The system prompt that instructs GPT on how to summarize the conversation. This should clearly explain what information to preserve and how to structure the summary.

Default:

```text
Considering all the prior conversation including the previous summaries, please generate a concise summary capturing the key points and significant themes up until now. Please ensure the summary contains all necessary information to understand the context of the current conversation.
```

Customize this to focus on specific aspects of your conversations.

#### Max Tokens to Keep

The maximum estimated token count allowed in the conversation before summarization is triggered.

Example: `${8192}`

When the total token count exceeds this limit, the element will summarize and truncate old messages.

#### Truncate to Tokens

The estimated token count to retain after summarization. This should be significantly lower than **Max Tokens to Keep** to provide headroom.

Example: `${4096}`

After truncation, the remaining messages (plus the summary) will total approximately this many tokens.

#### API key

Your OpenAI API key for the summarization API call. Store this in a service variable.

Example: `${GPT_API_KEY}`

#### Base URL

Optional. Base URL for the API endpoint. Defaults to `https://api.openai.com/v1`.

#### API options

Configuration options for the Chat Completion API used for summarization:

- **model** – Model to use for summarization (e.g., `gpt-4o`, `gpt-3.5-turbo`)
- **temperature** – Controls summary creativity (0.1 = factual, 0.7 = creative)
- **max_tokens** – Maximum tokens in the summary

Default values:

```text
model: gpt-4o
temperature: ${0.1}
max_tokens: ${2048}
```

Use a lower temperature for factual, consistent summaries.

#### Result Variable Name

The name of the variable available in the **Truncated Flow**. Default is `status`.

The variable contains:

- `${status.messages}` – Array of remaining messages (including summary) after truncation
- `${status.truncated}` – Array of messages that were removed (before summarization)

#### Messages (message_provider)

A sub-flow that provides the conversation messages to be checked and potentially summarized. Typically contains **Conversation Messages** or **System Message** elements.

#### Truncated Flow

Flow executed when messages are truncated and summarized. This runs **after** summarization but **before** messages are passed to the parent container.

Use this flow to:

- Log summarization events
- Store the summary for later reference
- Update conversation metadata
- Track API usage for summarization

### Runtime behavior

When the element executes:

1. The message_provider sub-flow runs, collecting messages
2. Total token count is estimated for all messages
3. If total exceeds **Max Tokens to Keep**:
   - Messages are removed from the beginning until total is below **Truncate to Tokens**
   - Removed messages are serialized and sent to GPT for summarization
   - GPT generates a concise summary of the removed messages
   - The summary is inserted as a system message at the beginning
   - Truncated Flow executes with result variable
4. Remaining messages (with summary prepended) are registered with the parent container

**Summarization process**:

1. A system message with your summarization prompt is created
2. A user message containing the serialized old messages is created
3. Chat Completion API is called with both messages
4. The AI response becomes the summary
5. Summary is prepended as a system message to remaining messages

### Extends Simple Messages Limiter

This element extends **SimpleMessagesLimiterElement** and adds summarization on top of basic truncation. All properties and behavior from Simple Messages Limiter apply, plus the summarization step.

### Example

**Long-running customer support conversation**:

**GPT Messages Limiter**:

- **System message**: `Summarize this customer support conversation. Include: customer's main issues, solutions provided, outstanding problems, and any important customer details mentioned.`
- **Max Tokens to Keep**: `${10000}`
- **Truncate to Tokens**: `${5000}`
- **API key**: `${GPT_API_KEY}`
- **API options**:
  - model: `gpt-4o`
  - temperature: `${0.1}`
  - max_tokens: `${1500}`
- **Messages** sub-flow:
  - **Conversation Messages**: `${conversation}`
- **Truncated Flow**:
  - **Log Message**: `Summarized ${count(status.truncated)} messages`
  - **Set Param** (session): `last_summary_time = ${date("Y-m-d H:i:s")}`

### Example: Specialized summarization

**Technical conversation with code**:

**System message**:

```text
Summarize this technical conversation. Preserve:
1. All code snippets mentioned (in code blocks)
2. Technical decisions made and their rationale
3. Outstanding bugs or issues
4. Architecture or design patterns discussed
Format as a bulleted list for easy reference.
```

This ensures the summary maintains technical details and formatting.

### Example: Recursive summarization

**Handle very long conversations with multiple summaries**:

The element automatically handles previously summarized conversations:

- First summarization: Summarizes messages 1-20
- Second summarization: Summarizes messages 1-40, including the previous summary
- Third summarization: Summarizes everything, including multiple previous summaries

The default system prompt instructs GPT to "consider all prior conversation including the previous summaries".

### Tips

- Use a lower temperature (0.1-0.3) for factual, consistent summaries
- Use a specific summarization prompt tailored to your use case (support, research, general chat, etc.)
- Set max_tokens for summary to 1500-2000 to get detailed summaries without excessive cost
- Log summarization events to track API usage and cost
- Test your summarization prompt – review generated summaries to ensure they capture what matters
- For critical conversations, store truncated messages in a database before they're lost
- Monitor the frequency of summarization – if it happens too often, increase **Max Tokens to Keep**
- The summary is prepended as a system message, so it provides context for all subsequent messages
- Summarization costs tokens – estimate ~500-1500 tokens per summarization depending on conversation length
- Use **Simple Messages Limiter** instead if summaries aren't needed (saves cost and latency)
- For best results, use `gpt-4o` or similar high-quality model for summarization
- Consider implementing a cap on summary length to prevent summaries themselves from becoming too long

