# Nexus

> Backend infrastructure for transaction tracking — ingests SMS messages, applies filtering and parsing logic, and exposes structured analytical data through a clean REST API.

---

## Requirements

- PHP >= 8.1
- Composer
- MySQL or PostgreSQL
- A mail driver configured (for OTP emails)

---

## Installation

```bash
# 1. Clone the repo
git clone https://github.com/your-org/nexus.git
cd nexus

# 2. Install dependencies
composer install

# 3. Set up environment
cp .env.example .env
php artisan key:generate

# 4. Configure your .env (see below)

# 5. Run migrations
php artisan migrate

# 6. Start the server
php artisan serve
```

---

## Environment Variables

```env
# App
APP_ENV=local
APP_DEBUG=true                  # Set to false in production
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexus
DB_USERNAME=root
DB_PASSWORD=

# Mail (used for OTP delivery — powered by Resend)
MAIL_MAILER=resend
RESEND_API_KEY=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"

# OTP
OTP_EXPIRES_MINUTES=5
```

---

## Auth Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/register` | Create a new user |
| POST | `/login` | Authenticate and get token |
| POST | `/logout` | Invalidate current token |
| POST | `/forgot-password` | Send OTP to email |
| POST | `/reset-password` | Verify OTP and update password |

After login, the response includes a `shop` field:
- `shop: null` → redirect to onboarding
- `shop: { ... }` → route by `role` (`admin` or `cashier`)

---

## Onboarding Endpoints *(planned)*

| Method | Endpoint | Description |
|---|---|---|
| POST | `/onboarding/create-shop` | Create a shop — caller becomes admin |
| POST | `/onboarding/join-shop` | Join a shop via invite code — caller becomes cashier |

---

## Middleware

Routes are protected by a layered middleware stack:

```php
// Both roles
Route::middleware(['auth:sanctum', 'has.shop'])->group(...)

// Admin only
Route::middleware(['auth:sanctum', 'has.shop', 'is.admin'])->group(...)

// Cashier only
Route::middleware(['auth:sanctum', 'has.shop', 'is.cashier'])->group(...)
```

---

## Response Format

Every endpoint returns the same shape:

```json
// Success
{ "success": true, "message": "...", "data": { } }

// Error
{ "success": false, "message": "...", "errors": null }
```

---

## Roadmap

- [ ] Subscriptions via Stripe / Paymob
- [ ] Trial period (`trial_ends_at`)
- [ ] Shop invite codes for cashier onboarding

---

## Documentation

Full API reference, schema, and architecture details → [`docs/api.md`](docs/api.md)
