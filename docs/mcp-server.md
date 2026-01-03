# OpenGRC MCP Server

OpenGRC provides a Model Context Protocol (MCP) server over HTTP that allows AI clients (like Claude Code) to interact with OpenGRC programmatically.

## Features

The MCP server exposes the following tools:

### Policy Management
- **CreatePolicy**: Create new security/compliance policies with structured fields
- **ListPolicies**: List all policies with filtering and pagination
- **GetPolicy**: Get detailed information about a specific policy

### Standards & Controls
- **ListStandards**: List available compliance frameworks (NIST, ISO, SOC2, etc.)
- **ListControls**: List security controls, optionally filtered by standard

### Reference Data
- **GetTaxonomyValues**: Get valid values for policy status, scope, departments

## Endpoint

```
POST /mcp/opengrc
```

The endpoint requires Sanctum authentication via Bearer token.

---

## Setup for Claude Code

### 1. Start the Development Server

```bash
php artisan serve
```

This starts the server at `http://127.0.0.1:8000`

### 2. Create an API Token

Generate a Sanctum API token for authentication:

```bash
php artisan tinker
```

```php
// Create or get a user
$user = \App\Models\User::first();

// Or create a new user if needed
// $user = \App\Models\User::create([
//     'name' => 'MCP Test User',
//     'email' => 'mcp@example.com',
//     'password' => bcrypt('password'),
// ]);

// Create an API token
$token = $user->createToken('mcp-client');
echo $token->plainTextToken;
```

Save this token - you'll need it for authentication.

### 3. Configure Claude Code

Add the following to your Claude Code MCP settings:

**For local development:**
```json
{
  "mcpServers": {
    "opengrc": {
      "type": "http",
      "url": "http://127.0.0.1:8000/mcp/opengrc",
      "headers": {
        "Authorization": "Bearer YOUR_TOKEN_HERE"
      }
    }
  }
}
```

**For remote/production:**
```json
{
  "mcpServers": {
    "opengrc": {
      "type": "http",
      "url": "https://your-opengrc-domain.com/mcp/opengrc",
      "headers": {
        "Authorization": "Bearer YOUR_TOKEN_HERE"
      }
    }
  }
}
```

Replace `YOUR_TOKEN_HERE` with the token generated in step 2.

### 4. Test the Connection

In Claude Code, try commands like:
- "List all policies in OpenGRC"
- "Show me the available compliance standards"
- "Create a Security Awareness Policy"

---

## Testing with cURL

You can test the MCP server directly with cURL:

```bash
# Initialize the connection
curl -X POST http://127.0.0.1:8000/mcp/opengrc \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "initialize", "id": 1, "params": {"protocolVersion": "2024-11-05", "capabilities": {}, "clientInfo": {"name": "test", "version": "1.0"}}}'

# List available tools
curl -X POST http://127.0.0.1:8000/mcp/opengrc \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "tools/list", "id": 2}'

# Call a tool (ListPolicies)
curl -X POST http://127.0.0.1:8000/mcp/opengrc \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc": "2.0", "method": "tools/call", "id": 3, "params": {"name": "ListPolicies", "arguments": {}}}'
```

---

## Production Deployment

For production use:

1. Update `APP_URL` in your `.env` to your production URL
2. Ensure HTTPS is enabled
3. Consider adding additional rate limiting in `app/Providers/RouteServiceProvider.php`
4. Review and restrict the `redirect_domains` in the MCP config if using OAuth

---

## Example: Creating a Policy

Here's an example of creating a policy using the MCP tools:

```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "id": 1,
  "params": {
    "name": "CreatePolicy",
    "arguments": {
      "name": "Security Awareness Policy",
      "purpose": "<p>The purpose of this Security Awareness Policy is to establish guidelines for employee security training and awareness programs.</p>",
      "policy_scope": "<p>This policy applies to all employees, contractors, and third-party users who have access to organizational systems and data.</p>",
      "body": "<h2>1.0 Training Requirements</h2><p>All employees must complete security awareness training within 30 days of hire and annually thereafter.</p><h2>2.0 Topics Covered</h2><ul><li>Password security</li><li>Phishing awareness</li><li>Data classification</li><li>Incident reporting</li></ul>",
      "effective_date": "2025-01-01"
    }
  }
}
```

The policy will be created with:
- Auto-generated code (e.g., `POL-001`)
- Status set to "Draft"
- Structured HTML content for purpose, scope, and body

---

## Tool Reference

### CreatePolicy

Creates a new policy document in OpenGRC.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| name | string | Yes | The name/title of the policy |
| code | string | No | Custom policy code (auto-generated if not provided) |
| purpose | string | No | Policy purpose (HTML supported) |
| policy_scope | string | No | Policy scope/applicability (HTML supported) |
| body | string | No | Main policy content (HTML supported) |
| effective_date | string | No | Date policy becomes effective (YYYY-MM-DD) |
| control_ids | array | No | Array of control IDs to link to this policy |

### ListPolicies

Lists all policies with filtering and pagination.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |
| status | string | No | Filter by status name |
| search | string | No | Search by code or name |

### GetPolicy

Retrieves a specific policy by code or ID.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| code | string | No* | Policy code (e.g., "POL-001") |
| id | integer | No* | Policy database ID |

*One of `code` or `id` is required.

### ListStandards

Lists available compliance standards/frameworks.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| search | string | No | Search by code, name, or authority |

### ListControls

Lists security controls with optional filtering.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| standard_id | integer | No | Filter by standard ID |
| search | string | No | Search by code, title, or description |
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 20, max: 100) |

### GetTaxonomyValues

Gets available taxonomy values for policies.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | No | Taxonomy type (e.g., "policy-status", "policy-scope", "department") |

---

## Troubleshooting

### 401 Unauthorized
- Verify your Sanctum token is valid
- Ensure the `Authorization` header is correctly formatted as `Bearer YOUR_TOKEN`

### 404 Not Found
- Verify the server is running and accessible
- Check that routes are registered: `php artisan route:list --path=mcp`

### 429 Too Many Requests
- The MCP endpoint is rate-limited to 120 requests per minute
- Wait a moment and try again

### CORS Issues (Browser-based clients)
- Configure CORS in `config/cors.php` to allow your client domain
