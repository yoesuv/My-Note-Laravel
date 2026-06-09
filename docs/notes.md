# API Notes

Notes belong to an authenticated user and one of that user's categories. A note cannot be created, moved, listed, shown, updated, or deleted through another user's category or account.

> Note: notes are protected API routes. Use a Sanctum Bearer token from login or register.

## Authentication Header

```http
Authorization: Bearer 1|plain-text-sanctum-token
Accept: application/json
```

## Note Object

```json
{
  "id": 1,
  "category_id": 1,
  "title": "Sprint planning",
  "content": "Prepare backlog",
  "category": {
    "id": 1,
    "name": "Work",
    "color": "#f39c12",
    "created_at": "2026-06-09T00:00:00.000000Z",
    "updated_at": "2026-06-09T00:00:00.000000Z"
  },
  "created_at": "2026-06-09T00:00:00.000000Z",
  "updated_at": "2026-06-09T00:00:00.000000Z"
}
```

## Validation Rules

| Field | Rules |
| --- | --- |
| `category_id` | required when creating, integer, must reference a category owned by the authenticated user |
| `title` | required when creating, string, trimmed, minimum 1 character, maximum 150 characters |
| `content` | optional, nullable, string, trimmed when supplied, maximum 65,535 characters |

For update requests, `category_id`, `title`, and `content` are optional, but supplied fields must pass the same rules.

## List Notes

### Endpoint

```http
GET /api/notes
```

Optional filter:

```http
GET /api/notes?category_id=1
```

`category_id` must belong to the authenticated user.

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
      "category_id": 1,
      "title": "Sprint planning",
      "content": "Prepare backlog",
      "category": {
        "id": 1,
        "name": "Work",
        "color": "#f39c12",
        "created_at": "2026-06-09T00:00:00.000000Z",
        "updated_at": "2026-06-09T00:00:00.000000Z"
      },
      "created_at": "2026-06-09T00:00:00.000000Z",
      "updated_at": "2026-06-09T00:00:00.000000Z"
    }
  ]
}
```

## Create Note

### Endpoint

```http
POST /api/notes
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
  "category_id": 1,
  "title": " Sprint planning ",
  "content": " Prepare backlog "
}
```

`content` may be omitted or set to `null`.

### Success Response

Status code:

```txt
201 Created
```

Response body:

```json
{
  "message": "Note created successfully.",
  "data": {
    "note": {
      "id": 1,
      "category_id": 1,
      "title": "Sprint planning",
      "content": "Prepare backlog",
      "category": {
        "id": 1,
        "name": "Work",
        "color": "#f39c12",
        "created_at": "2026-06-09T00:00:00.000000Z",
        "updated_at": "2026-06-09T00:00:00.000000Z"
      },
      "created_at": "2026-06-09T00:00:00.000000Z",
      "updated_at": "2026-06-09T00:00:00.000000Z"
    }
  }
}
```

## Show Note

### Endpoint

```http
GET /api/notes/{note}
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
    "category_id": 1,
    "title": "Sprint planning",
    "content": "Prepare backlog",
    "category": {
      "id": 1,
      "name": "Work",
      "color": "#f39c12",
      "created_at": "2026-06-09T00:00:00.000000Z",
      "updated_at": "2026-06-09T00:00:00.000000Z"
    },
    "created_at": "2026-06-09T00:00:00.000000Z",
    "updated_at": "2026-06-09T00:00:00.000000Z"
  }
}
```

## Update Note

### Endpoint

```http
PATCH /api/notes/{note}
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
  "category_id": 2,
  "title": "Updated title",
  "content": "Updated content"
}
```

Partial updates are allowed:

```json
{
  "content": null
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
  "message": "Note updated successfully.",
  "data": {
    "note": {
      "id": 1,
      "category_id": 2,
      "title": "Updated title",
      "content": "Updated content",
      "category": {
        "id": 2,
        "name": "Ideas",
        "color": "#9b59b6",
        "created_at": "2026-06-09T00:00:00.000000Z",
        "updated_at": "2026-06-09T00:00:00.000000Z"
      },
      "created_at": "2026-06-09T00:00:00.000000Z",
      "updated_at": "2026-06-09T00:05:00.000000Z"
    }
  }
}
```

## Delete Note

### Endpoint

```http
DELETE /api/notes/{note}
```

### Success Response

Status code:

```txt
200 OK
```

Response body:

```json
{
  "message": "Note deleted successfully."
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

### Invalid or Unowned Category

Returned when `category_id` is missing, malformed, nonexistent, or belongs to another user.

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The selected category id is invalid.",
  "errors": {
    "category_id": [
      "The selected category id is invalid."
    ]
  }
}
```

### Missing or Blank Title

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The title field is required.",
  "errors": {
    "title": [
      "The title field is required."
    ]
  }
}
```

### Note Not Found

Returned when the note does not exist, the ID is malformed, or the note belongs to another user.

Status code:

```txt
404 Not Found
```

Example response:

```json
{
  "message": "No query results for model [App\\Models\\Note]."
}
```

## Example cURL

### List Notes

```bash
curl http://localhost:8000/api/notes \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|plain-text-sanctum-token"
```

### Create Note

```bash
curl -X POST http://localhost:8000/api/notes \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|plain-text-sanctum-token" \
  -d '{
    "category_id": 1,
    "title": "Sprint planning",
    "content": "Prepare backlog"
  }'
```

### Update Note

```bash
curl -X PATCH http://localhost:8000/api/notes/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|plain-text-sanctum-token" \
  -d '{
    "title": "Updated title",
    "content": null
  }'
```

### Delete Note

```bash
curl -X DELETE http://localhost:8000/api/notes/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|plain-text-sanctum-token"
```
