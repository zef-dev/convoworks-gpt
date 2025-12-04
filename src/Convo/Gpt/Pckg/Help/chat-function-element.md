### Chat Function

Defines a custom function that can be called by GPT through the Chat Completion API. Functions allow GPT to request actions, fetch data, or perform operations based on the conversation context. This is the primary way to implement tool calling in Convoworks.

### When to use

Use **Chat Function** when you need to:

- Let GPT access external data or APIs
- Perform actions based on GPT's decisions (create posts, send emails, update settings)
- Implement RAG (Retrieval-Augmented Generation) patterns
- Provide GPT with dynamic information (current weather, database queries, user data)
- Build AI agents that can interact with your system

This element must be used inside the **Messages** (message_provider) flow of **GPT Chat Completion API v2**.

### Properties

#### Function Name (name)

Unique identifier for the function. This is how GPT will reference the function when it decides to call it.

**Naming guidelines**:

- Use snake_case (e.g., `get_user_posts`, `create_draft_page`)
- Be descriptive but concise
- Avoid generic names like `execute` or `run`

Example: `search_products`

#### Description

Clear explanation of what the function does, when to use it, and what it returns. This is **critical** – GPT uses this to decide when to call the function.

**Writing good descriptions**:

- Be specific about the function's purpose
- Explain when it should be used
- Describe the return value format
- Include any important constraints or limitations

Example:

```text
Searches the product catalog by keywords. Returns a list of matching products with name, price, description, and availability. Use this when the user asks about products, prices, or availability.
```

#### Function Parameters (parameters)

JSON Schema definitions for each parameter the function accepts. Each parameter should have:

- **type** – Data type (string, number, integer, boolean, array, object)
- **description** – What the parameter is used for
- **enum** (optional) – Allowed values for the parameter

Example:

```text
query:
  type: string
  description: Search keywords for finding products

category:
  type: string
  description: Product category to filter by
  enum: ["electronics", "clothing", "books"]

max_results:
  type: integer
  description: Maximum number of results to return (1-50)
```

#### Defaults

Associative array of default values for function parameters. Parameters with defaults are optional when GPT calls the function.

Example:

```text
${{
  "max_results": 10,
  "category": "all"
}}
```

#### Required

Array of parameter names that are mandatory. GPT must provide these when calling the function.

Example: `${["query"]}`

#### Request Data Variable (request_data)

Variable name that will hold the function arguments when the function is executed. Default is `data`.

Inside the **OK Flow**, access arguments like:

- `${data.query}`
- `${data.category}`
- `${data.max_results}`

#### Function Result (result_data)

Expression that evaluates to the function's return value. This is what gets sent back to GPT after the function executes.

The result can be:

- A string: `${"Product found: " . product.name}`
- An object/array: `${search_results}`
- JSON-encoded data: `${json_encode(products)}`
- A callable result from the OK Flow

Example: `${function_result}`

Then in the OK Flow, set `function_result` with the actual data.

#### OK Flow

Workflow executed when GPT calls this function. This is where you implement the function's logic:

- Fetch data from databases or APIs
- Perform calculations or transformations
- Create/update/delete content
- Log the function call
- Set the result variable

### Runtime behavior

When the element is used:

1. During the **Messages flow** (message_provider), the function is registered with Chat Completion v2
2. The function definition is sent to GPT as part of the API request
3. If GPT decides to call this function:
   - Chat Completion v2 extracts the function name and arguments
   - The function is located and executed
   - Arguments are merged with defaults and stored in the request data variable
   - The **OK Flow** executes
   - The result expression is evaluated and returned to GPT
   - The result is added as a `tool` message to the conversation
   - A new API call is made with the function result included

### Function definition format

The function is registered with GPT using this structure:

```json
{
  "name": "search_products",
  "description": "Searches the product catalog...",
  "parameters": {
    "type": "object",
    "properties": {
      "query": {"type": "string", "description": "..."},
      "category": {"type": "string", "description": "...", "enum": ["..."]}
    },
    "required": ["query"]
  }
}
```

### Example

**Product search function**:

**Chat Function**:

- **Function Name**: `search_products`
- **Description**: `Searches the product catalog by keywords. Returns a list of products with name, price, and availability. Use this when the user asks about products or wants to find something to buy.`
- **Parameters**:
  - `query`:
    - type: `string`
    - description: `Keywords to search for in product names and descriptions`
  - `max_results`:
    - type: `integer`
    - description: `Maximum number of results to return (default: 5)`
- **Defaults**: `${{"max_results": 5}}`
- **Required**: `${["query"]}`
- **Request Data Variable**: `data`
- **Function Result**: `${json_encode(results)}`

**OK Flow**:

1. **WordPress Query Loop** element:
   - Post type: `product`
   - Query args: `${{s: data.query, posts_per_page: data.max_results}}`
   - Item name: `product`
   - **OK Flow** (inside loop):
     - **Set Param** (request scope):
       - Name: `results`
       - Value: `${array_merge(results, [{"name": product.post_title, "price": get_post_meta(product.ID, "_price", true), "id": product.ID}])}`

2. **If** element: `${empty(results)}`
   - **True flow**: **Set Param**: `results = ${[{"error": "No products found matching: " . data.query}]}`

**Usage**: GPT will call this function when users ask "Show me laptops" or "Find books under $20".

### Example: Create WordPress post

**Chat Function**:

- **Function Name**: `create_draft_post`
- **Description**: `Creates a new draft post in WordPress. Use this when the user asks to create, write, or draft a new blog post or article.`
- **Parameters**:
  - `title`: string, "Post title"
  - `content`: string, "Post content/body"
  - `category`: string, "Category name (optional)"
- **Required**: `${["title", "content"]}`
- **Request Data Variable**: `data`
- **Function Result**: `${result_message}`

**OK Flow**:

1. **PHP Delegate**:
   - Code:
     ```php
     $post_id = wp_insert_post([
       'post_title' => $data['title'],
       'post_content' => $data['content'],
       'post_status' => 'draft',
       'post_type' => 'post'
     ]);
     
     if ($data['category']) {
       wp_set_post_categories($post_id, [get_cat_ID($data['category'])]);
     }
     
     return "Draft post created with ID: $post_id. Title: {$data['title']}";
     ```
   - Result variable: `result_message`

### Tips

- Write **clear, specific descriptions** – this is the most important part of function definitions
- Include examples in the description when helpful: "Example: search_products(query='laptop', max_results=5)"
- Use enums for parameters with limited valid values
- Set sensible defaults for optional parameters
- Keep function names under 64 characters (OpenAI limit)
- Return structured JSON for complex data: `${json_encode({"products": products, "total": count(products)})}`
- Return error messages as JSON: `${json_encode({"error": "Invalid category"})}`
- Log function calls for debugging: Add **Log Message** at the start of OK Flow
- Test function calling thoroughly – GPT may call functions in unexpected ways
- Handle missing or invalid arguments gracefully in the OK Flow
- For functions that modify data, include confirmation in the description: "Always confirm with the user before creating the post."
- Limit result size – Chat Completion v2 has `max_func_result_tokens` limit
- Don't expose sensitive operations without proper authorization checks
- Use `AbstractScopedFunction` inheritance for better parameter scoping (automatic in this element)

