
## Convoworks GPT WordPress plugin

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
