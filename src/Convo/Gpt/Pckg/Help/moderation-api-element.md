### GPT Moderation API

Validates input content using the OpenAI Moderation API. This element helps ensure that user-generated content or AI responses comply with content policies by detecting potentially harmful, offensive, or policy-violating content.

### When to use

Use **GPT Moderation API** when you need to:

- Screen user input before processing it with GPT
- Validate AI-generated content before displaying it to users
- Implement content filtering and safety controls
- Detect harmful, offensive, or inappropriate content
- Comply with content moderation policies and regulations
- Flag content for human review based on moderation scores

### Properties

#### Input

The text content to be moderated. This can be user input, AI-generated content, or any text you want to validate.

Example values:

- `${request.text}` – User's message
- `${gpt_response.choices[0].message.content}` – AI response
- `Any text string you want to check`

#### API key

Your OpenAI API key. Store this in a service variable for security.

Example: `${GPT_API_KEY}`

#### Base URL

Optional. Base URL for the API endpoint. If left blank, defaults to `https://api.openai.com/v1`.

Use this to point to custom endpoints if needed.

#### API options

Configuration options for the Moderation API:

- **model** – The moderation model to use (e.g., `text-moderation-latest`, `text-moderation-stable`)

Default value:

```text
model: text-moderation-latest
```

The `text-moderation-latest` model is automatically updated to use the most recent moderation model, while `text-moderation-stable` provides a stable version that only updates with advance notice.

For details, see the [OpenAI Moderation API Reference](https://platform.openai.com/docs/api-reference/moderations).

#### Result Variable Name

The name of the variable that will store the moderation API response. Default is `status`.

#### OK flow

Sub-flow executed after the moderation check completes. The result variable will be available for use in this flow.

Use this flow to:

- Check moderation flags and take appropriate action
- Block content that violates policies
- Log flagged content for review
- Provide user feedback about content violations

### Runtime behavior

When the element executes:

1. The input text is evaluated
2. An API call is made to OpenAI Moderation endpoint
3. The moderation response is stored in the result variable (request scope)
4. The OK flow is executed with the result variable available

### Response structure

The result variable contains moderation analysis with:

- `${status.results[0].flagged}` – Boolean indicating if content was flagged
- `${status.results[0].categories}` – Object with category flags (true/false for each category)
- `${status.results[0].category_scores}` – Object with confidence scores (0.0-1.0) for each category

**Moderation categories**:

- `hate` – Content promoting hate based on identity
- `hate/threatening` – Hateful content with violence or harm
- `harassment` – Harassing, bullying, or abusive content
- `harassment/threatening` – Harassment with threats of harm
- `self-harm` – Content promoting self-harm
- `self-harm/intent` – Intent to engage in self-harm
- `self-harm/instructions` – Instructions for self-harm
- `sexual` – Sexual content
- `sexual/minors` – Sexual content involving minors
- `violence` – Content promoting violence
- `violence/graphic` – Graphic violent content

### Example

**Moderate user input before sending to GPT**:

**Configuration**:

- **Input**: `${request.text}`
- **API key**: `${GPT_API_KEY}`
- **Result Variable Name**: `moderation`

**In the OK flow**, add conditional logic:

1. Add **If** element with condition: `${moderation.results[0].flagged}`
   - **True flow**: Add **Text Response**: `I'm sorry, I can't process that request. Please rephrase your message to comply with our content policy.`
   - Add **Stop** element to prevent further processing
   - **False flow**: Continue with normal GPT processing

### Example: Log flagged content

**In the OK flow**, add logging for flagged content:

1. Add **If** element: `${moderation.results[0].flagged}`
   - **True flow**:
     - Add **Log Message**: `Flagged content detected: ${request.text}`
     - Add **Set Param** (session scope): 
       - Name: `flagged_messages`
       - Value: `${array_merge(flagged_messages, [{"text": request.text, "categories": moderation.results[0].categories, "timestamp": date("Y-m-d H:i:s")}])}`

### Example: Category-specific handling

**Handle different categories differently**:

1. Add **If** element: `${moderation.results[0].categories.harassment}`
   - **True flow**: Block and log as harassment
2. Add **Else If** element: `${moderation.results[0].category_scores.violence > 0.8}`
   - **True flow**: Flag for manual review (high violence score)
3. Add **Else** flow: Continue normally

### Example: Two-way moderation

**Moderate both user input and AI responses**:

**Step 1 – Moderate user input**:

- Use **GPT Moderation API** on `${request.text}`
- Block flagged input

**Step 2 – Process with GPT** (if input is clean)

**Step 3 – Moderate GPT response**:

- Use another **GPT Moderation API** on `${gpt_response.choices[0].message.content}`
- If flagged, regenerate with adjusted parameters or use a fallback response

### Tips

- Always moderate user input before processing expensive GPT API calls – prevents wasting tokens on policy-violating content
- Consider moderating AI-generated content as well – even with safety settings, GPT can occasionally produce borderline content
- Use `category_scores` for fine-grained control – you can set your own thresholds stricter than the default `flagged` boolean
- Log all flagged content with timestamps and user IDs for compliance and improvement
- Provide clear, helpful error messages to users when content is blocked
- For chatbots, implement progressive warnings – first warning, then temporary restrictions, then account restrictions for repeated violations
- Use `text-moderation-latest` in development, `text-moderation-stable` in production if you need predictable behavior
- The Moderation API is free to use (as of current OpenAI pricing) – don't hesitate to use it liberally
- Combine with application-level filtering for comprehensive content safety
- Test edge cases – the API may not catch all policy violations, and it may occasionally flag acceptable content (false positives)

