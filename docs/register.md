# API Register

Register creates a new user account, then returns a Sanctum Bearer token.

## Endpoint

```http
POST /api/register
```

## Headers

```http
Content-Type: application/json
Accept: application/json
```

## Request Body

```json
{
  "full_name": "Test User",
  "email": "test@example.com",
  "password": "password"
}
```

## Validation Rules

| Field | Rules |
| --- | --- |
| `full_name` | required, string, minimum 2 characters, maximum 100 characters |
| `email` | required, valid email format, maximum 255 characters, unique in users table |
| `password` | required, string, minimum 6 characters |

> Note: the API uses `full_name`, but the database stores this value in the `users.name` column.

## Success Response

Status code:

```txt
201 Created
```

Response body:

```json
{
  "message": "User registered successfully.",
  "data": {
    "user": {
      "id": 1,
      "full_name": "Test User",
      "email": "test@example.com"
    },
    "token": "1|plain-text-sanctum-token"
  }
}
```

Use the returned token for protected API routes:

```http
Authorization: Bearer 1|plain-text-sanctum-token
```

## Error Cases

### Missing Full Name

Request:

```json
{
  "email": "test@example.com",
  "password": "password"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The full name field is required.",
  "errors": {
    "full_name": [
      "The full name field is required."
    ]
  }
}
```

### Full Name Too Short

Request:

```json
{
  "full_name": "A",
  "email": "test@example.com",
  "password": "password"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The full name field must be at least 2 characters.",
  "errors": {
    "full_name": [
      "The full name field must be at least 2 characters."
    ]
  }
}
```

### Full Name Too Long

Request:

```json
{
  "full_name": "A name longer than 100 characters",
  "email": "test@example.com",
  "password": "password"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The full name field must not be greater than 100 characters.",
  "errors": {
    "full_name": [
      "The full name field must not be greater than 100 characters."
    ]
  }
}
```

### Missing Email

Request:

```json
{
  "full_name": "Test User",
  "password": "password"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

### Invalid Email Format

Request:

```json
{
  "full_name": "Test User",
  "email": "not-an-email",
  "password": "password"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The email field must be a valid email address.",
  "errors": {
    "email": [
      "The email field must be a valid email address."
    ]
  }
}
```

### Email Already Registered

Request:

```json
{
  "full_name": "Test User",
  "email": "test@example.com",
  "password": "password"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

### Missing Password

Request:

```json
{
  "full_name": "Test User",
  "email": "test@example.com"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The password field is required.",
  "errors": {
    "password": [
      "The password field is required."
    ]
  }
}
```

### Password Too Short

Request:

```json
{
  "full_name": "Test User",
  "email": "test@example.com",
  "password": "12345"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Example response:

```json
{
  "message": "The password field must be at least 6 characters.",
  "errors": {
    "password": [
      "The password field must be at least 6 characters."
    ]
  }
}
```

### Rate Limited

Register is rate limited to:

```txt
5 requests per minute per IP address
```

Status code:

```txt
429 Too Many Requests
```

Example response:

```json
{
  "message": "Too Many Attempts."
}
```

## Example cURL

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "Test User",
    "email": "test@example.com",
    "password": "password"
  }'
```
