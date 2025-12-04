### MCP Processor

Implements the Model Context Protocol (MCP) server functionality, handling protocol initialization, tool calls, prompt templates, and resources. This processor acts as the bridge between MCP clients (like Claude Desktop, IDEs) and your Convoworks components.

### When to use

Use **MCP Processor** when you need to:

- Expose your Convoworks service as an MCP server
- Provide tools, prompts, and resources to MCP clients
- Build AI assistants that integrate with Claude Desktop or other MCP-enabled applications
- Create serverless AI agents accessible via standardized protocol
- Expose WordPress content and functionality to AI applications

This processor should be added to a service configured with the **MCP Server** platform.

### Properties

#### Server Name (name)

Label returned during MCP protocol initialization. This identifies your server to clients and appears in logs and client UIs.

Example: `Convoworks MCP`, `WordPress Site Assistant`, `Content Manager Bot`

The name can be dynamic: `${site_name} Assistant`

#### Server Version (version)

Version string for your MCP server. Use semantic versioning (e.g., `1.0`, `2.1.3`) to manage updates and track compatibility.

Example: `1.0`, `1.2.0`

Clients may use this for logging or compatibility checks.

#### Tools

Container for child components that implement MCP tools, prompts, or chat functions. Drag components here to expose them via MCP.

Supported child components:

- **Chat Function** – Exposed as MCP tools
- **WP REST Proxy Function** – WordPress REST API as MCP tools
- **External Chat Function** – Custom PHP functions as MCP tools
- **Simple MCP Prompt Template** – Reusable prompt templates
- Any component that registers functions or prompts

### Runtime behavior

#### MCP Protocol Flow

1. **Initialization** (`initialize`):
   - Client connects and sends protocol version
   - Server validates protocol version (`2025-06-18` or `2025-03-26` supported)
   - Server returns capabilities, server info, and session ID
   - Session is activated and stored

2. **Capability Discovery**:
   - Client calls `tools/list` to discover available tools
   - Client calls `prompts/list` to discover available prompts
   - Client calls `resources/list` to discover available resources (currently empty)

