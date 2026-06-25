# Secure Auth & Profile Management System

A decoupled signup, login, and profile management system featuring custom premium styling layered on Bootstrap 5 and secure stateful backends utilizing MySQL (Auth), MongoDB (Profiles), and Redis (Sessions).

---

## Technical Stack & Architecture
* **Frontend**: HTML5, Custom CSS3, JavaScript (jQuery v3.7.1 + AJAX)
* **Backend**: PHP 8.x
* **Databases**:
  * **MySQL**: Accounts and credentials (bcrypt-hashed passwords).
  * **MongoDB**: Extended profile data (age, date of birth, contact, address).
  * **Redis**: High-speed cache for session mapping and rate limiting.

---

## API Response Specification
All backend endpoints return a standardized JSON response shape for consistency:
```json
{
  "success": true|false,
  "message": "User-facing description message.",
  "data": null|object
}
```

---

## Token/Session Flow Lifecycle

```
[Register]
    │  (Username, Email, Password)
    ▼
MySQL INSERT (Prepared statement + password_hash)
    │
[Login] (Rate limited: Max 10 attempts/min per IP)
    │  (Username/Email + Password)
    ▼
MySQL Query ──► Verify (password_verify)
    │
    ├──► Generate Token (CSPRNG bytes)
    ├──► Redis SETEX session:{token} -> user_id (TTL: 1 hour)
    ▼
Respond with Token ──► Save in Frontend localStorage
    │
[Profile Load / Update / Logout]
    │
    ├──► AJAX GET/POST with Header: `Authorization: Bearer <token>`
    ├──► PHP checks Redis session:{token}
    │      ├──► Token Missing/Expired: Return 401 &rarr; Redirect to Login
    │      └──► Token Valid: Allow MySQL (Read) and MongoDB (Read/Write)
    │
    └──► Logout: DELETE key from Redis, clear localStorage, Redirect
```

---

## Local Setup Instructions

### 1. Enable PHP Extensions
Ensure that the following extensions are active in your `php.ini` file:
```ini
extension=mysqli
extension=mongodb
extension=redis
```

### 2. Configure Environment Variables
Copy or create the `.env` file in the project root:
```ini
DB_HOST=127.0.0.1
DB_DATABASE=auth_system
DB_USERNAME=root
DB_PASSWORD=

MONGO_URI=mongodb://127.0.0.1:27017
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### 3. Initialize Databases

#### MySQL Schema
Run the queries inside `schema.sql` to initialize your local table:
```sql
CREATE DATABASE IF NOT EXISTS auth_system;
USE auth_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### MongoDB Collection
The database `auth_system` and collection `profiles` will automatically initialize on the first update query.

#### Redis Server
Start your local redis instance (defaults to port `6379`):
```bash
redis-server
```

---

## Production Hardening Considerations
When deploying to a live server:
1. **Enforce HTTPS**: Prevent network eavesdropping on raw `Authorization: Bearer` headers.
2. **Configure Authentication**: Add authentication credentials to MongoDB (`MONGO_URI`) and Redis (`auth` password in `config.php`).
3. **Disable Debug Details**: Ensure `display_errors = Off` is active in your production `php.ini` to avoid technical path disclosures.
