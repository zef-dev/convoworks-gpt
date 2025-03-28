

# Convoworks GPT WordPress Plugin

Convoworks GPT is an extension package for [Convoworks framework](https://github.com/zef-dev/convoworks-core). It is in the form of a WordPress plugin so you can use it with the [Convoworks WP](https://wordpress.org/plugins/convoworks-wp/).

This is a development version and it is yet decided will this package be part of the Convoworks core or will it stay as a separate plugin.

## Building the Plugin

You can use the prebuilt plugin version (in the releases section https://github.com/zef-dev/convoworks-gpt/releases) or you can build it by yourself.

When using it for the first time, you have to install the node packages. Navigate to the project root and enter the following command:

```bash
npm install
```
To build the deployment package (plugin) on your own, run this command, which will create the plugin zip. The version is taken from the `package.json` file:

```bash
npm run build
```

### Installation & Quick Start

* Download and activate **Convoworks WP** through your plugin installer (available on [WordPress.org](https://wordpress.org/plugins/convoworks-wp/)). No additional configuration is required.
* Upload the **convoworks-gpt** plugin zip file through your WordPress plugin installer and activate it.
* In your WP admin panel, navigate to Convoworks WP, click on **Create new**, enter the desired name, select the **GPT Example Chat** or **GPT Site Admin** template, and press **Submit**.
* In your newly created service, navigate to the **Variables** view and enter your OpenAI key into the `GPT_API_KEY` variable.
* Navigate to the **Test view** to try it out.

### Displaying Chat on Public Pages

* Navigate to the Configuration view and enable the Convo Chat platform (no additional settings are needed).
* Use the shortcode to display it on the front end: `[convo_chat service_id="gpt-example-chat" title="Example Chat"]`


## Service Templates

### GPT Example Chat

This template provides a simple and interactive chat experience. It includes a basic system prompt and, additionally, loads up to 10 pages into the chat context using retrieval-augmented generation (RAG). This approach makes it a ready-to-use chatbot for small business websites with a few pages, helping users find information that might otherwise be hard to locate.

The template also includes a simple GPT function example that can generate a random number.

This template is a solid base for building your next public-facing chat. For more details, check out [A Dead Simple RAG Setup for WordPress: AI Chatbots for Small Websites](https://convoworks.com/a-dead-simple-rag-setup-for-wordpress-ai-chatbots-for-small-websites/).

### GPT Site Admin

The GPT Site Admin template is designed as a robust AI assistant to help manage your system. In addition to basic instructions, this bot has access to PHP's `call_user_func()` function, enabling it to fetch and manipulate posts, create files, and more.

For more details, check out [The GPT Site Admin: A New Era of AI Integration with WordPress](https://convoworks.com/the-gpt-site-assistant-a-new-era-of-ai-integration-with-wordpress/).

### Deep Research Assistant

The Deep Research Assistant template empowers you to conduct iterative, in-depth research by leveraging GPT-powered recursive web searches, analysis, and report generation. It offers a no-code, visual workflow to automate the entire research process and seamlessly generate structured Markdown reports. For more details, check out [A No-Code Experiment: Bringing Deep Research to WordPress](https://convoworks.com/a-no-code-experiment-bringing-deep-research-to-wordpress/).


## Functions

Convoworks GPT WordPress Plugin provides predefined functions that can be utilized within workflows to extend the capabilities and provide more dynamic interactions.

### `tokenize_string`

**Description:**  
The `tokenize_string` function processes an input string by removing all HTML tags, converting it to lowercase, stripping punctuation, and then tokenizing it by spaces. The resulting tokens are further refined by removing common stop words, producing a meaningful representation suitable for use with the embeddings API.

**Parameters:**  
* `$text` - The input string to be tokenized.
* `$stopWords` (optional) - An array of words to be considered as stop words and removed from the tokenized output. If not provided, a default list of common English stop words will be used.

**Usage:**  
To tokenize a raw string:  
```
${tokenize_string(your_raw_text)}
```

To tokenize a raw string with custom stop words:  
```
${tokenize_string(your_raw_text, ["custom", "stop", "words"])}
```

**Default Stop Words:**  
The default stop words list includes common English words like "a", "about", "above", and so forth. These words are typically removed to produce a cleaner, more meaningful representation of the input.


### `serialize_gpt_messages`

This function serializes messages into human readable string.

**Parameters:**  
* `$messages` - Array of messages (in a GPT API format) that should be serialized
  

### `unserialize_gpt_messages`

This function deserializes a human-readable conversation string back into an array of messages in GPT API format.

**Parameters:**  
* `$string` - A string containing serialized conversation messages, formatted with roles and content, that should be converted back to an array format.

### `split_text_into_chunks`

**Description:**  
The `split_text_into_chunks` function splits a large text document into smaller chunks, making it suitable for indexing in a vector database. The function ensures that chunks are split at natural language boundaries (e.g., after a period, question mark, or exclamation mark) and adheres to a defined maximum character length. If a chunk is smaller than the margin, it gets appended to the previous chunk to avoid small, incomplete segments.

**Parameters:**  
* `$text` - The input text to be split into chunks.
* `$maxChar` (optional) - The maximum number of characters allowed in each chunk. The default is `30,000` characters.
* `$margin` (optional) - A margin to avoid creating very small chunks. If the final chunk is smaller than the margin, it will be appended to the previous chunk. The default margin is `1,000` characters.

**Usage:**  
To split a large document into chunks with default settings:
```
${split_text_into_chunks(large_document)}
```

To split a large document with custom chunk size and margin:
```
${split_text_into_chunks(large_document, 20000, 500)}
```

**How it works:**  
1. The function processes the text by splitting it at sentence boundaries (periods, question marks, or exclamation marks).
2. It accumulates text segments into chunks until the combined length exceeds the `maxChar` limit.
3. Any chunk smaller than the margin is appended to the previous chunk, ensuring that no small, incomplete chunks are created.



## Components

### GPT Chat Completion API Element

This is an OpenAI API wrapper element, which allows you to make calls and get the chat completion API response.


**Parameters:**

* `System message` - The initial `system` message in the conversation.
* `Messages` - An array of conversation messages, including the `assistant` and `user` roles.
* `Result Variable Name` - Defaults to `status`, this is the variable name that stores the complete API response. Access the completion text with: `${status.choices[0]["message"]["content"]}`
* `API key` - Your OpenAI API key.
* `Base URL` - Base URL for the API endpoint. If left blank, the default is the OpenAI API endpoint: https://api.openai.com/v1
* `API options` - Options for the [OpenAI Chat Completion API](https://platform.openai.com/docs/api-reference/chat).

**Flows:**
* `OK flow` - Executes elements after completion. Access completion results through the `result_var` variable.


### GPT Chat Completion API v2

This advanced component enables you to perform chat completion API calls with more dynamic capabilities and additional contexts.

**Parameters:**

* `Result Variable Name` - Default: `status`. The variable that stores the API completion response. By default, it is `status`, but this can be changed. The available values are: `response` (full API response), `messages` (array of messages for the current conversation), and `last_message` (the last message returned by GPT as an associative array).
* `API key` - Your OpenAI API key.
* `Base URL` - Base URL for the API endpoint. If left blank, the default is the OpenAI API endpoint: https://api.openai.com/v1
* `API options` - Configuration options for the chat completion API, such as the model, temperature, and token limit.

**Flows:**

* `Messages` - Defines a sub-flow that provides messages (the full conversation, including the prompt) and GPT functions for the chat completion API. This allows you to prepend agent definitions, add external data, or use conditional logic to determine the context. When handling GPT function calls, this flow is executed (messages are rebuilt) before each subsequent GPT API call, allowing function calls to affect the prompt definition.
* `New Message Flow` - This flow is executed after each GPT response or request. Use this flow to register new messages (default `${status.last_message}`) in your conversation.
* `OK Flow` - This flow is executed once the API call is finished.



### GPT Embeddings Element

This element serves as a wrapper for the OpenAI Embeddings API, enabling the retrieval of vector representations for a given input.

**Parameters:**

* `Input` - The string to be embedded. Recommended preprocessing using `tokenize_string()`: `${ tokenize_string( your_raw_text)}`.
* `Status Variable` - The variable that contains the complete API response. Access the embedding value with: `${status.data[0].embedding}`.
* `API key` - Your OpenAI API key.
* `Base URL` - Base URL for the API endpoint. If left blank, the default is the OpenAI API endpoint: https://api.openai.com/v1
* `API options` - Options for the Embeddings API.

**Flows:**
* `OK flow` - Executes when the operation completes and the result variable is available.


For more information on available API options, refer to the [Embeddings API Reference - OpenAI API](https://platform.openai.com/docs/api-reference/embeddings).

### GPT Moderation API

This element allows you to validate input with the OpenAI Moderation API. The Moderation API is a powerful tool for content moderation, helping you ensure that the generated content aligns with your guidelines and policies.

**Parameters:**

* `Input` - Text for moderation.
* `Result Variable Name` - Variable storing the moderation API response.
* `API key` - Your OpenAI API key.
* `Base URL` - Base URL for the API endpoint. If left blank, the default is the OpenAI API endpoint: https://api.openai.com/v1
* `API options` - Options for the OpenAI Moderation API.

**Flows:**
* `OK flow` - Executes once the moderation operation completes and the result variable is available.

For more information on the OpenAI Moderation API and its capabilities, refer to the [OpenAI Moderation API documentation](https://platform.openai.com/docs/api-reference/moderations).


### System Message

The System Message element defines a system-generated message within the chat context. These messages are primarily used in conjunction with the **GPT Chat Completion API v2** to prepend system-level information or context to a conversation. This can be useful for providing agents with a consistent introduction or setting the tone for the conversation.

**Parameters:**
* `Message Content` - Text content of the system message, which can be static or dynamically generated.

For use cases and more details on how system messages can be integrated with the Chat Completion API v2, refer to the associated component documentation.


### Group System Messages

This component groups itself with all child system messages into a single one. It helps in managing multiple system messages more effectively by combining them.

**Parameters:**

* `Trim Child Prompts` - When enabled, child messages are joined inline to create a more compact output.
* `Messages` - Defines a sub-flow that provides the messages to be grouped (System Message elements). The sub-flow executes to retrieve and group messages that are part of the conversation.



### Conversation Messages

The Conversation Messages element plays a pivotal role in handling conversations with the **GPT Chat Completion API v2**. It manages the storage and provision of the entire conversation that needs to be sent to the API. This ensures that the context and flow of the conversation remain intact during API interactions.

**Parameters:**
* `Messages` - Expression evaluating to an array of conversation messages. These chronologically ordered messages ensure contextual continuity.

For more details on how to properly set up and manage conversation messages with the GPT Chat Completion API v2, refer to the associated component documentation.


### GPT Messages Limiter

Limits the size of messages by summarizing the oldest ones.

**Parameters:**

- `System message` - The main system prompt.
- `Max messages to keep` - The maximum message count before older messages get summarized.
- `Truncate to this number of messages` - The message count to retain after truncation.
- `API key` - Your OpenAI API key.
- `Base URL` - The base URL for the API endpoint. If left blank, the default is the OpenAI API endpoint: `https://api.openai.com/v1`.
- `API options` - Options for summarizing the conversation.
- `Messages` - Provides the conversation messages.
- `Result variable` - The variable that stores the result of the truncation operation. This variable contains:
  - `messages`: The current set of retained messages.
  - `truncated`: The array of truncated messages.
- `Truncated Flow` - The flow to execute if the conversation is truncated.


### Simple Message Limiter

Limits the size of messages by trimming the array to a defined size.

**Parameters:**

- `Max messages to keep` - The maximum number of messages allowed before the conversation is trimmed.
- `Truncate message count` - The number of messages to retain after trimming.
- `Messages` - Provides the initial conversation messages array.
- `Result variable` - The variable that stores the result of the truncation operation. This variable contains:
  - `messages`: The current set of retained messages.
  - `truncated`: The array of truncated messages.
- `Truncated Flow` - The flow to execute if the conversation is truncated.


### Chat Function

Function definition that can be used with Completion API based elements.

**Parameters:**

* `Function name` - Unique function name.
* `Description` - Function description.
* `Function parameters` - Defines required function parameters. For more details on how to define parameter check [JSON Schema documentation](https://json-schema.org/understanding-json-schema/)
* `Defaults` - Default values for function parameters.
* `Required` - List of mandatory function fields.
* `Request data variable` - Variable for passing function arguments.
* `Function result` - Expression to determine the function result.

**Flows:**
* `OK flow` - Workflow executed when an action is requested through this function.

### External GPT Chat Function

This function definition can be used with Completion API-based elements. Unlike the regular chat functions element, this one delegates function execution via a Callable parameter.

**Parameters:**

* `Function Name` - A unique name for identifying the function.
* `Description` - A description of what the function does.
* `Function Parameters` - Definitions of all the parameters required by this function.
* `Defaults` - An associative array specifying default values for the function parameters.
* `Required` - A list of mandatory fields required for this function.
* `Callable` - A variable that holds the callable (e.g., function). When the function is executed, a single parameter (associative array) is passed, containing all defined parameters.



## Deprecated components

The following components are associated with the now-deprecated OpenAI endpoints. While these components remain in the package to ensure your existing services continue to function, they are no longer visible in the toolbox.


### GPT Query Generator

The GPT Query Generator element allows you to create context-rich questions from a given conversation. These questions can be utilized to query a knowledge database, enhancing GPT chat completion-based interactions by providing additional context and insights.

In your system message, you can use the `${conversation}` expression, which contains a serialized conversation summary. If omitted, the serialized conversation will be attached as a separate system prompt.

**Parameters:**

* `System Message` - Sets initial context and format for the conversation.
* `Messages` - Array of messages representing the GPT chat completion, acting as the primary context for question generation.
* `Messages Count` - Number of recent conversation messages to be considered.
* `Result Variable Name` - Variable storing the generated questions.
* `API key` - Your OpenAI API key.
* `API Options` - Parameters for the GPT chat completion API.

**Flows:**
* `OK Flow` - Executes after successful question generation.



### GPT Completion API Element

This is an OpenAI API wrapper element, which allows you to make calls and get the completion API response.

**Parameters:**

* `prompt` - The actual prompt sent for completion
* `result_var` - Default `status`, the name of the variable that contains the complete API response. You can access the completion text itself like this: `${status.choices[0]["message"]["content"]}`
* `api_key` - Your OpenAI API key
* `apiOptions` - [OpenAI Completion API](https://platform.openai.com/docs/api-reference/completions) endpoint options

**Flows:**

* `ok` - Elements to execute after completion. The completion result is accessible via the variable defined with `result_var`


### Autonomous Chat Element

This specialized conversational element enables the building of bots that are capable of interacting with your system (WordPress). This variant is built using the GPT-3 completion API.

The conversation is between the user, the bot, and the website. As usual, the user talks to the bot, and when necessary, the bot issues action commands to the website, which returns action results. When speaking (responding) to the user request, the bot speaks in plain English. When the bot wants the website to perform some action, it responds with a JSON message.

Here is an example conversation:

    Bot: Hi, how can I help you?
    User: I would like to book a demo.
    Bot: Great, when would you like to book it?
    User: Friday at 4 pm.
    Bot: {"action":"check_appointment_slot", "date":"2023-03-31", "time":"16:00"}
    Website: {"available":true}
    Bot: Great! That time is available. Please tell me your name and email.
    User: I'm John Smith, johnny@gmail.com
    Bot: {"action":"create_appointment", "date":"2023-03-31", "time":"16:00", "name":"John Smith", "email":"johnny@gmail.com"}
    Website: {"appointment_id":"abc344bh43"}
    Bot: Thanks. Your appointment is created. Can I help you with something else?

In order to enable this kind of behavior, this element delegates prompt building and actions to specialized components, configured in the Convoworks workflow. Prompt and action components can be used inside other elements (e.g. IF/ELSE, INCLUDE, FOR), allowing you to dynamically change the system behavior.

**Parameters:**

* `system_message` - The main prompt to generate completions for. It will be appended with eventual child Prompt elements.
* `user_message` - The new user message to append to the conversation.
* `messages` - An array containing all messages in the current conversation
* `result_var` - Default `status`, the name of the variable that contains additional information. It contains these fields: `messages` array of all conversation messages (including the last bot response), `bot_response` the last bot response (safe to display to the user), `last_prompt` the full last prompt which is sent to the API call. Useful for debugging.
* `api_key` - Your OpenAI API key
* `apiOptions` - [OpenAI Completion API](https://platform.openai.com/docs/api-reference/completions) endpoint options
* `skipChildPrompts` - When enabled, only this component prompt will be used. This can be useful when testing and tuning your prompts.

**Flows:**

* `prompts` - Prompt and Action definition components (`IChatPrompt` and `IChatAction` interfaces) which will participate in the prompt building. 
* `ok` - Elements to execute after completion. The completion result is accessible via the variable defined with `result_var`

### Turbo Chat Element

This component is quite similar to the previous one, with a difference that it is using GPT-3.5-urbo & chat completion API. As chat and completion API have different interfaces, there are slight differences in the element's parameters too. 

**Parameters:**

* `system_message` - The main prompt to generate completions for (prepended as `role = system` message).
* `messages` - An array containing all messages in the current conversation including the latest user request. These messages are following the chat API defined structure: `{"role":"msg_role", "content" : "Message content"}`
* `result_var` - Default `status`, the name of the variable that contains additional information. It contains these fields: `messages` array of all conversation messages (including the last bot response), `bot_response` the last bot response (also as a complex object. Use it to display a response to the user).
* `api_key` - Your OpenAI API key
* `apiOptions` - [OpenAI Chat Completion API](https://platform.openai.com/docs/api-reference/chat) endpoint options
* `skipChildPrompts` - When enabled, only this component prompt will be used. This can be useful when testing and tuning your prompts.

**Flows:**

* `prompts` - Prompt and Action definition components (`IChatPrompt` and `IChatAction` interfaces) which will participate in the prompt building. 
* `ok` - Elements to execute after completion. The completion result is accessible via the variable defined with `result_var`

### Simple Prompt  Element

This element allows you to split complex prompts into several, manageable sections. Use this element inside the **prompts** flow.

**Parameters:**

* `title` - The title for the prompt section.
* `content` - The content of the prompt section.


### Prompt Section  Element

Addition to the Simple Prompt which allows prompts grouping into sections. 

**Parameters:**

* `title` - The title for the prompt section.
* `content` - The content of the prompt section.

**Flows:**

* `prompts` - Child Prompt and Action definition components which will participate in the prompt building. 

### Simple Chat Action Element

This element allows you to define an action, with its ID and prompt definition, and to define a workflow which will be executed when the action is invoked. Use this element inside the **actions** flow.

**Parameters:**

* `action_id` - A unique action identifier.
* `title` - The title for the prompt section.
* `content` - The content of the prompt section.
* `action_var` - A variable containing action data which is available in the **OK** flow.
* `result` - A variable which evaluates to a result of the executed action. This (evaluated) value will be appended to the conversation as a website response.
* `autoActivate` - When enabled, it will call this action automatically and prepend the call to the conversation. Applicable for getting current user information or any other action which does not require input parameters.

**Flows:**

* `ok` - Elements to execute when the action is requested. 

### Validation Error Element

Stops the execution and signals the Chat App that the action request is not valid.

**Parameters:**

* `message` - Error message describing why the validation failed.

---

> Written with [StackEdit](https://stackedit.io/).
