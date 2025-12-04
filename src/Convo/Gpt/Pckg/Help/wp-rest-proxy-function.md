### WP REST Proxy Function

Exposes a WordPress REST API endpoint (`/wp/v2/...`) as a GPT function, allowing AI to interact with WordPress content directly. This element automatically handles parameter mapping, authentication, and optional cursor-based pagination.

### When to use

Use **WP REST Proxy Function** when you need to:

- Let GPT access WordPress content (posts, pages, users, comments, etc.)
- Enable AI to create, read, update, or delete WordPress content
- Build AI site administrators or content managers
- Implement RAG with WordPress content
- Provide GPT with dynamic WordPress data

This is the easiest way to give GPT access to WordPress without building custom REST handlers.

### Properties

#### Function Name (name)

The name GPT will use to call this function.

Example: `list_posts`, `create_page`, `update_user`

#### Description

Clear explanation of what the function does and when to use it. This is critical for GPT to decide when to call the function.

Example:

```text
Lists published WordPress posts with filters. Returns post title, excerpt, date, and author. Use this when the user asks about blog posts, articles, or recent content.
```

#### Required Parameters (required)

Expression that evaluates to an array of required parameter names.

Example: `${["postId"]}` or `${[]}`for functions with no required parameters

#### Default Values (defaults)

Expression that evaluates to an object with default parameter values. These are merged with GPT-provided arguments.

Example:

```text
${{"per_page": 10, "status": "publish"}}
```

#### HTTP Method (method)

HTTP verb to use for the REST API call: GET, POST, PUT, PATCH, or DELETE.

- **GET** – Read data (list, retrieve)
- **POST** – Create new content
- **PUT/PATCH** – Update existing content
- **DELETE** – Remove content

Default: `GET`

#### REST Endpoint (endpoint)

Path under `/wp/v2/`, for example `posts`, `pages`, `users/123`, or `comments`.

**Do not include `/wp/v2/` prefix** – it's added automatically.

Examples:

- `posts` → `/wp/v2/posts`
- `users/${userId}` → `/wp/v2/users/123` (dynamic)
- `pages` → `/wp/v2/pages`

#### Enable Pagination (pagination)

When enabled, wraps REST responses in a pagination structure with `results` and `nextCursor` fields for cursor-based paging.

Default: `false`

**Pagination response structure**:

```json
{
  "results": [ /* array of items */ ],
  "nextCursor": "base64-encoded-cursor"
}
```

GPT can use `nextCursor` in subsequent calls to fetch the next page.

#### Function Parameters (parameters)

JSON Schema property definitions for each parameter. Define type, description, enum, etc.

Example:

```text
status:
  type: string
  description: Post status filter
  enum: ["publish", "draft", "private"]

per_page:
  type: integer
  description: Number of posts per page (1-100)

search:
  type: string
  description: Search keywords in post title or content
```

These definitions help GPT understand what parameters are available and how to use them.

### Runtime behavior

When GPT calls this function:

1. Arguments are merged with defaults
2. If pagination is enabled and a `cursor` argument is provided, it's decoded and merged
3. A `WP_REST_Request` is created for the specified endpoint and method
4. Parameters are set (query params for GET, body params for POST/PUT/PATCH/DELETE)
5. The REST request is executed via `rest_do_request()`
6. If pagination is enabled, the response is wrapped with `results` and `nextCursor`
7. The response is JSON-encoded and returned to GPT

**Authentication**: The REST request executes with the current user's permissions (from the service session).

### Example

**List published posts**:

**WP REST Proxy Function**:

- **Function Name**: `list_posts`
- **Description**: `Lists published blog posts with optional filtering and search. Returns post title, excerpt, date, author, and link. Use this when the user asks about posts, articles, or blog content.`
- **Required**: `${[]}`
- **Defaults**: `${{"per_page": 10, "status": "publish", "orderby": "date", "order": "desc"}}`
- **HTTP Method**: `GET`
- **REST Endpoint**: `posts`
- **Enable Pagination**: `true`
- **Parameters**:
  ```text
  per_page:
    type: integer
    description: Number of posts to return (1-100)
  
  search:
    type: string
    description: Search keywords in title or content
  
  categories:
    type: array
    items:
      type: integer
    description: Filter by category IDs
  ```

