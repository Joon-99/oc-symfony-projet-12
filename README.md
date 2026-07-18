# Eco Garden Symfony API

This is a Symfony 8.1 JSON REST API with 3 main features:

- user registration and administration
- conseils, which are month-based tips
- weather data lookup

The API uses JWT authentication, expects JSON and returns JSON.

## Endpoints

### Public endpoints

- `POST /auth` - get a JWT token
- `POST /user` - create a user

### Protected endpoints

✅ available to all authenticated users

- `GET /conseil` - list conseils for the current month
- `GET /conseil/{month}` - list conseils for a specific month
- `GET /meteo` - get weather for the current user's city
- `GET /meteo/{zipCode}` - look up weather by zip code

⚠️ restricted

- `POST /conseil` - create a conseil
- `PUT /conseil/{id}` - update a conseil
- `DELETE /conseil/{id}` - delete a conseil
- `GET /user/{id}` - get user data
- `PUT /user/{id}` - update user data
- `DELETE /user/{id}` - delete a user

## Authentication

Login with:

```http
POST /auth
Content-Type: application/json

{
  "username": "myUserName",
  "password": "myPassword"
}
```

If credentials are valid, the response includes a JWT token. Send that token on protected routes:

```http
Authorization: Bearer <token>
```

## User creation

You can create an account by providing the following data:

```http
POST /user
Content-Type: application/json

{
  "username": "alice",
  "password": "StrongPass123!",
  "zipCode": "75001"
}
```

If the request is valid, the API returns:

```http
201 Created
Location: http://localhost/user/42

{
  "id": 42,
  "username": "alice",
  "city": {
    "name": "Paris",
    "zipCode": "75001"
  }
}
```

## Notes

- `GET /conseil` returns the current month by default when no month is provided.
- `GET /meteo` uses the authenticated user's city.