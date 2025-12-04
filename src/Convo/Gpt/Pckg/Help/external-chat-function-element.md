### External Chat Function

Function definition variant that delegates execution to an external callable (PHP function or closure) instead of a Convoworks workflow. This is useful for integrating existing PHP code or libraries directly into GPT function calling.

### When to use

Use **External Chat Function** when you need to:

- Integrate existing PHP functions or closures as GPT tools
- Avoid building workflow elements for simple function logic
- Reuse code from external libraries or packages
- Implement functions programmatically without visual workflows
- Provide functions dynamically from PHP code

For most use cases, prefer **Chat Function** which provides better visual workflow integration and debugging.

### Properties

#### Function Name (name)

Unique identifier for the function. This is how GPT will reference the function.

Example: `calculate_shipping_cost`

#### Description

Explanation of what the function does, when to use it, and what it returns. GPT uses this to decide when to call the function.

Example:

```text
Calculates shipping cost based on destination country and package weight. Returns the shipping cost in USD. Use this when the user asks about shipping prices or delivery costs.
```

#### Function Parameters (parameters)

Expression that evaluates to a JSON Schema object defining the function's parameters.

Example:

```text
${[
  "country": {"type": "string", "description": "Destination country code (e.g., US, UK, CA)"},
  "weight": {"type": "number", "description": "Package weight in kilograms"}
]}
```

Unlike **Chat Function** which uses a params editor, this expects a complete object expression.

#### Defaults

Expression that evaluates to an associative array of default parameter values.

Example: `${{"weight": 1.0}}`

#### Required

Expression that evaluates to an array of required parameter names.

Example: `${["country"]}`

#### Callable (execute)

Expression that evaluates to a callable (PHP function, closure, or method). This callable receives a single parameter – an associative array of function arguments – and must return a string or JSON-encodable value.

**Callable signature**:

```php
function(array $data): string|array|object
```

Example values:

- `${my_custom_function}` – Reference to a PHP function
- `${function($data) { return calculate_cost($data['weight'], $data['country']); }}` – Inline closure
- `${[my_object, 'method_name']}` – Object method reference

### Runtime behavior

When GPT calls this function:

1. The function definition is matched by name
2. Arguments from GPT are merged with defaults
3. The callable is invoked with the merged arguments
4. The callable's return value is converted to a string (JSON-encoded if not already a string)
5. The result is returned to GPT as a tool message

No workflow elements are executed – all logic is in the callable.

### Example

**Simple calculation function**:

**External Chat Function**:

- **Function Name**: `calculate_tax`
- **Description**: `Calculates sales tax for a given amount and US state. Returns the tax amount in dollars. Use this when the user asks about tax or final prices.`
- **Parameters**:
  ```text
  ${{"amount": {"type": "number", "description": "Base price in USD"}, "state": {"type": "string", "description": "Two-letter US state code (e.g., CA, NY)"}}}
  ```
- **Required**: `${["amount", "state"]}`
- **Defaults**: `${{}}`
- **Callable**:
  ```text
  ${function($data) {
    $rates = ["CA" => 0.0725, "NY" => 0.0875, "TX" => 0.0625];
    $rate = $rates[$data['state']] ?? 0.06;
    $tax = $data['amount'] * $rate;
    return json_encode(["tax": number_format($tax, 2), "rate": $rate * 100 . "%"]);
  }}
  ```

**Usage**: When GPT calls `calculate_tax(amount=100, state="CA")`, the function returns `{"tax": "7.25", "rate": "7.25%"}`.

### Example: Integration with external library

**Using a third-party API client**:

**In service PHP code or plugin**:

```php
function fetch_weather_data($data) {
  $api_key = get_option('weather_api_key');
  $client = new WeatherApiClient($api_key);
  $weather = $client->getCurrentWeather($data['city']);
  
  return json_encode([
    'temperature' => $weather->getTemperature(),
    'conditions' => $weather->getConditions(),
    'humidity' => $weather->getHumidity()
  ]);
}
```

**External Chat Function**:

- **Function Name**: `get_weather`
- **Description**: `Fetches current weather data for a specified city. Use this when the user asks about weather or temperature.`
- **Parameters**: `${{"city": {"type": "string", "description": "City name"}}}`
- **Required**: `${["city"]}`
- **Callable**: `${fetch_weather_data}`

### Example: Database query

**Query custom database table**:

**External Chat Function**:

- **Function Name**: `search_inventory`
- **Description**: `Searches product inventory by SKU or name. Returns stock levels and locations.`
- **Parameters**:
  ```text
  ${{"query": {"type": "string", "description": "SKU or product name to search"}}}
  ```
- **Required**: `${["query"]}`
- **Callable**:
  ```text
  ${function($data) {
    global $wpdb;
    $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}inventory WHERE sku LIKE %s OR name LIKE %s", '%' . $data['query'] . '%', '%' . $data['query'] . '%');
    $results = $wpdb->get_results($query, ARRAY_A);
    return json_encode(["products": $results, "count": count($results)]);
  }}
  ```

### Comparison: External vs. Chat Function

| Feature | External Chat Function | Chat Function |
|---------|------------------------|---------------|
| **Implementation** | PHP callable | Convoworks workflow |
| **Visual editor** | No | Yes (OK Flow) |
| **Debugging** | PHP debugging tools | Workflow debugging, logs |
| **Complexity** | Simple, direct | More flexible, composable |
| **Best for** | Existing PHP code, simple logic | Complex workflows, visual users |

### Tips

- Use **External Chat Function** for integrating existing PHP functions without refactoring
- For new functionality, prefer **Chat Function** – it's easier to debug and maintain
- Always return JSON-encoded strings for complex data: `return json_encode($result);`
- Return simple strings for text responses: `return "The tax is $7.25";`
- Handle errors gracefully in the callable: `return json_encode(["error": "Invalid state code"]);`
- Test callables independently before integrating them
- Closures defined inline have access to service scope variables
- Use function references for better performance and reusability
- Avoid heavy computations in callables – they block request processing
- Log errors in the callable for debugging: `error_log("Function failed: " . $e->getMessage());`
- Merge defaults with GPT arguments using `array_merge` if needed (done automatically by the element)
- Validate input data in the callable – GPT may provide unexpected values
- Keep callable functions pure (no side effects) when possible
- For WordPress operations, ensure proper permissions and nonces if needed

