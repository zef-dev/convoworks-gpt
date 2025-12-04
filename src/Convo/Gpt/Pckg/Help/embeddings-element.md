### GPT Embeddings API

Wrapper for the OpenAI Embeddings API, enabling you to generate vector representations (embeddings) for text input. Embeddings are useful for semantic search, clustering, recommendations, and similarity comparisons.

### When to use

Use **GPT Embeddings API** when you need to:

- Create vector embeddings for semantic search in your content
- Build recommendation systems based on text similarity
- Cluster or categorize text content automatically
- Compare semantic similarity between documents
- Store vector representations for RAG (Retrieval-Augmented Generation) systems

### Properties

#### Input

The text input for which you want to create an embedding. Can be a string or an array of strings (depending on the model).

**Preprocessing recommendation**: Clean up raw text using the `tokenize_string()` function to remove stop words and improve embedding quality:

```text
${tokenize_string(your_raw_text)}
```

Example values:

- `${page_content}` – Content from a WordPress page
- `${tokenize_string(post.content)}` – Tokenized post content
- `The quick brown fox jumps over the lazy dog` – Literal string

#### API key

Your OpenAI API key. Store this in a service variable for security.

Example: `${GPT_API_KEY}`

#### Base URL

Optional. Base URL for the API endpoint. If left blank, defaults to `https://api.openai.com/v1`.

Use this to point to custom endpoints or OpenAI-compatible APIs.

#### API options

Configuration options for the Embeddings API:

- **model** – The embedding model to use (e.g., `text-embedding-ada-002`, `text-embedding-3-small`, `text-embedding-3-large`)

Default value:

```text
model: text-embedding-ada-002
```

For all available options and models, see the [OpenAI Embeddings API Reference](https://platform.openai.com/docs/api-reference/embeddings).

#### Result Variable Name

The name of the variable that will store the complete embeddings API response. Default is `status`.

#### OK flow

Sub-flow executed after the API call completes successfully. The result variable will be available for use in this flow.

### Runtime behavior

When the element executes:

1. The input text is evaluated
2. An API call is made to OpenAI Embeddings endpoint with the input and options
3. The complete response is stored in the result variable (request scope)
4. The OK flow is executed with the result variable available

### Accessing the embedding

Inside the OK flow, access the embedding vector like this:

```text
${status.data[0].embedding}
```

The result variable contains:

- `${status.data}` – Array of embedding objects
- `${status.data[0].embedding}` – The actual embedding vector (array of floats)
- `${status.data[0].index}` – Index of the embedding in the input array
- `${status.usage}` – Token usage statistics

The embedding vector is a numerical array (typically 1536 dimensions for `text-embedding-ada-002`, or configurable dimensions for newer models).

### Example

**Building a semantic search index**:

**Configuration**:

- **Input**: `${tokenize_string(post.post_content)}`
- **API key**: `${GPT_API_KEY}`
- **API options**:
  - model: `text-embedding-3-small`
- **Result Variable Name**: `embedding_result`

**In the OK flow**, store the embedding:

1. Add **HTTP Request** to store in your vector database:
   - URL: `https://your-vector-db.com/api/embeddings`
   - Method: `POST`
   - Body: `${{"post_id": post.ID, "embedding": embedding_result.data[0].embedding}}`

Or store in WordPress post meta:

1. Add **Set Meta** element:
   - Post ID: `${post.ID}`
   - Meta key: `_embedding_vector`
   - Meta value: `${json_encode(embedding_result.data[0].embedding)}`

### Example: Semantic similarity search

**Find similar posts**:

1. Get embedding for search query using **GPT Embeddings API**
2. In OK flow, compare with stored embeddings using cosine similarity
3. Rank posts by similarity score

### Tips

- Use `tokenize_string()` to preprocess text – removes stop words and improves embedding quality
- For long documents, split into chunks and embed each chunk separately (embeddings have token limits)
- Use `text-embedding-3-small` for cost-effective embeddings with good quality
- Use `text-embedding-3-large` for highest quality when accuracy is critical
- Store embeddings in a vector database (Pinecone, Weaviate, pgvector) for production semantic search
- For WordPress-based vector search, store embeddings in post meta or a custom table
- Calculate cosine similarity between embeddings to measure semantic similarity (dot product if vectors are normalized)
- Batch multiple inputs in a single API call when possible to reduce costs
- Monitor token usage through `${status.usage.total_tokens}` – embeddings are charged per token
- Cache embeddings – they don't change unless the input text changes

