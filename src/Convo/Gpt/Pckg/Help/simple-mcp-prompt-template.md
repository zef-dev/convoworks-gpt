### Simple MCP Prompt Template

Defines a reusable prompt template for MCP (Model Context Protocol) servers. Prompt templates allow clients to retrieve pre-configured prompts with argument substitution, making it easy to provide consistent, parameterized instructions to AI models.

### When to use

Use **Simple MCP Prompt Template** when you need to:

- Expose reusable prompt templates via MCP
- Provide parameterized prompts that clients can invoke
- Standardize prompts for common tasks (summarize post, analyze content, etc.)
- Build MCP servers with prompt libraries
- Support MCP client applications (Claude Desktop, IDEs, etc.)

This element must be used inside the **Tools** flow of **MCP Processor**.

### Properties

#### Prompt Name (name)

Unique identifier for this prompt template. This is how MCP clients will reference the prompt.

Example: `summarize_post`, `analyze_content`, `generate_meta_description`

Use descriptive, snake_case names.

#### Description

Human-readable summary of what this prompt does and when to use it. Displayed in MCP clients when browsing available prompts.

Example:

```text
Summarizes a WordPress post into a concise paragraph. Useful for creating post excerpts or social media descriptions.
```

#### Prompt Arguments (arguments)

Expression that evaluates to an array of argument definitions. Each argument should have:

- **name** – Argument name (snake_case)
- **description** – What the argument is used for
- **required** – Boolean indicating if the argument is mandatory (default: false)

Example:

```text
${[
  {"name": "postId", "description": "WordPress post ID to summarize", "required": true},
  {"name": "maxLength", "description": "Maximum summary length in words (default: 50)", "required": false}
]}
```

#### Prompt

The actual prompt template text. Reference arguments using `${argumentName}` placeholder syntax.

The prompt supports full expression language evaluation, so you can:

- Reference arguments: `${postId}`, `${maxLength}`
- Call functions: `${get_post_content(postId)}`
- Use conditionals: `${maxLength ? "Keep it under " . maxLength . " words." : ""}`

Example:

```text
Summarize the following WordPress post into a concise paragraph. Keep the summary under ${maxLength} words.

Post content:
${get_post_content(postId)}

Provide a clear, engaging summary that captures the main points.
```

### Runtime behavior

When used in an MCP server:

1. During **MCP Processor** initialization, the prompt is registered with the MCP server
2. MCP clients can list available prompts via `prompts/list`
3. When a client calls `prompts/get` with this prompt's name and arguments:
   - Required arguments are validated
   - Arguments are merged with the prompt template
   - The prompt text is evaluated with argument values substituted
   - The result is returned as a user message to the client
4. Additionally, if this element is used inside a **GPT Chat Completion API v2** messages flow, it acts as a **System Message** with the evaluated prompt content

### Dual mode operation

This element can work in two contexts:

1. **Inside MCP Processor** → Registered as an MCP prompt template
2. **Inside Chat Completion v2 Messages flow** → Acts as a system message (for non-MCP usage)

This allows you to reuse the same prompt definitions for both MCP and direct chat completion.

### Example

**Post summarization prompt**:

**Simple MCP Prompt Template**:

- **Prompt Name**: `summarize_post`
- **Description**: `Creates a concise summary of a WordPress post. Useful for generating post excerpts or social media descriptions.`
- **Prompt Arguments**:
  ```text
  ${[
    {"name": "postId", "description": "WordPress post ID", "required": true},
    {"name": "maxWords", "description": "Maximum words in summary (default: 50)", "required": false}
  ]}
  ```
- **Prompt**:
  ```text
  Summarize the following blog post in ${maxWords ? maxWords : 50} words or less.
  
  Title: ${get_post_field("post_title", postId)}
  
  Content:
  ${get_post_field("post_content", postId)}
  
  Create a compelling summary that captures the main message and would work well as a social media description.
  ```

**MCP Usage**: Client calls `prompts/get` with `{"name": "summarize_post", "arguments": {"postId": 123, "maxWords": 30}}` and receives the evaluated prompt.

### Example: Content analysis prompt

**Simple MCP Prompt Template**:

- **Prompt Name**: `analyze_seo`
- **Description**: `Analyzes a page for SEO optimization opportunities. Provides suggestions for improving search ranking.`
- **Prompt Arguments**:
  ```text
  ${[
    {"name": "url", "description": "Page URL to analyze", "required": true},
    {"name": "focusKeyword", "description": "Target SEO keyword (optional)", "required": false}
  ]}
  ```
- **Prompt**:
  ```text
  Analyze the following page for SEO optimization:
  
  URL: ${url}
  ${focusKeyword ? "Focus Keyword: " . focusKeyword : ""}
  
  Page Content:
  ${fetch_page_content(url)}
  
  Provide:
  1. Current SEO strengths
  2. Areas for improvement
  3. Specific recommendations for:
     - Title tag
     - Meta description
     - Heading structure
     - Content keywords
     ${focusKeyword ? "- Keyword density for '" . focusKeyword . "'" : ""}
  ```

### Example: Code generation prompt

**Simple MCP Prompt Template**:

- **Prompt Name**: `generate_wp_shortcode`
- **Description**: `Generates a WordPress shortcode based on requirements. Creates both the shortcode registration code and usage examples.`
- **Prompt Arguments**:
  ```text
  ${[
    {"name": "shortcodeName", "description": "Name for the shortcode", "required": true},
    {"name": "functionality", "description": "What the shortcode should do", "required": true},
    {"name": "attributes", "description": "Comma-separated list of shortcode attributes", "required": false}
  ]}
  ```
- **Prompt**:
  ```text
  Generate a WordPress shortcode with the following specifications:
  
  Shortcode Name: [${shortcodeName}]
  Functionality: ${functionality}
  ${attributes ? "Attributes: " . attributes : "No attributes"}
  
  Provide:
  1. Complete PHP code for registering the shortcode
  2. Usage examples with different attribute combinations
  3. Best practices and security considerations
  ```

### Tips

- Use clear, descriptive prompt names that indicate the template's purpose
- Write detailed argument descriptions – they guide MCP clients on what values to provide
- Mark essential arguments as `required: true`
- Provide default values in the prompt template for optional arguments: `${maxLength ? maxLength : 50}`
- Test prompts with various argument combinations to ensure they work correctly
- Keep prompts focused on a single task or purpose
- Include context and instructions that would help the AI produce better results
- Use expression language to dynamically fetch WordPress data: `get_post_field()`, `get_user_meta()`, etc.
- For long prompts, consider using multiple paragraphs or sections for clarity
- Include examples or desired output format in the prompt when helpful
- MCP clients can list all available prompts – ensure your descriptions are clear and differentiated
- Argument validation is basic (required check only) – add additional validation in the prompt text if needed
- The evaluated prompt is returned as a user message to the MCP client
- This element can be used alongside **Chat Function** and **WP REST Proxy Function** in the same MCP server

