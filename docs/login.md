# API Login

Login authenticates an existing user with email and password, then returns a Sanctum Bearer token.

## Endpoint

```http
POST /api/login
```

## Headers

```http
Content-Type: application/json
Accept: application/json
```

## Request Body

```json
{
  "email": "test@example.com",
  "password": "password"
}
```

## Validation Rules

| Field | Rules |
| --- | --- |
| `email` | required, valid email format |
| `password` | required, string |

## Success Response

Status code:

```txt
200 OK
```

Response body:

```json
{
  "message": "Login successful.",
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

### Missing Email

Request:

```json
{
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

### Missing Password

Request:

```json
{
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

### Email Is Not Registered

Request:

```json
{
  "email": "unknown@example.com",
  "password": "password"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Response body:

```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": [
      "The provided credentials are incorrect."
    ]
  }
}
```

### Wrong Password

Request:

```json
{
  "email": "test@example.com",
  "password": "wrong-password"
}
```

Status code:

```txt
422 Unprocessable Entity
```

Response body:

```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": [
      "The provided credentials are incorrect."
    ]
  }
}
```

> Note: unregistered email and wrong password intentionally return the same message. This prevents leaking which emails are registered.

### Rate Limited

Login is rate limited to:

```txt
5 requests per minute per email + IP address
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
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'
```

## Test Account

After running the seeder, this account is available:

```txt
email: test@example.com
password: password
```