3. **Tool Execution** (`tools/call`):
   - Client requests tool execution with name and arguments
   - Server locates the matching **Chat Function** or **WP REST Proxy Function**
   - Tool executes (runs the function's OK Flow or REST call)
   - Result is returned to client as text content
   - Errors are caught and returned with `isError: true`

4. **Prompt Retrieval** (`prompts/get`):
   - Client requests a prompt template with name and arguments
   - Server validates required arguments
   - Prompt template is evaluated with arguments
   - Evaluated prompt is returned as a user message

5. **Session Management**:
   - Sessions are identified by unique session IDs
   - Sessions timeout after `CONVO_GPT_MCP_SESSION_TIMEOUT` (default: 30 days)
   - Inactive sessions are cleaned up automatically
   - Ping requests (`ping`) keep sessions alive

#### Supported MCP Methods

- `initialize` – Protocol handshake
- `ping` – Keep-alive / health check
- `tools/list` – List all available tools (from Chat Functions)
- `tools/call` – Execute a tool
- `prompts/list` – List all available prompt templates
- `prompts/get` – Retrieve and evaluate a prompt template
- `resources/list` – List resources (currently returns empty)
- `resources/templates/list` – List resource templates (currently returns empty)
- `completion/complete` – Completion suggestions (currently returns empty)
- `notifications/initialized` – Client initialization complete notification

### Protocol Capabilities

The processor announces these capabilities during initialization:

```json
{
  "capabilities": {
    "tools": {"listChanged": true},
    "prompts": {"listChanged": true},
    "completions": {}
  }
}
```

### Example

**Basic MCP server with WordPress tools**:

**Service Configuration**:

1. Configure service with **MCP Server** platform
2. Add **MCP Processor** to Process phase

**MCP Processor**:

- **Server Name**: `WordPress Content Manager`
- **Server Version**: `1.0`
- **Tools**:
  1. **WP REST Proxy Function** (`list_posts`):
     - Lists published posts with filters
  2. **WP REST Proxy Function** (`create_draft_post`):
     - Creates draft posts
  3. **Chat Function** (`search_products`):
     - Searches WooCommerce products
  4. **Simple MCP Prompt Template** (`summarize_post`):
     - Generates post summaries

**Result**: MCP clients can discover and use these 3 tools and 1 prompt template.

### Example: Advanced MCP server with custom tools

**MCP Processor**:

- **Server Name**: `${site_name} AI Assistant`
- **Server Version**: `2.0`
- **Tools**:
  1. **Group System Messages** (for documentation):
     - Describes available tools and usage guidelines
  2. **WP REST Proxy Function** – Multiple REST API tools:
     - `list_posts`, `get_post`, `create_post`, `update_post`, `delete_post`
     - `list_pages`, `create_page`, `update_page`
     - `list_users`, `get_user`
     - `list_comments`, `moderate_comment`
  3. **Chat Function** – Custom business logic:
     - `calculate_pricing` – Dynamic price calculations
     - `check_inventory` – Product stock levels
     - `send_notification` – Email/SMS notifications
  4. **Simple MCP Prompt Template** – Prompt library:
     - `analyze_content_seo`
     - `generate_social_post`
     - `summarize_comments`

### Session Management

Sessions are managed by `McpSessionManager`:

- Session IDs are generated by clients during initialization
- Sessions store state across multiple MCP requests
- Sessions timeout after `CONVO_GPT_MCP_SESSION_TIMEOUT` (default: 2592000 seconds = 30 days)
- Cleanup runs on each `tools/list`, `tools/call`, or `prompts/get` request
- Sessions are stored via `McpFilesystemSessionStore` (default) or custom storage

### Error Handling

Errors during tool execution are caught and returned to the client:

```json
{
  "content": [{"type": "text", "text": "{\"error\": \"Post not found\"}"}],
  "isError": true
}
```

RPC errors (protocol errors) are returned with JSON-RPC format:

```json
{
  "jsonrpc": "2.0",
  "id": "request-id",
  "error": {"code": -32602, "message": "Missing required argument 'postId'"}
}
```

### Configuration Constants

Defined in plugin main file, override in `wp-config.php`:

```php
// Session storage path
define('CONVO_GPT_MCP_SESSION_STORAGE_PATH', '/path/to/storage');

// Session timeout (seconds)
define('CONVO_GPT_MCP_SESSION_TIMEOUT', 2592000);

// Background poll interval (microseconds)
define('CONVO_GPT_MCP_LISTEN_USLEEP', 300000);

// Ping interval (seconds)
define('CONVO_GPT_MCP_PING_INTERVAL', 10);
```

### Tips

- Test your MCP server with Claude Desktop or MCP Inspector before deploying
- Organize tools logically – group related functions together
- Provide clear, detailed descriptions for tools and prompts – clients use these to understand what's available
- Use **WP REST Proxy Function** for standard WordPress operations – it's faster than building custom Chat Functions
- Use **Chat Function** for custom business logic that isn't covered by REST API
- Monitor session storage size – old sessions should be cleaned up automatically but check if issues arise
- Use descriptive server names and versions – they appear in client logs and UIs
- For security, ensure proper WordPress authentication and authorization checks in your tools
- Test error handling – ensure tools return meaningful error messages, not exceptions
- Keep tool results concise – MCP clients may have message size limits
- Document your MCP server's tools and prompts – provide a README or help endpoint
- Use **Simple MCP Prompt Template** for reusable prompt patterns that clients can invoke with different arguments
- For large deployments, consider implementing custom session storage (database instead of filesystem)
- MCP protocol version `2025-06-18` is currently supported (and `2025-03-26` for backward compatibility)

