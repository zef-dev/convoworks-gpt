
# Convoworks GPT WordPress Plugin

Convoworks GPT is an extension package for [Convoworks framework](https://github.com/zef-dev/convoworks-core). It is in the form of a WordPress plugin so you can use it with the [Convoworks WP](https://wordpress.org/plugins/convoworks-wp/).

This is a development version and it is yet decided will this package be part of the Convoworks core or will it stay as a separate plugin.

## Building the Plugin

You can use prebuilt plugin version (in the `./build` folder) or you can build it by yourself.

When using it for the first time, you have to install the node packages. Navigate to the project root and enter the following command:

```bash
npm install
```

To build the deployment package, run this command, which will create the plugin zip. The version is taken from the `package.json` file:

```bash
node build.js
```
## Current status

Two API components, GPT Completion API and GPT Chat Completion API are quite stable and we do not expect many changes with them.

The  Autonomous Chat Element might be changed in future releases. There are couple of experiments and issues we would like to try with it:

* try it with the gpt-3.5-turbo (now it is working with the plain GPT-3)
* improve usage of the signed in user available data (now you have to tell to the Bot to use account data)
* implement long term memory

## GPT Package Overview

After installing and activating the plugin, you will be able to enable an additional package, **convo-gpt**, in the Convoworks Editor. This package contains several components, allowing for easy access to the OpenAI API. 

Package contains an example service template **GPT Examples** you can use to see how the components can be used. You can access templates when creating a new service.  **Remember to set your OpenAI `API_KEY` in service variables view.**
This service contains three examples, completion api, chat api and autonomous chat. You can chose the active one in the "Session Start" service step by modifying GOTO element in it. 

For more information on the OpenAI API and its use, please refer to their [documentation](https://platform.openai.com/docs/).

### GPT Completion API Element

This is an OpenAI API wrapper element, which allows you to make calls and get the completion API response.

**Parameters:**

* `prompt` - The actual prompt sent for completion
* `result_var` - Default `status`, the name of the variable that contains the complete API response. You can access the completion text itself like this: `${status.choices[0]["message"]["content"]}`
* `api_key` - Your OpenAI API key
* `apiOptions` - [OpenAI Completion API](https://platform.openai.com/docs/api-reference/completions) endpoint options

**Flows:**

* `ok` - Elements to execute after completion. The completion result is accessible via the variable defined with `result_var`

### GPT Chat Completion API Element

This is an OpenAI API wrapper element, which allows you to make calls and get the chat completion API response.

**Parameters:**

* `system_message` - The initial `system` message in the conversation.
* `messages` - An array of conversation messages, containing only the `assistant` and `user` roles
* `result_var` - Default `status`, the name of the variable that contains the complete API response. You can access the completion text itself like this: `${status.choices[0]["message"]["content"]}`
* `api_key` - Your OpenAI API key
* `apiOptions` - [OpenAI Chat Completion API](https://platform.openai.com/docs/api-reference/chat) endpoint options

**Flows:**

* `ok` - Elements to execute after completion. The completion result is accessible via the variable defined with `result_var`

### Autonomous Chat Element

This specialized conversational element enables the building of bots that are capable of interacting with your system (WordPress). 

The conversation is between the user, the bot, and the website. As usual, the user talks to the bot, and when necessary, the bot issues action commands to the website, which returns action results. When speaking (responding) to the user request, the bot speaks in plain English. When the bot wants the website to perform some action, it responds with a JSON message.

Here is an example conversation:

    Bot: Hi how can I help you?
    User: I would like to book a demo.
    Bot: Great when would you like to book it?
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

**Flows:**

* `ok` - Elements to execute when the action is requested. 

### Validation Error Element

Stops the execution and signals the Chat App that action request is not valid.

**Parameters:**

* `message` - Error message describing why the validation failed.


> Written with [StackEdit](https://stackedit.io/).