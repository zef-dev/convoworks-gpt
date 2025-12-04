### System Message

Defines a system-generated message within the chat context for **GPT Chat Completion API v2**. System messages set the behavior, personality, and context for the AI assistant and are crucial for consistent AI responses.

### When to use

Use **System Message** when you need to:

- Provide instructions or context to the AI assistant
- Set the AI's personality, tone, or role
- Define the AI's capabilities or limitations
- Include dynamic context (date, user info, site settings) in the prompt
- Add transient instructions that shouldn't be stored in conversation history

This element is typically used in the **Messages** (message_provider) flow of **GPT Chat Completion API v2**.

### Properties

#### Message content (content)

The text content of the system message. This can be static text or dynamic content using expression language.

Example values:

- `You are a helpful customer support assistant for ${site_name}.`
- `Today is ${date("l, F j, Y")}. Answer questions about our products and services.`
- `You are an expert in ${topic}. Provide detailed, accurate information.`

The content is evaluated as an expression by default, allowing you to inject dynamic values.

#### Disable evaluation (disable_eval)

When enabled, prevents expression language evaluation in the content field. The content is used as literal text.

Default: `false`

Use this when:

- You want to include `${...}` syntax as literal text (for demonstrating expression language)
- You have pre-formatted content that shouldn't be evaluated
- You want to avoid accidental expression evaluation in user-provided content

### Runtime behavior

When the element executes:

1. The content is evaluated (unless `disable_eval` is true)
2. A system message object is registered with the parent `IMessages` container
3. The message is marked as `transient: true` by default

**Transient flag**: System messages created by this element are marked as transient, meaning they are excluded from the conversation history returned by `getConversation()`. They are still sent to the API but don't appear in the persistent conversation array.

### Message structure

The registered message has this structure:

```text
{
  "role": "system",
  "transient": true,
  "content": "<evaluated content>"
}
```

### Integration with Chat Completion API v2

**System Message** is typically used at the beginning of the **Messages** (message_provider) flow in **GPT Chat Completion API v2**:

**Messages flow**:

1. **System Message**: Main instructions for the AI
2. **Conversation Messages**: Load conversation history
3. *[Optional]* Additional **System Message** elements for context-specific instructions

**Why multiple system messages?**

You can add multiple **System Message** elements to organize your prompts:

- First system message: Core AI personality and role
- Second system message: Dynamic context (date, user profile, current page)
- Third system message: Task-specific instructions

GPT will consider all system messages when generating responses.

### Example

**Basic system message**:

- **Message content**: `You are a friendly travel assistant. Provide concise information about destinations, hotels, and activities.`
- **Disable evaluation**: `false`

**Dynamic system message with date**:

- **Message content**: `You are a helpful assistant. Today is ${date("l, F j, Y")}. The current time is ${date("H:i")}. Use this information when relevant to the user's query.`
- **Disable evaluation**: `false`

**User-personalized system message**:

- **Message content**: `You are assisting ${user_name}, a ${user_role} at ${company_name}. Tailor your responses to their professional context.`
- **Disable evaluation**: `false`

### Example: Multi-part system prompts

**Messages flow in Chat Completion v2**:

1. **System Message**:
   - Content: `You are a WordPress site administrator assistant with access to the WordPress REST API.`

2. **System Message**:
   - Content: `Current site: ${site_url}. Site name: ${site_name}. Admin user: ${current_user.display_name}.`

3. **System Message**:
   - Content: `You can create, read, update, and delete posts, pages, and users. Always confirm destructive actions before executing them.`

4. **Conversation Messages**:
   - Messages: `${conversation}`

### Example: Literal expression syntax

**When you need to show `${...}` as literal text**:

- **Message content**: `To access variables in Convoworks, use this syntax: \${variable_name}`
- **Disable evaluation**: `true`

This will send the literal text with `${variable_name}` to GPT without evaluation.

### Example: Conditional system message

**Add context only when needed**:

**Messages flow**:

1. **System Message**:
   - Content: `You are a helpful assistant.`

2. **If** element: `${request.platform == "amazon"}`
   - **True flow**:
     - **System Message**:
       - Content: `You are running on Amazon Alexa. Keep responses concise and voice-friendly.`

3. **Conversation Messages**: `${conversation}`

### Tips

- Keep system messages clear, specific, and concise – avoid overly long instructions
- Place the most important instructions in the first system message
- Use multiple system messages to separate concerns (role, context, rules)
- Include the current date/time when temporal context matters: `${date("Y-m-d H:i:s")}`
- System messages with `transient: true` won't be stored in the conversation array returned by Chat Completion v2
- System messages are processed by GPT on every API call, so dynamic values (like date) are always fresh
- For production chatbots, include usage guidelines (e.g., "Don't provide medical advice", "Don't share personal data")
- Test different system prompts – small wording changes can significantly impact AI behavior
- Avoid contradictory instructions across multiple system messages
- For showing expression syntax as examples, use `disable_eval: true`
- System messages appear as separate messages in the API request, so they count toward context length

