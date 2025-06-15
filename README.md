

# Convoworks GPT WordPress Plugin

Convoworks GPT is an extension package for [Convoworks framework](https://github.com/zef-dev/convoworks-core). It is in the form of a WordPress plugin so you can use it with the [Convoworks WP](https://wordpress.org/plugins/convoworks-wp/).

This is a development version and it is yet decided will this package be part of the Convoworks core or will it stay as a separate plugin.

> **Note:** The Convoworks WP plugin is currently unavailable on WordPress.org due to an automated disable triggered by email server issues. In the meantime, you can download it directly here:  
> https://downloads.wordpress.org/plugin/convoworks-wp.0.22.44.zip

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


## Working with MCP Server

1. **Enable the MCP Server platform**  
   After creating your service from the **MCP Server Example** template, open the **Configuration** view and toggle on **MCP Server**—no further setup is needed.

2. **Copy the base MCP URL**  
   In the service editor’s **Releases** view (under **Development**), click **LINK** next to `convo-gpt.mcp-server` to copy your MCP server’s base URL to the clipboard.

3. **Connect with an SSE‑enabled client**  
   Any MCP client that speaks Server‑Sent Events (SSE) can use this URL directly—just paste it into the “server URL” field (for example, cLine for VS Code).

### WordPress REST API as MCP Tools

Convoworks GPT exposes the full WP REST API as MCP tools—definitions are built‑in and activated via separate filters for each endpoint group. See **Example 3 – External Functions** in the MCP Server Example for a live demo.

**Available Filters:**  
`convo_mcp_register_wp_posts`, `convo_mcp_register_wp_pages`, `convo_mcp_register_wp_comments`, `convo_mcp_register_wp_users`, `convo_mcp_register_wp_media`, `convo_mcp_register_wp_plugins`, `convo_mcp_register_wp_taxonomies`, `convo_mcp_register_wp_settings`

---

### Optional: Proxy for stdio‑only clients

If your client only supports stdio (like Claude Desktop), you can bridge it via `mcp-proxy`:

1. **Install the proxy**  
   ```bash
   npm install -g mcp-proxy
   ```

2. **Configure your client**  
   In your `claude_desktop_config.json`, add (use endpoint url provided in the Releases view):
   ```jsonc
   "convoworks-proxy": {
     "command": "mcp-proxy",
     "args": [
       "http://localhost/wordpress/wp-json/convo/v1/public/service-run/external/convo-gpt/mcp-server/a/mcp-prototype"
     ]
   }
   ```
   This wraps the SSE endpoint over stdio so Claude Desktop can communicate with your MCP server.

### Configuration Constants

You can override these in your `wp-config.php` **before** the plugin loads—just add your own `define()` calls:

```php
// Filesystem path where MCP session files are stored (currently FS‑based, moving to DB in a future release)
define( 'CONVO_GPT_MCP_SESSION_STORAGE_PATH', '/custom/path/to/mcp-sessions/' );

// Session timeout in seconds (how long an inactive session stays alive)
define( 'CONVO_GPT_MCP_SESSION_TIMEOUT', 60 * 30 ); // default: 1,800 (30 minutes)

// Background poll interval (microseconds) for checking new messages
define( 'CONVO_GPT_MCP_LISTEN_USLEEP', 300_000 );   // default: 300,000 µs (0.3 s)

// Ping interval in seconds for background processes (0 to disable)
define( 'CONVO_GPT_MCP_PING_INTERVAL', 30 );       // default: 30 s
```

- **Session storage** is currently implemented on the filesystem under `CONVO_GPT_MCP_SESSION_STORAGE_PATH`.  
- In an upcoming release, sessions will be stored in the database instead of files.



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

### MCP Server Example

A demo service with several switchable examples demonstrating no‑code functions, WP REST Proxy calls, external REST‑API functions via filters, and reusable prompt templates.

#### Service Configuration Variables

Two service‑level variables let you control auth and which REST APIs are active:

**MCP_AUTH_USER_ID** (default: `0`) sets the WordPress user ID under which proxy calls run.  
**MCP_ACTIVE_REST_APIS** (default: `["convo_mcp_register_wp_posts"]`) is the list of filter names that enable specific REST‑API tools; only filters included here will be registered.

> **Security Note:** The MCP server currently has no built‑in authentication—anyone who knows your endpoint can connect. If you set **MCP_AUTH_USER_ID**, calls will run under that WordPress account (which could be dangerous on a public server). Future releases will add Basic Auth as a quick fix and OAuth2 for full security.

## Functions

Convoworks GPT WordPress Plugin provides predefined functions that can be utilized within workflows to extend the capabilities and provide more dynamic interactions.

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

### `estimate_tokens`

**Description:**  
The `estimate_tokens` function provides a robust estimate of the number of tokens in a given string, useful for working with GPT models (such as OpenAI's GPT-3/4).

It estimates the number of tokens by combining two heuristics:
- Calculates the number of words and divides by 0.75.
- Calculates the number of characters and divides by 4.
- The final estimate is the average of these two values, rounded down to the nearest integer.

**Parameters:**  
* `$content` - The string to estimate token count for.

**Usage:**  
To estimate the number of tokens in a string:
```
${estimate_tokens(content)}
```

This approach provides a more accurate estimate for English and similar languages, and is suitable for most GPT model tokenization needs.


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
* `Max Function Result Size` - Optional. Sets the maximum number of tokens allowed for the function result. Default is <code>8192</code>. This limits the size of the output returned by a function call within the chat completion process.
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
- `Max Tokens to Keep` - The maximum estimated token count allowed in the conversation before it gets summarized. Tokens are estimated for each message's content.
- `Truncate to Tokens` - The estimated token count to retain after the conversation is summarized. This value is set lower than the maximum to ensure the conversation size remains under control.
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

- `Max Tokens to Keep` - The maximum estimated token count allowed in the conversation before it gets summarized. Tokens are estimated for each message's content.
- `Truncate to Tokens` - The estimated token count to retain after the conversation is summarized. This value is set lower than the maximum to ensure the conversation size remains under control.
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


### Simple MCP Prompt Template

Defines a reusable prompt template you can invoke anywhere in your MCP pipeline.

**Parameters:**
- **Prompt Name**  
  Unique identifier for this template. Can be plain text or an expression wrapped in ``${…}``.
- **Description**  
  Short, human‑readable summary of what the prompt does. Displayed in the component preview.
- **Prompt Arguments**  
  Expression‑language array of argument definitions wrapped in ``${…}``.  
  Each item must include:
  - `name` (string)  
  - `description` (string)  
  - `required` (boolean, optional)  
  **Example:**  
  ``${[{"name":"postId","description":"WP post ID","required":true}]}``
- **Prompt**  
  The template text itself. Reference arguments using ``${argumentName}``. Supports multi‑line content and any valid expression constructs.

---

### WP REST Proxy Function

Exposes a WordPress REST API call under `/wp/v2/…` as a chat‑invokable function, with parameter mapping and optional cursor‑based pagination.

**Parameters:**
- **Function Name**  
  The name by which this function is called from chat messages.
- **Description**  
  Brief explanation shown in function selectors (e.g. “List posts with filters and paging”).
- **Required Parameters**  
  Expression‑language array of parameter keys that must be present, wrapped in ``${…}``.  
  **Example:** ``${["postId"]}``
- **Default Values**  
  Expression‑language object of default parameter values, wrapped in ``${…}``.  
  **Example:** ``${{"per_page":10,"status":"publish"}}``
- **HTTP Method**  
  HTTP verb to use (GET, POST, PUT, DELETE, etc.). Defaults to GET.
- **REST Endpoint**  
  Path under `/wp/v2/`, for example `posts` or `users/123`.
- **Enable Pagination**  
  When checked, responses include a `results` array and `nextCursor` for paging.
- **Function Parameters**  
  Define metadata for each parameter (type, description, enum, etc.) via a sub‑flow to generate JSON‑Schema for UI hints and validation.

---

### MCP Processor

Serves as the bridge between the MCP protocol and your Convoworks components. It wires up initialization, handles notifications, and automatically registers any child prompts, tools, or resources.

**Parameters:**
- **Name**  
  Label returned during protocol initialization. Helps identify this server instance.
- **Version**  
  Protocol version string exposed to clients. Use semantic versioning when updating.
- **Tools**  
  Drop in child components that implement prompts, resources, or chat functions. Each child is auto‑registered and exposed over MCP.


---

> Written with [StackEdit](https://stackedit.io/).
