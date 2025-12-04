### GPT Chat Completion API

Allows you to execute OpenAI Chat Completion API calls and get chat completion responses. This is a simple wrapper element for making chat API calls with a system message and conversation history.

### When to use

Use **GPT Chat Completion API** when you need to:

- Make straightforward chat completion API calls with OpenAI
- Quickly test GPT responses with a simple system prompt
- Build basic conversational interfaces without function calling
- Get chat completions without complex message management

For more advanced scenarios with function calling, dynamic message providers, and better control over the conversation flow, consider using **GPT Chat Completion API v2** instead.

### Properties

#### System message

The main system prompt that will be automatically prepended to the conversation. This sets the behavior and context for the AI assistant.

Example values:

- `The following is a conversation with an AI assistant. The assistant is helpful, creative, clever, and very friendly. Today is ${date("l, F j, Y")}.`
- `You are a customer support assistant for our e-commerce platform. Be professional and helpful.`

The system message is evaluated as an expression, so you can use dynamic values and functions.

#### Messages

An array of message objects in the OpenAI chat format. Each message should have a `role` (user, system, or assistant) and `content` (the message text).

Example format:

```text
${[
  {"role": "user", "content": "Hello!"},
  {"role": "assistant", "content": "Hi! How can I help you today?"},
  {"role": "user", "content": "Tell me a joke."}
]}
```

**Important**: This element does not automatically manage message persistence through the session. You are responsible for storing and retrieving the conversation history from session variables.

#### API key

Your OpenAI API key. This should typically be stored in a service variable for security.

Example: `${GPT_API_KEY}`

#### Base URL

Optional. Base URL for the API endpoint. If left blank, the default OpenAI API endpoint is used: `https://api.openai.com/v1`

Use this to point to custom endpoints, proxy servers, or OpenAI-compatible APIs.

#### API options

Configuration parameters for the Chat Completion API. Common options include:

- **model** – The model to use (e.g., `gpt-4o`, `gpt-4`, `gpt-3.5-turbo`)
- **temperature** – Controls randomness (0.0 to 2.0). Lower values make output more focused and deterministic
- **max_tokens** – Maximum number of tokens in the response

Default values:

```text
model: gpt-4o
temperature: ${0.7}
max_tokens: ${4096}
```

For all available options, see the [OpenAI Chat Completion API Reference](https://platform.openai.com/docs/api-reference/chat).

#### Result Variable Name

The name of the variable that will store the complete Chat Completion API response. Default is `status`.

The response contains the full API response object, including choices, usage statistics, and metadata.

#### OK flow

Sub-flow executed after the API call completes successfully. The result variable will be available for use in this flow.

### Runtime behavior

When the element executes:

1. The system message is evaluated and prepended to the messages array
2. An API call is made to OpenAI with the combined messages and options
3. The complete response is stored in the result variable (request scope)
4. The OK flow is executed with the result variable available

### Accessing the response

Inside the OK flow, access the completion text like this:

```text
${status.choices[0]["message"]["content"]}
```

The result variable contains the complete API response structure:

- `${status.choices}` – Array of completion choices
- `${status.choices[0].message}` – The assistant's message object
- `${status.choices[0].message.content}` – The actual response text
- `${status.usage}` – Token usage statistics

### Example

**Configuration**:

- **System message**: `You are a helpful travel assistant. Provide concise information about destinations.`
- **Messages**: `${conversation}`
- **API key**: `${GPT_API_KEY}`
- **API options**:
  - model: `gpt-4o`
  - temperature: `${0.5}`
  - max_tokens: `${1000}`
- **Result Variable Name**: `gpt_response`

**In the OK flow**, add a Text Response element:

> ${gpt_response.choices[0]["message"]["content"]}

### Tips

- Store your API key in a service variable (Configuration → Variables) instead of hardcoding it
- Use lower temperature values (0.1-0.3) for more consistent, focused responses
- Use higher temperature values (0.7-1.0) for more creative, varied responses
- Always handle the response inside the OK flow to ensure the API call completed successfully
- For production use with ongoing conversations, store the messages array in session scope and append to it with each turn
- Consider using **GPT Chat Completion API v2** if you need function calling or more flexible message management
- Monitor your token usage through `${status.usage.total_tokens}` to optimize costs

