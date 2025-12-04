# Convoworks GPT – Agent Documentation

This document provides an overview of the Convoworks GPT project, a WordPress plugin that extends the Convoworks WP plugin with GPT/AI-powered workflow components.

---

## Project Overview

**Convoworks GPT** is a WordPress plugin that extends [Convoworks WP](https://github.com/zef-dev/convoworks-wp) with AI-powered components for integrating OpenAI's GPT models, embeddings, moderation API, and MCP (Model Context Protocol) server capabilities into workflow automation.

### Key Features

- **GPT Chat Completion** – Components for OpenAI Chat Completion API with function calling support
- **Embeddings & Moderation** – Vector embeddings and content moderation API wrappers
- **MCP Server Platform** – Full Model Context Protocol server implementation with Streamable HTTP
- **WordPress REST API Integration** – Exposes WP REST API as MCP tools/functions
- **Service Templates** – Ready-to-use templates for chat apps, site admin assistants, research bots, and MCP servers

### Technology Stack

- **Backend**: PHP 7.2+, WordPress 5.3+
- **Dependencies**: Minimal (no heavy PHP libraries, just autoloader)
- **Build Tools**: Composer, npm (for packaging)
- **Extends**: Convoworks WP (required)

---

## Documentation

### 📦 [Build & Packaging](README.md#building-the-plugin)

The plugin has a simple build process:

```bash
npm install        # Install packaging dependencies
npm run build      # Creates distributable zip in build/
```

Unlike Convoworks WP, this plugin has:
- **No frontend JavaScript bundles** (no Webpack, no AngularJS UI)
- **Simple Composer autoload** (no complex scoping needed currently)
- **Straightforward packaging** – just copies source files and runs `composer install --no-dev`

### ⚙️ PHP Framework & Components

**Main namespace**: `Convo\Gpt` (`src/Convo/Gpt/`)

Key areas:

#### Core Classes (`src/Convo/Gpt/`)
- `GptPlugin.php` – Main plugin registration and initialization
- `GptApiFactory.php`, `GptApi.php` – OpenAI API client wrapper
- `PluginContext.php` – Plugin context and configuration helpers
- `Util.php` – Utility functions (token estimation, message serialization, etc.)
- Exception classes: `ContextLengthExceededException`, `RefuseFunctionCallException`

#### Component Package (`src/Convo/Gpt/Pckg/`)
- `GptPackageDefinition.php` – Package descriptor registering all GPT components
- `GptPackageDefinition.json` – JSON metadata for the package
- Component classes:
  - `ChatCompletionElement.php`, `ChatCompletionV2Element.php` – GPT API wrapper elements
  - `ChatFunctionElement.php`, `ExternalChatFunctionElement.php` – Function calling support
  - `ConversationMessagesElement.php` – Conversation state management
  - `SystemMessageElement.php`, `SystemMessageGroupElement.php` – System prompts
  - `MessagesLimiterElement.php`, `SimpleMessagesLimiterElement.php` – Context length management
  - `EmbeddingsElement.php`, `ModerationApiElement.php` – Other OpenAI API wrappers
  - `WpRestProxyFunction.php` – WordPress REST API as GPT function
  - `SimpleMcpPromptTemplate.php` – MCP prompt templates
  - `McpServerProcessor.php` – MCP server processor
- `Help/` folder – Markdown/HTML help files for each component
- `*.template.json` – Service templates (GPT Example Chat, Site Admin, Deep Research, MCP Server Example)

#### MCP Server Implementation (`src/Convo/Gpt/Mcp/`)
- `McpServerPlatform.php` – MCP platform adapter (implements `IRestPlatform`)
- `McpServerPublisher.php` – Release manager for MCP services
- `StreamableRestHandler.php` – Streamable HTTP handler for MCP protocol
- `StreamHandler.php`, `StreamWriter.php`, `SseResponse.php` – SSE streaming support
- `McpSessionManager.php`, `McpSessionManagerFactory.php` – Session management
- `IMcpSessionStoreInterface.php`, `McpFilesystemSessionStore.php` – Session storage
- `CommandDispatcher.php` – Routes MCP commands to service processors
- `McpServerCommandRequest.php` – MCP command request wrapper

#### WordPress REST API Tools (`src/Convo/Gpt/Tools/`)
- `AbstractRestFunctions.php` – Base class for REST API function groups
- `PostRestFunctions.php`, `PagesRestFunctions.php` – Post/page management tools
- `CommentRestFunctions.php`, `UserRestFunctions.php` – Comment/user tools
- `MediaRestFunctions.php` – Media library tools
- `TaxonomyRestFunctions.php` – Taxonomy/term tools
- `SettingsRestFunctions.php` – WordPress settings tools
- `PluginRestFunctions.php` – Plugin management tools

#### Admin Integration (`src/Convo/Gpt/Admin/`)
- `SettingsProcessor.php`, `SettingsView.php`, `SettingsViewModel.php` – Settings page (minimal, mostly for MCP config)
- `McpConvoworksManager.php` – Helper for accessing Convoworks services from MCP context

### Service Templates

The plugin includes several ready-to-use service templates in `src/Convo/Gpt/Pckg/`:

1. **gpt-example-chat.template.json** – Simple RAG chat for small business sites
2. **gpt-site-admin.template.json** – AI site administrator with WordPress API access
3. **deep-research-assistant.template.json** – Recursive web research and report generation
4. **mcp-server-example.template.json** – MCP server with multiple example patterns
5. **mcp-server-project.template.json** – Basic MCP server project starter

---

## Architecture & Integration

### How Convoworks GPT Extends Convoworks WP

Convoworks GPT is **not standalone** – it requires Convoworks WP to be installed and activated.

Integration points:

1. **Plugin Registration** (`GptPlugin::register()`)
   - Checks for Convoworks WP presence via `defined('CONVO_WP_VERSION')`
   - Registers the GPT package with Convoworks framework

2. **Package Registration**
   - `GptPackageDefinition` is registered with Convoworks' `PackageProviderFactory`
   - All GPT components become available in the Convoworks editor

3. **Platform Registration** (MCP Server)
   - `McpServerPlatform` is registered as a new platform type
   - Appears in service configuration alongside Alexa, Viber, Convo Chat, etc.

4. **REST API Integration**
   - MCP endpoints are exposed through Convoworks' public REST API
   - WordPress REST API tools are registered via filters (`convo_mcp_register_wp_posts`, etc.)

### Configuration Constants

Defined in `convoworks-gpt.php`, can be overridden in `wp-config.php`:

```php
// MCP session storage path (filesystem-based currently)
CONVO_GPT_MCP_SESSION_STORAGE_PATH

// MCP session timeout in seconds (default: 30 days)
CONVO_GPT_MCP_SESSION_TIMEOUT

// Background poll interval in microseconds (default: 300ms)
CONVO_GPT_MCP_LISTEN_USLEEP

// Ping interval in seconds (default: 10s)
CONVO_GPT_MCP_PING_INTERVAL
```

---

## Development Workflow

### Making Changes

1. **PHP component changes**
   - Edit files in `src/Convo/Gpt/`
   - No build step needed for local development
   - Just refresh WordPress admin to see changes

2. **Help file updates**
   - Edit Markdown files in `src/Convo/Gpt/Pckg/Help/`
   - Changes appear immediately in component help sidebar

3. **Service template updates**
   - Edit `*.template.json` files in `src/Convo/Gpt/Pckg/`
   - Templates are re-read when creating new services

4. **Version bumps**
   - Update `version` in `package.json`
   - Run `npm run sync-version` to propagate to `composer.json` and `convoworks-gpt.php`
   - Update `CHANGELOG.md`

### Building for Distribution

```bash
npm run build      # Creates build/convoworks-gpt-vX.Y.Z.zip
```

This will:
1. Read version from `package.json`
2. Copy required files to `dist/temp/convoworks-gpt-vX.Y.Z/`
3. Run `composer install --no-dev` in the temp directory
4. Create a zip archive in `build/`
5. Clean up temp directory

The resulting zip is ready to upload to WordPress.

---

## Testing

### Manual Testing

1. Install Convoworks WP in a local WordPress site
2. Clone/symlink this plugin to `wp-content/plugins/convoworks-gpt/`
3. Run `composer install` in the plugin directory
4. Activate both plugins in WordPress admin
5. Create a new Convoworks service using one of the GPT templates
6. Test in the Convoworks Test view

### PHPUnit Tests

Basic tests are in `tests/`:
- `ProcessJsonWithConstantsTest.php` – JSON processing with constants
- `SummarizeMessagesTest.php` – Message summarization logic
- `TruncateToSizeTest.php` – Token-based truncation

Run with:

```bash
composer install --dev
vendor/bin/phpunit
```

---

## Common Tasks

### Adding a New GPT Component

1. Create the component class in `src/Convo/Gpt/Pckg/`
2. Add component definition in `GptPackageDefinition::_initDefintions()`
3. Create help file in `src/Convo/Gpt/Pckg/Help/` (Markdown preferred)
4. Test in Convoworks editor

### Adding WordPress REST API Tools

1. Create new class extending `AbstractRestFunctions` in `src/Convo/Gpt/Tools/`
2. Define tool definitions in `getToolDefinitions()` method
3. Implement `execute()` method to handle tool calls
4. Register filter in `GptPlugin::register()` for activation

### Updating Service Templates

1. Edit the `*.template.json` file in `src/Convo/Gpt/Pckg/`
2. Templates are JSON service definitions – edit carefully
3. Test by creating a new service from the updated template

---

## Troubleshooting

### "Convoworks WP not found" error

- Ensure Convoworks WP is installed and activated first
- Check that `CONVO_WP_VERSION` constant is defined (should be set by Convoworks WP)

### MCP server not responding

- Check `CONVO_GPT_MCP_SESSION_STORAGE_PATH` is writable
- Increase `CONVO_GPT_MCP_SESSION_TIMEOUT` if sessions expire too quickly
- Check PHP error logs for session storage issues

### OpenAI API errors

- Verify `GPT_API_KEY` is set in service variables
- Check `Base URL` if using alternative API endpoints
- Monitor context length – use message limiters to prevent token overflow

### Build issues

- Run `npm install` to ensure packaging dependencies are present
- Check that `composer.json` is valid before building
- Ensure version sync has run (`npm run sync-version`)

---

## Resources

- **GitHub Repository**: https://github.com/zef-dev/convoworks-gpt
- **Convoworks WP**: https://wordpress.org/plugins/convoworks-wp/
- **Convoworks Website**: https://convoworks.com
- **OpenAI API Docs**: https://platform.openai.com/docs/api-reference
- **MCP Specification**: https://spec.modelcontextprotocol.io/

---

For more details on the Convoworks framework itself, see the [Convoworks WP documentation](https://github.com/zef-dev/convoworks-wp).

