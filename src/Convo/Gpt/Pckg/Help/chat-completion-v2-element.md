### GPT Chat Completion API v2

Facilitates chat completion API calls with advanced capabilities, including function calling, dynamic message providers, and flexible conversation management. This is the recommended element for production chat applications.

### When to use

Use **GPT Chat Completion API v2** when you need:

- **Function calling** – Let GPT invoke functions/tools to access data or perform actions
- **Dynamic message building** – Build the conversation context programmatically before each API call
- **Complex conversation flows** – Handle multi-turn conversations with function calls and context updates
- **Message flow hooks** – React to each new message (both from GPT and function calls)
- **Better control** – Manage message arrays, function definitions, and conversation state dynamically

For simple chat completions without function calling, **GPT Chat Completion API** is sufficient.

### Properties

#### Result Variable Name

The variable that stores the API completion response. Default is `status`.

The result variable contains:

- `response` – Full API response body as an associative array
- `messages` – Array of all messages in the conversation (available only when New Message Flow is empty, for backward compatibility)
- `last_message` – Last message returned from GPT as an associative array

#### API key

Your OpenAI API key. Store this in a service variable for security.

Example: `${GPT_API_KEY}`

#### Base URL

Optional. Sets the base URL for the API endpoint. If left blank, defaults to `https://api.openai.com/v1`.

Use this to point to custom endpoints, proxy servers, or OpenAI-compatible APIs.

#### Max Function Result Tokens

Maximum number of tokens allowed for function results. Default is `${8192}`.

If a function returns a result larger than this limit, a runtime error is thrown. This prevents excessive token usage and context length issues.

#### API options

Configuration options for the chat completion API:

- **model** – The model to use (e.g., `gpt-4o`, `gpt-4`, `gpt-3.5-turbo`)
- **temperature** – Controls randomness (0.0 to 2.0)
- **max_tokens** – Maximum tokens in the response

Default values:

```text
model: gpt-4o
temperature: ${0.1}
max_tokens: ${4096}
```

For all available options, see the [OpenAI Chat Completion API Reference](https://platform.openai.com/docs/api-reference/chat).

#### Messages (message_provider)

A sub-flow that provides the conversation messages and GPT function definitions for the chat completion API.

**Important behavior**: This flow is executed (messages are rebuilt) **before each GPT API call**, including after function calls. This allows function calls to dynamically affect the prompt and context.

Inside this flow:

- Use **Conversation Messages** or **System Message** elements to provide messages
- Add **Chat Function** or **WP REST Proxy Function** elements to register GPT functions/tools
- Use **Messages Limiter** or **Simple Messages Limiter** to manage context length

The message provider flow should build the complete conversation context each time it runs.

#### New Message Flow

Executed after each new message is created – both GPT responses and function call messages.

Use this flow to:

- Store messages in your conversation history (session/database)
- Log interactions for debugging or analytics
- Trigger side effects based on message content
- Update conversation metadata

**Available variables in New Message Flow**:

- `${status.last_message}` – The newly created message object
- `${status.response}` – Full API response (null for function call messages)

#### OK flow

Executed once the entire operation is finished (after all function calls are resolved) and the result variable is ready.

**Available variables in OK Flow**:

- `${status.response}` – Full API response body
- `${status.last_message}` – Last message returned from GPT
- `${status.messages}` – All conversation messages (only when New Message Flow is empty)

### Runtime behavior

#### Execution flow

1. **Message provider flow executes** – Builds messages array and registers functions
2. **API call to GPT** – Sends messages and function definitions
3. **Response handling**:
   - If GPT returns a regular message → New Message Flow runs → OK Flow runs
   - If GPT returns function calls → For each function call:
     - GPT's function call message is added → New Message Flow runs
     - Function executes
     - Function result is added → New Message Flow runs
     - Message provider flow **re-executes** to rebuild context
     - **New API call** to GPT with updated messages
     - Repeat until GPT returns a regular message
4. **Final response** → OK Flow executes

#### Function calling

When GPT requests a function call:

- The function name and arguments are extracted from the response
- The corresponding **Chat Function** element is located and executed
- The function result is added as a `tool` message
- The conversation context is rebuilt (message provider runs again)
- A new API call is made with the function result included

Function call protection:

- Each unique function call (name + arguments) is tracked
- After 3 identical calls, execution is stopped to prevent infinite loops
- A warning is issued after 3 attempts, and an error is thrown on the 4th

#### Message types

Messages support standard OpenAI roles:

- `system` – System instructions
- `user` – User messages
- `assistant` – GPT responses
- `tool` – Function call results (with `tool_call_id`)

Messages can also have a `transient` flag set to `true` – these are excluded from the conversation history returned by `getConversation()`.

#### Function result size limit

If a function returns more tokens than `max_func_result_tokens`, a `RuntimeException` is thrown with a helpful message suggesting the user adjust function arguments to return less data.

### Example

**Configuration**:

- **Result Variable Name**: `status`
- **API key**: `${GPT_API_KEY}`
- **Max Function Result Tokens**: `${8192}`
- **API options**:
  - model: `gpt-4o`
  - temperature: `${0.1}`
  - max_tokens: `${2048}`

**Messages flow**:

1. Add **System Message**: `You are a helpful assistant with access to the WordPress site.`
2. Add **Conversation Messages**: `${conversation}`
3. Add **Chat Function** elements or **WP REST Proxy Function** elements for available tools

**New Message Flow**:

1. Add **Set Param** element:
   - Scope: `session`
   - Name: `conversation`
   - Value: `${array_merge(conversation, [status.last_message])}`

**OK Flow**:

1. Add **Text Response**: `${status.last_message.content}`

### Tips

- Always use the **message_provider** flow to rebuild the full conversation context – don't try to pass a static messages array
- Use **New Message Flow** to persist messages to session/database after each turn
- Implement **Messages Limiter** or **Simple Messages Limiter** in the message provider to prevent context length overflow
- Keep function results concise – summarize or paginate large datasets instead of returning everything
- Monitor function call loops – if GPT keeps calling the same function, improve the function description or return better error messages
- Use `transient: true` on messages that shouldn't be stored in conversation history (e.g., verbose function results)
- Test function calling scenarios thoroughly – ensure function descriptions clearly explain when and how to use each function
- Set appropriate `max_func_result_tokens` based on your model's context window and expected function results
- Access the response text with `${status.last_message.content}` or `${status.response.choices[0].message.content}`

