
## Convoworks GPT WordPress plugin

### 0.15.0 - 2025-06-16 - Token-based limiters

* Message limiter elements now use token-based limits.
* You can now limit function call result size (in tokens).
* Removed old, deprecated components.
* Added `estimate_tokens(content)` function.

### 0.14.0 - 2025-04-25 - Session ping

* Improved keep alive mechanism and check
* Added missing MCP logo 

### 0.13.2 - 2025-04-23 - Function scope fix

* Initialize properly scoped function call

### 0.13.1 - 2025-04-22 - Menu fix

* Remove MCP settings page from menu

### 0.13.0 - 2025-04-22 - MCP Server Support

* Added MCP Server platform.  
* Added **MCP Server Example** service template.  
* Added MCP Processor for routing MCP requests.  
* Added WP REST Proxy Function for forwarding REST API calls.  
* Added Simple MCP Prompt Template.


### 0.12.2 - 2025-03-27 - Deep Research template

* Added new service template - Deep Research Assistant.


### 0.12.1 - 2025-02-23 - Chat Functions Handling  

* Improved chat function execution with better error handling—now catches `Throwable`.  
* Used scoped functions as the base for all chat functions, enabling local function scope.  


### 0.12.0 Preview fixes

* Added function scope support to the Chat Function element

### 0.11.2 Preview fixes

* Fixed not displayed help for the Conversation Messages Element.
* Long System GPT messages are now trimmed (12 lines) when displayed in editor.
* The Chat Function Element now has description displayed in editor.  
* Fixed message serialization when message content is null (tool call).

### 0.11.1 Truncate messages

* Fixed the truncate messages function - grouping

### 0.11.0 Truncate messages

* Fixed truncate messages functions to preserve message grouping
* Message limiter elements now have truncated flow

### 0.10.0 Catchup with OpenAI API changes - tools

* GPT API components now support base url parameter, enabling using other AI providers
* Chat compčletion element now uses functions as part of tools definition
* Added `unserialize_gpt_messages()` function for unserializing stringified conversation into associative array.

### 0.9.2 Updated Service Templates

- Updated both service templates to take advantage of new features.
- Revised documentation and help files.

### 0.9.1 Serialize messages function

* Added `serialize_gpt_messages()` function for serialisation messages into readable string.

### 0.9.0 External gpt functions element

* Added `ExternalChatFunctionElement` which allows registering GPT functions from 3rd party plugins

### 0.8.0

* Added simple messages limiter
* Chat completion now rebuilds context even adter function calls
* Chat completion now has on a new message flow to more transparent messages handling
* Added `split_text_into_chunks()` el function  
* Added `SystemMessageGroupElement` for grouping prompt parts into the single system message. 

### 0.7.3 Regenerate context

* In function call scenarios, prompt (and functions) are now rebuilt for each GPT API call.

### 0.7.2 JSON schema fix

* Fixed `args` argument definition for the `call_user_func_array` in `gpt-site-assistant.template.json`

### 0.7.1 Arry syntax fix

* Corrected extra comma issue in `ChatCompletionV2Element` which caused error on the PHP 7.2

### 0.7.0 Include Function Results in Summarization

* Added the ability to include function results in the summarization process.
* Introduced `${conversation}`, a conversation summary that can be utilized within the query generator prompt.

### 0.6.0 Improved Chat Functions Execution

* Enhanced the execution of chat functions: Included fixes for JSON parsing, handled endless loops better, and improved error handling.
* Optimized the trimming of conversation messages. 

### 0.5.0 Chat functions support

* New Chat GPT API component with support for functions
* Added System Message and Message Limiter elements
* Added Embeddings and Moderation API elements

### 0.4.0 Auto activated actions

* Add ability to mark actions as auto activated (e.g. logged user info)
* Add ability to use just main Chat app prompt - child prompts will be ignored. Useful when testing and tuning prompts.

### 0.3.0 Turbo Chat App

* Add chat app which uses GPT-3.5-turbo API
* Update service template with the Turbochat app example

### 0.2.0 Refactor Chat App

* Refactor prompt & action interfaces
* Add validation error element
* Remove actions prompt element
* Update service template with appointment scheduling

### 0.1.0 Initial version
