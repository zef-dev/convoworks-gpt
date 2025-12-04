### Group System Messages

Groups multiple child **System Message** elements into a single combined system message. This helps organize complex prompts and manage multiple system instructions more effectively.

### When to use

Use **Group System Messages** when you need to:

- Combine multiple system messages into one
- Organize complex prompts with conditional or dynamic parts
- Reduce the number of separate system messages in the conversation
- Build system prompts from modular components
- Control formatting of combined system instructions

This element is typically used in the **Messages** (message_provider) flow of **GPT Chat Completion API v2**.

### Properties

#### Trim Child Prompts (trim_children)

Controls how child messages are joined together:

- **false** (default): Child messages are separated with double newlines (`\n\n`)
- **true**: Child messages are joined inline without spacing (trimmed)

Use `true` for compact, inline formatting. Use `false` for better readability with paragraph breaks.

#### Messages (message_provider)

A sub-flow containing **System Message** elements that will be grouped. The sub-flow executes, collecting all child system messages, then combines them into a single system message.

### Runtime behavior

When the element executes:

1. The message_provider sub-flow runs, collecting all **System Message** elements
2. The content of each child message is extracted
3. If `trim_children` is true, contents are joined inline (no spacing)
4. If `trim_children` is false, contents are separated with `\n\n`
5. A single combined system message is registered with the parent `IMessages` container
6. The combined message is marked as `transient: true`

### Message structure

The registered message has this structure:

```text
{
  "role": "system",
  "transient": true,
  "content": "<combined content from all children>"
}
```

### Example

**Grouping role and instructions**:

**Group System Messages**:

- **Trim Child Prompts**: `false`
- **Messages** sub-flow:
  1. **System Message**: `You are a helpful WordPress site administrator assistant.`
  2. **System Message**: `You have access to the WordPress REST API for managing posts, pages, users, and settings.`
  3. **System Message**: `Always confirm before making destructive changes like deleting content or deactivating plugins.`

**Result**: A single system message with content:

```text
You are a helpful WordPress site administrator assistant.

You have access to the WordPress REST API for managing posts, pages, users, and settings.

Always confirm before making destructive changes like deleting content or deactivating plugins.
```

### Example: Inline formatting

**Group System Messages**:

- **Trim Child Prompts**: `true`
- **Messages** sub-flow:
  1. **System Message**: `Current date: ${date("Y-m-d")}.`
  2. **System Message**: ` User: ${current_user.display_name}.`
  3. **System Message**: ` Site: ${site_name}.`

**Result**: A single compact system message:

```text
Current date: 2025-12-04. User: John Doe. Site: My WordPress Site.
```

### Example: Conditional system instructions

**Group System Messages**:

- **Trim Child Prompts**: `false`
- **Messages** sub-flow:
  1. **System Message**: `You are a customer support assistant for ${company_name}.`
  2. **If** element: `${user_is_premium}`
     - **True flow**: **System Message**: `This is a premium customer. Provide priority support and offer advanced features.`
  3. **If** element: `${hour(date("H")) >= 18}`
     - **True flow**: **System Message**: `It's currently after business hours. Inform the user that live chat support resumes at 9 AM.`

**Result**: A dynamically composed system message based on user status and time.

### Example: Modular prompt components

**Organize complex prompts into logical sections**:

**Group System Messages**:

- **Messages** sub-flow:
  1. **Group System Messages** (Role definition):
     - **System Message**: `You are an AI assistant specialized in technical support.`
  2. **Group System Messages** (Available tools):
     - **System Message**: `You have access to the following tools: search_knowledge_base, create_support_ticket, check_server_status.`
  3. **Group System Messages** (Rules and guidelines):
     - **System Message**: `Never share sensitive information. Always verify user identity before accessing account details.`

### Tips

- Use `trim_children: false` for better readability – it's easier for GPT to parse separate paragraphs
- Use `trim_children: true` when building compact inline metadata or lists
- Group related instructions together – don't mix unrelated concepts in one group
- Combine with conditional elements (**If**) to dynamically include/exclude sections based on context
- Nest **Group System Messages** elements to create hierarchical prompt structures
- Use **Group System Messages** to separate static prompts from dynamic ones
- Remember that the grouped message still counts as a single system message in the API request
- Grouped messages are marked `transient: true` – they won't appear in `getConversation()` results
- This element is particularly useful when building prompts from template files or database-stored instructions
- For very long prompts, consider using **Messages Limiter** to keep total context under control

