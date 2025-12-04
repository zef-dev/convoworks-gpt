### Conversation Messages

Manages and provides the entire conversation history to the **GPT Chat Completion API v2**, ensuring consistent context throughout the interaction. This element loads a stored conversation array and registers each message with the parent Chat Completion component.

### When to use

Use **Conversation Messages** when you need to:

- Load a conversation history stored in session or database
- Provide the full message thread to GPT Chat Completion API v2
- Maintain conversation context across multiple turns
- Integrate with **GPT Chat Completion API v2**'s message provider flow

This element must be used inside the **message_provider** flow of **GPT Chat Completion API v2**.

### Properties

#### Messages

Expression that evaluates to an array of conversation messages. Each message should be an object with `role` and `content` properties following the OpenAI chat format.

Example values:

- `${conversation}` – Load from session variable
- `${get_conversation(user_id)}` – Load from custom function
- `${[]}` – Start with empty conversation

**Message format**:

```text
${[
  {"role": "system", "content": "You are a helpful assistant."},
  {"role": "user", "content": "Hello!"},
  {"role": "assistant", "content": "Hi! How can I help you?"}
]}
```

### Runtime behavior

When the element executes:

1. The messages expression is evaluated
2. If the result is an array, each message is registered with the parent `IMessages` container (typically **GPT Chat Completion API v2**)
3. The registered messages become part of the conversation context sent to the API

**Important**: This element does not modify or store messages – it only loads and registers them. Message persistence is handled separately (typically in the **New Message Flow** of Chat Completion v2).

### Integration with Chat Completion API v2

**Conversation Messages** is typically used inside the **Messages** (message_provider) flow of **GPT Chat Completion API v2**:

**Chat Completion v2 configuration**:

- **Messages flow**:
  1. **System Message**: `You are a helpful assistant for ${site_name}.`
  2. **Conversation Messages**: `${conversation}` ← Loads conversation history
  3. *[Optional]* **Messages Limiter** or **Simple Messages Limiter** to manage context length

- **New Message Flow**:
  1. **Set Param** (session scope):
     - Name: `conversation`
     - Value: `${array_merge(conversation, [status.last_message])}` ← Saves new messages

This pattern ensures:
- Messages are loaded before each API call
- New messages (both from GPT and function calls) are appended to conversation
- Full conversation history is maintained across turns

### Example

**Basic conversation management**:

**Service Variables** (Configuration → Variables):

- `conversation` (default: `${[]}`)

**Chat Completion v2 configuration**:

**Messages flow**:

1. **System Message**:
   - Content: `You are a friendly customer support assistant. Today is ${date("l, F j, Y")}.`

2. **Conversation Messages**:
   - Messages: `${conversation}`

3. **Simple Messages Limiter**:
   - Max Tokens: `${8000}`
   - Truncate to Tokens: `${4000}`
   - Messages: (nested) **Conversation Messages** with `${conversation}`

**New Message Flow**:

1. **Set Param**:
   - Scope: `session`
   - Name: `conversation`
   - Value: `${array_merge(conversation, [status.last_message])}`

**OK Flow**:

1. **Text Response**: `${status.last_message.content}`

### Example: Database-backed conversation

**Load from custom storage**:

**Messages flow**:

1. **PHP Delegate**:
   - Code: Load conversation from database
   - Result variable: `db_conversation`

2. **Conversation Messages**:
   - Messages: `${db_conversation}`

**New Message Flow**:

1. **PHP Delegate**:
   - Code: Save new message to database
   - Parameters: `${status.last_message}`

### Tips

- Always initialize the conversation variable with an empty array `${[]}` in service configuration
- Use session scope for the conversation variable to persist across requests
- Wrap **Conversation Messages** inside a **Messages Limiter** or **Simple Messages Limiter** to prevent context length overflow
- The messages array can come from session variables, database queries, API responses, or any expression that evaluates to an array
- Each message object should have at minimum `role` and `content` properties
- Optionally, messages can include `name` (for function calls), `tool_calls`, `tool_call_id`, or `transient` flags
- If the expression evaluates to null or non-array, no messages are registered (no error)
- Don't confuse with **System Message** – use **Conversation Messages** for loading multi-turn history, use **System Message** for single static/dynamic instructions
- For debugging, log the messages count: `${count(conversation)}` to monitor conversation growth

