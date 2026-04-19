# Nexus — API Reference

## Architecture

```
Request → FormRequest (validate) → Controller → Service → Model → DB
                                       ↓
                                  ApiResponse trait (unified response)
```

---

## Database Schema

### `users`
| column | type | notes |
|---|---|---|
| id | bigint | PK |
| name | string | unique |
| email | string | unique |
| phone | string | unique |
| password | string | hashed |
| remember_token | string | nullable |
| timestamps | | |

### `shops`
| column | type | notes |
|---|---|---|
| id | bigint | PK |
| name | string | |
| address | string | nullable |
| phone | string | nullable |
| owner_id | foreignId | → users, cascadeOnDelete |
| is_active | boolean | default true |
| timestamps | | |

### `shop_user` (pivot)
| column | type | notes |
|---|---|---|
| id | bigint | PK |
| shop_id | foreignId | → shops, cascadeOnDelete |
| user_id | foreignId | → users, cascadeOnDelete |
| role | string | `'admin'` or `'cashier'` |
| is_active | boolean | default true |
| timestamps | | |
| unique | | (shop_id, user_id) |

> Role lives on the pivot — not on the user — because the same person can be admin in one shop and cashier in another.

---

## Models

### User
```php
// Relationships
$user->shops()       // BelongsToMany via shop_user
$user->ownedShop()   // HasOne shops where owner_id = user.id

// Helpers
$user->isAdminOf(Shop $shop): bool
$user->isCashierOf(Shop $shop): bool
$user->isActiveIn(Shop $shop): bool

// Role constants
User::ROLE_ADMIN    = 'admin'
User::ROLE_CASHIER  = 'cashier'
```

### Shop
```php
// Relationships
$shop->owner()         // BelongsTo user (owner_id)
$shop->members()       // BelongsToMany users via shop_user
$shop->admins()        // members filtered by role = admin
$shop->cashiers()      // members filtered by role = cashier
$shop->activeMembers() // members filtered by is_active = true
```

### ShopUser (Pivot)
```php
// Extends Pivot — needed because pivot has extra columns (role, is_active)
$pivot->shop()  // BelongsTo Shop
$pivot->user()  // BelongsTo User
```

---

## Auth Flow

```
POST /register  → creates user only, no role, no shop yet
POST /login     → returns user + token + shop + role
                  shop: null = send to onboarding
                  shop: {...} = send to admin/cashier app
POST /logout           → deletes current device token
POST /forgot-password  → sends OTP to email
POST /reset-password   → verifies OTP, updates password
```

### Login Response
```json
{
  "success": true,
  "message": "Logged in successfully",
  "data": {
    "user": { "id", "name", "email", "phone" },
    "token": "...",
    "shop": null,
    "role": null
  }
}
```

> Flutter checks `shop` — if null → onboarding, if exists → route by role.

---

## Middleware Stack

| middleware | purpose |
|---|---|
| `auth:sanctum` | user must be authenticated |
| `has.shop` | user must have completed onboarding |
| `is.admin` | user must be admin in their shop |
| `is.cashier` | user must be cashier in their shop |

### Route Grouping Pattern
```php
// Shared (both roles)
Route::middleware(['auth:sanctum', 'has.shop'])->group(...)

// Admin only
Route::middleware(['auth:sanctum', 'has.shop', 'is.admin'])->group(...)

// Cashier only
Route::middleware(['auth:sanctum', 'has.shop', 'is.cashier'])->group(...)
```

### Performance Note
`HasShop` middleware fetches the shop and attaches it to the request:
```php
$request->merge(['current_shop' => $shop]);
```
`IsAdmin` and `IsCashier` reuse `$request->current_shop` — only one DB query total.

---

## Exceptions

### `ApiException`
Custom exception with a guaranteed clean HTTP status code:
```php
throw new ApiException('Invalid credentials', 401);
throw new ApiException('Invalid token', 400);
throw new ApiException('Token expired', 400);
```

Controllers catch `ApiException` separately from generic exceptions:
```php
} catch (ApiException $e) {
    return $this->error($e->getMessage(), $e->getCode());
} catch (\Exception $e) {
    return $this->error('Something went wrong', 500);
}
```

---

## Service Layer

### AuthService

| method | description |
|---|---|
| `register(array $data)` | Creates user, returns user + token |
| `login(array $credentials)` | Authenticates, returns user + token + shop + role |
| `logout($request)` | Deletes current access token |
| `logoutAll($request)` | Deletes all tokens (all devices) |
| `forgotPassword(string $email)` | Generates OTP, sends email — always returns success |
| `resetPassword(array $data)` | Verifies OTP, updates password, deletes OTP |

---

## Security Decisions

- `forgotPassword` always returns success — never reveals whether an email exists
- Token is deleted server-side on logout
- OTP expires after `config('otp.expires_minutes')` minutes
- OTP is deleted after use — one-time only
- `APP_DEBUG=false` in production — raw errors are never exposed
