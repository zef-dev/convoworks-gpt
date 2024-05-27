
# Convoworks GPT WordPress plugin

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
