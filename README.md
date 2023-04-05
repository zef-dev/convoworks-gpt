
# Convoworks GPT WordPress Plugin

Convoworks GPT is an extension package for [Convoworks framework](https://github.com/zef-dev/convoworks-core). It is in the form of a WordPress plugin so you can use it with the [Convoworks WP](https://wordpress.org/plugins/convoworks-wp/).

## Building the Plugin

When using it for the first time, you have to install the node packages. Navigate to the project root and enter the following command:

```bash
npm install
```

To build the deployment package, run this command, which will create the plugin zip. The version is taken from the `package.json` file:

```bash
node build.js
```

## GPT Package Overview

After installing and activating the plugin, you will be able to enable an additional package, **convo-gpt**, in the Convoworks Editor. This package contains several components, allowing for easy access to the OpenAI API. 

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

* `prompts` - Prompt definition components (`IChatPrompt` interface) which will participate in the prompt building. 
* `actions` - Action components (`IChatAction` interface) capable of executing actions. Because the system needs to know about them, they also participate in the prompt building (check the "Actions Prompt Generator Element")  
* `ok` - Elements to execute after completion. The completion result is accessible via the variable defined with `result_var`

### Prompt Section Element

This element allows you to split complex prompts into several, manageable sections. Use this element inside the **prompts** flow.

**Parameters:**

* `title` - The title for the prompt section.
* `content` - The content of the prompt section.

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

### Actions Prompt Generator Element

This element will collect all defined actions and append their prompts to its own. This way, each enabled action will be listed and the bot will be aware of it. Use this element inside the **prompts** flow.

**Parameters:**

* `title` - The title for the prompt section.
* `content` - The content of the prompt section.

> Written with [StackEdit](https://stackedit.io/).