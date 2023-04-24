# Convoworks GPT WordPress Plugin

Convoworks GPT is an extension package for [Convoworks framework](https://github.com/zef-dev/convoworks-core). It is in the form of a WordPress plugin so you can use it with the [Convoworks WP](https://wordpress.org/plugins/convoworks-wp/).

This is a development version and it is yet decided will this package be part of the Convoworks core or will it stay as a separate plugin.

## Building the Plugin

You can use the prebuilt plugin version (in the `./build` folder) or you can build it by yourself.

When using it for the first time, you have to install the node packages. Navigate to the project root and enter the following command:

```bash
npm install
```

To build the deployment package, run this command, which will create the plugin zip. The version is taken from the `package.json` file:

```bash
node build.js
```
### Installation

* Download and activate **Convoworks WP** through your plugin installer (it is on the WordPresss.org). No additional configuration is required.
* Upload the **convoworks-gpt** plugin zip through your WordPress plugin installer and activate it.
* In your WP admin, navigate to Convoworks WP, click on the **Create new**, enter desired name, select **GPT Examples** template and press **Submit**.
* In your newly created service, navigate to the **Variables** view and enter your OpenAI key.
* Navigate to **Test view** and try it.
* In service editor, **Session Start** step, modify the GOTO instruction to try different example (there are 4 examples. Default one is Turbo Chat). 

## Current status

Two API components, GPT Completion API and GPT Chat Completion API are quite stable and we do not expect many changes with them.

The  Autonomous Chat and Turbo Chat elements might be changed in future releases. There are couple of experiments and issues we would like to try with it:

* improve usage of the signed in user available data (now you have to tell to the Bot to use account data)
* implement long term memory

## GPT Package Overview

After installing and activating the plugin, you will be able to enable an additional package, **convo-gpt**, in the Convoworks Editor. This package contains several components, allowing for easy access to the OpenAI API. It also contains an example service template **GPT Examples** you can use it to see how the components can be used.

You can access templates when creating a new service.  **Remember to set your OpenAI `API_KEY` in the service variables view.**

For more information on the OpenAI API and its use, please refer to their [documentation](https://platform.openai.com/docs/).

### GPT Examples template

There are four examples in this template and you can choose which one is active in the **Session Start** step. All examples are ready to use and are demonstrating chat usage.

#### API examples

**Completion API Example** and Chat **Completion API Example** are demonstrating GPT API components usage. One is using the completion API while the second is using the chat completion API. Both examples are configured to work as simple chat assistants.

#### Autonomous chats examples

**Important! Please note that Autonomous chats examples are having quite large prompts (2,500 tokens).** 

**Chat App** and **Turbo Chat App** are using specialized components capable of executing actions on the website. Again, the main difference is in the API they are using (chat vs completion API).

The main idea behind these chat apps is to have ability to easily define and use actions which are interacting with your WordPress installation (check the **Fragments** tab for current actions).  

These examples are using two action packages which are integrated with the website.

The first is **newsletter subscription** related and they are enabling AI to check subscription status, register or unregister. The newsletter subscription list is implemented as a **custom post type** (default `my_newsletter_list` and can be changed on the variables view).

The second set of actions is for **appointment scheduling**.  It enables AI to check, schedule or reschedule appointments. These actions are using the Convoworks Appointments package and by default it is configured to use a dummy appointments context. Enable **convo-wp-plugin-pack** to replace it with the concrete **Simply Schedule Appointments**, **Easy Appointments** or **Five Star Restaurant Reservations** plugins. Check for [more about appointments](https://convoworks.com/appointment-scheduling-on-your-wordpress-website-now-with-amazon-alexa-skill/) on our blog.


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

Stops the execution and signals the Chat App that the action request is not valid.

**Parameters:**

* `message` - Error message describing why the validation failed.


> Written with [StackEdit](https://stackedit.io/).
