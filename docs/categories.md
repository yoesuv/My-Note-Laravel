# API Categories

Categories let an authenticated user organize notes. Category names are trimmed and unique per user case-insensitively.

> Note: categories are protected API routes. Use a Sanctum Bearer token from login or register.

## Authentication Header

```http
Authorization: Bearer 1|plain-text-sanctum-token
Accept: application/json
```

## Category Object

```json
{
  "id": 1,
  "name": "Work",
  "color": "#f39c12",
  "created_at": "2026-06-08T00:00:00.000000Z",
  "updated_at": "2026-06-08T00:00:00.000000Z"
}
```

## Validation Rules

| Field | Rules |
| --- | --- |
| `name` | required when creating, string, trimmed, minimum 1 character, maximum 100 characters, unique per user case-insensitively |
| `color` | required when creating, string, 6-digit hex color format like `#f39c12` |

For update requests, `name` and `color` are optional, but supplied fields must pass the same rules.

Colors are normalized to lowercase before storage. For example, `#F39C12` is stored as `#f39c12`.

## List Categories

### Endpoint

```http
GET /api/categories
```

### Success Response

Status code:

```txt
200 OK
```

Response body:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Ideas",
      "color": "#9b59b6",
      "created_at": "2026-06-08T00:00:00.000000Z",
      "updated_at": "2026-06-08T00:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Personal",
      "color": "#3498db",
      "created_at": "2026-06-08T00:00:00.000000Z",
      "updated_at": "2026-06-08T00:00:00.000000Z"
    }
  ]
}
```

## Create Category

### Endpoint

```http
POST /api/categories
```

### Headers

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer 1|plain-text-sanctum-token
```

### Request Body

```json
{
  "name": " Work ",
  "color": "#F39C12"
}
```

### Success Response

Status code:

```txt
201 Created
```

Response body:

```json
{
  "message": "Category created successfully.",
  "data": {
    "category": {
      "id": 1,
      "name": "Work",
      "color": "#f39c12",
      "created_at": "2026-06-08T00:00:00.000000Z",
      "updated_at": "2026-06-08T00:00:00.000000Z"
    }
  }
}
```

## Show Category

### Endpoint

```http
GET /api/categories/{category}
```

### Success Response

Status code:

```txt
200 OK
```

Response body:

```json
{
  "data": {
    "id": 1,
    "name": "Work",
    "color": "#f39c12",
    "created_at": "2026-06-08T00:00:00.000000Z",
    "updated_at": "2026-06-08T00:00:00.000000Z"
  }
}
```

## Update Category

### Endpoint

```http
PATCH /api/categories/{category}
```

`PUT` is also supported.

### Headers

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer 1|plain-text-sanctum-token
```

### Request Body

```json
{
  "name": "Personal",
  "color": "#3498db"
}
```

Partial updates are allowed:

```json
{
  "color": "#2ecc71"
}
```

### Success Response

Status code:

```txt
200 OK
```

Response body:

```json
{
  "message": "Category updated successfully.",
  "data": {
    "category": {
      "id": 1,
      "name": "Personal",
      "color": "#3498db",
      "created_at": "2026-06-08T00:00:00.000000Z",
      "updated_at": "2026-06-08T00:05:00.000000Z"
    }
  }
}
```

## Delete Category

### Endpoint

```http
DELETE /api/categories/{category}
```

### Success Response

Status code:

```txt
200 OK
```

Response body:

```json
{
  "message": "Category deleted successfully."
}
```

## Error Cases

### Unauthenticated

Status code:

```txt
401 Unauthorized
```

Example response:

```json
{
  "message": "Unauthenticated."
}
```

### Missing Name

Request:

```json
{
  "color": "#f39c12"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The name field is required.",
  "errors": {
    "name": [
      "The name field is required."
    ]
  }
}
```

### Invalid Color

Request:

```json
{
  "name": "Work",
  "color": "f39c12"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The color field format is invalid.",
  "errors": {
    "color": [
      "The color field format is invalid."
    ]
  }
}
```

### Duplicate Name for Same User

Category names are unique per user after trimming and lowercasing.

These names conflict for the same user:

```txt
Work
 work
WORK
WoRk
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The name has already been taken.",
  "errors": {
    "name": [
      "The name has already been taken."
    ]
  }
}
```

### Category Not Found

Returned when the category does not exist, the ID is malformed, or the category belongs to another user.

Status code:

```txt
404 Not Found
```

Example response:

```json
{
  "message": "No query results for model [App\\Models\\Category]."
}
```

## Example cURL

### List Categories

```bash
curl http://localhost:8000/api/categories \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|plain-text-sanctum-token"
```

### Create Category

```bash
curl -X POST http://localhost:8000/api/categories \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|plain-text-sanctum-token" \
  -d '{
    "name": "Work",
    "color": "#f39c12"
  }'
```

### Update Category

```bash
curl -X PATCH http://localhost:8000/api/categories/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|plain-text-sanctum-token" \
  -d '{
    "name": "Personal",
    "color": "#3498db"
  }'
```

### Delete Category

```bash
curl -X DELETE http://localhost:8000/api/categories/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|plain-text-sanctum-token"
```

## Seeded Categories

After running the seeder, the test user has these categories:

```txt
Personal: #3498db
Work: #2ecc71
Ideas: #9b59b6
```

Test user:

```txt
email: test@example.com
password: password
```