**Usage**: GPT can call `list_posts(search="wordpress", per_page=5)` to find posts about WordPress.

### Example: Create a draft page

**WP REST Proxy Function**:

- **Function Name**: `create_page`
- **Description**: `Creates a new draft page in WordPress. Use this when the user asks to create a new page or add content to the site. Always confirm with the user before creating.`
- **Required**: `${["title", "content"]}`
- **Defaults**: `${{"status": "draft"}}`
- **HTTP Method**: `POST`
- **REST Endpoint**: `pages`
- **Enable Pagination**: `false`
- **Parameters**:
  ```text
  title:
    type: string
    description: Page title
  
  content:
    type: string
    description: Page content (HTML allowed)
  
  status:
    type: string
    description: Page status
    enum: ["draft", "publish", "private"]
  ```

**Usage**: GPT calls `create_page(title="About Us", content="<p>Welcome to our site!</p>", status="draft")`.

### Example: Update a specific post

**WP REST Proxy Function**:

- **Function Name**: `update_post`
- **Description**: `Updates an existing WordPress post. Use this to edit post content, title, or status. Requires post ID.`
- **Required**: `${["id"]}`
- **Defaults**: `${{}}`
- **HTTP Method**: `PUT`
- **REST Endpoint**: `posts/${id}`
- **Parameters**:
  ```text
  id:
    type: integer
    description: Post ID to update
  
  title:
    type: string
    description: New post title (optional)
  
  content:
    type: string
    description: New post content (optional)
  
  status:
    type: string
    description: New post status (optional)
    enum: ["publish", "draft", "pending", "private"]
  ```

### Example: With pagination

**Handling large result sets**:

When **Enable Pagination** is true and multiple pages exist:

1. First call: `list_posts(per_page=10)` returns:
   ```json
   {
     "results": [ /* 10 posts */ ],
     "nextCursor": "eyJwYWdlIjoyLCJwZXJfcGFnZSI6MTB9"
   }
   ```

2. GPT can fetch more: `list_posts(cursor="eyJwYWdlIjoyLCJwZXJfcGFnZSI6MTB9")` returns the next 10 posts

3. When no more pages exist, `nextCursor` is omitted

### Supported REST endpoints

Common `/wp/v2/` endpoints you can proxy:

- `posts` – Blog posts
- `pages` – Pages
- `media` – Media library items
- `comments` – Comments
- `categories` – Categories
- `tags` – Tags
- `users` – Users
- `users/{id}` – Specific user
- `posts/{id}` – Specific post

For custom post types registered with REST support: `{custom_post_type_slug}`

### Tips

- **Always confirm destructive actions** in the function description: "Confirm with the user before deleting"
- Use `status: publish` in defaults for read operations to avoid exposing drafts
- Set reasonable `per_page` defaults (10-20) to avoid large responses
- Enable pagination for list endpoints that might return many items
- For update/delete operations, require the item ID as a parameter
- Test REST endpoints manually first using WordPress REST API console or Postman
- Check REST API permissions – some endpoints require authentication or specific caps
- For custom endpoints, ensure they're registered with REST API (`show_in_rest: true`)
- Use enums for status, orderby, and other fields with limited valid values
- Include helpful descriptions for each parameter – GPT uses these to decide what values to pass
- Log function calls for debugging and monitoring: Add **Log Message** elements if needed (not available in this element, but can wrap it)
- For security, validate that the current user has appropriate permissions before calling sensitive endpoints
- Return clear error messages when REST requests fail (automatically handled by the element)
- Don't expose admin-only operations to untrusted users

