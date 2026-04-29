# User Service

Simple user microservice for the UTS-IAE demo. It provides basic auth and user lookup endpoints used by other services.

## Endpoints

- `POST /api/auth/register`
	- body: `name`, `email`, `password`, `password_confirmation`
- `POST /api/auth/login`
	- body: `email`, `password`
- `GET /api/auth/profile`
	- header: `Authorization: Bearer <token>`
- `POST /api/auth/logout`
	- header: `Authorization: Bearer <token>`
- `GET /api/users/{id}`
- `GET /api/users/{id}/history`
	- calls OrderService on `http://127.0.0.1:8003`

## Local run

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8001
```
