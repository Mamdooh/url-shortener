# URL Shortener Project Analysis

## Overview

This is a **Laravel 8.17.2** URL shortener application that allows users to create shortened URLs, track visitor statistics, and manage links with optional privacy features.

---

## 📋 Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Homepage Deep Dive](#homepage-deep-dive)
3. [Database Schema](#database-schema)
4. [Routing Structure](#routing-structure)
5. [Core Features](#core-features)
6. [URL Shortening Algorithm](#url-shortening-algorithm)
7. [Authentication & Authorization](#authentication--authorization)
8. [API Endpoints](#api-endpoints)
9. [Key Files Reference](#key-files-reference)

---

## Architecture Overview

### Technology Stack
| Component | Technology |
|-----------|------------|
| **Framework** | Laravel 8.17.2 |
| **Frontend** | Bootstrap + jQuery |
| **Database** | MySQL (Eloquent ORM) |
| **Authentication** | Laravel Auth scaffolding |
| **Testing** | PHPUnit + Laravel Dusk (browser tests) |
| **Deployment** | Docker + Heroku support |

### Directory Structure (Key Components)
```
app/
├── Contracts/
│   └── UrlShortenerContract.php    # Interface for URL shortening service
├── Http/
│   ├── Controllers/
│   │   ├── LinksController.php     # Main homepage & URL processing
│   │   ├── DashboardController.php # User dashboard
│   │   ├── AdminController.php     # Admin panel
│   │   └── Api/LinksController.php # REST API
│   └── Middleware/
│       └── AdminMiddleware.php     # Admin access protection
├── Models/
│   ├── Link.php                    # Shortened URL model
│   ├── User.php                    # User model
│   └── Visitor.php                 # Visit tracking model
├── Providers/
│   └── UrlShortenerServiceProvider.php # Service binding
└── Utilities/
    ├── Base62Converter.php         # Base62 encoding for hashes
    ├── HashGenerator.php           # CRC32-based hash generation
    └── UrlShortenerService.php     # Main shortening logic
```

---

## Homepage Deep Dive

### Route Definition
```php
// routes/web.php
Route::get('/', 'LinksController@create');
Route::post('/links', 'LinksController@store');
```

### Controller: `LinksController`

The homepage is handled by `LinksController@create`:

```php
public function create()
{
    return view('create');
}
```

### Homepage Form Submission (`store` method)

```php
public function store(Request $request)
{
    $request->validate([
        'url' => 'required|url',
        'hash' => 'nullable|unique:links,hash',
        'allowed_email' => [
            new Delimited('email'),
            Rule::requiredIf($request->has('is_private')),
        ],
    ]);

    $link = $this->urlShortener->make($request->url, $request->hash);

    if ($request->has('is_private')) {
        $link->is_private = true;
        $link->allowed_email = $request->allowed_email;
    } else {
        $link->is_private = false;
        $link->allowed_email = null;
    }
    $link->save();

    return redirect('/')->with(['url' => url($link->hash)]);
}
```

### View: `resources/views/create.blade.php`

The homepage view contains a form with the following fields:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `url` | text input | Yes | The long URL to shorten |
| `hash` | text input | No | Optional custom hash/slug |
| `is_private` | checkbox | No | Mark URL as private |
| `allowed_email` | text input | Conditional | Comma-separated emails (required if private) |

### Homepage UI States

#### Logged Out State
- Shows: Login/Register links in navbar
- Form available: Yes (anonymous URL shortening supported)
- User dashboard: Not accessible

![Homepage Logged Out](../screenshots/home_page_logged_out.png)

#### Logged In State
- Shows: User dropdown with name + Admin badge (if admin)
- Form available: Yes (URLs associated with user account)
- User dashboard: Accessible via dropdown

![Homepage Logged In](../screenshots/home_page_logged_in.png)

### JavaScript Behavior

```javascript
// resources/js/app.js
$(document).ready(function () {
    // Toggle "Allowed Emails" field visibility based on "Is Private" checkbox
    if($('#is-private').is(':checked')) {
        $('#allowed-email').removeClass('hidden');
    } else {
        $('#allowed-email').addClass('hidden');
    }

   $('#is-private').click(function () {
        if($(this).is(':checked')) {
            $('#allowed-email').removeClass('hidden');
        } else {
            $('#allowed-email').addClass('hidden');
        }
   });
});
```

### Session Flash Messages

The homepage displays two types of flash messages:

1. **Success** (`session('url')`): Shows the generated shortened URL
2. **Error** (`session('error')`): Shows error messages (e.g., invalid URL, private URL access denied)

---

## Database Schema

### `links` Table
```sql
CREATE TABLE links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULLABLE,        -- NULL for anonymous links
    url VARCHAR(255) NOT NULL,               -- Original long URL
    hash VARCHAR(255) UNIQUE NOT NULL,       -- Short hash/slug
    is_private BOOLEAN DEFAULT FALSE,        -- Privacy flag
    allowed_email TEXT NULLABLE,             -- Comma-separated emails
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### `visitors` Table
```sql
CREATE TABLE visitors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    link_id BIGINT UNSIGNED NOT NULL,        -- FK to links
    ip VARCHAR(255) NULLABLE,                -- Visitor IP
    os VARCHAR(255) NULLABLE,                -- Operating system
    browser VARCHAR(255) NULLABLE,           -- Browser name
    device VARCHAR(255) NULLABLE,            -- Device type
    meta JSON NULLABLE,                      -- Additional metadata
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### `users` Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULLABLE,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    api_token VARCHAR(30),                   -- For API authentication
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Routing Structure

### Web Routes (`routes/web.php`)

| Method | URI | Controller@Action | Middleware | Description |
|--------|-----|-------------------|------------|-------------|
| GET | `/` | `LinksController@create` | - | Homepage |
| POST | `/links` | `LinksController@store` | - | Create shortened URL |
| GET | `/links/{link}` | `LinksController@show` | - | Link details |
| GET | `/dashboard` | `DashboardController@index` | auth | User dashboard |
| GET | `/settings` | `DashboardController@settings` | auth | User settings |
| POST | `/update-profile` | `DashboardController@updateProfile` | auth | Update profile |
| POST | `/generate-token` | `DashboardController@generateToken` | auth | Generate API token |
| GET | `/admin/links` | `AdminController@links` | admin | All links (admin) |
| GET | `/admin/users` | `AdminController@users` | admin | All users (admin) |
| GET | `/{hash}` | `LinksController@process` | - | Redirect to original URL |

### API Routes (`routes/api.php`)

| Method | URI | Controller@Action | Auth | Description |
|--------|-----|-------------------|------|-------------|
| GET | `/api/links/{hash}` | `Api\LinksController@byHash` | Bearer Token | Get link by hash |
| POST | `/api/links` | `Api\LinksController@create` | Bearer Token | Create new link |

---

## Core Features

### 1. URL Shortening
- Accepts any valid URL
- Generates unique hash using CRC32 + Base62 encoding
- Supports custom hashes (must be unique)
- Works for both authenticated and anonymous users

### 2. Private URLs
- URLs can be marked as private
- Requires comma-separated list of allowed emails
- Only users with allowed emails can access the link
- Authentication required to access private URLs

### 3. Visitor Tracking
Each visit records:
- IP address
- Operating System (via jenssegers/agent)
- Browser name
- Device type

### 4. User Dashboard
- Lists all user's shortened URLs
- Shows visitor count per link
- Access to link details

### 5. Admin Panel
- View all links in the system
- View all registered users
- Requires `is_admin = true` in database

---

## URL Shortening Algorithm

### Flow
```
Long URL → CRC32 Hash → Base62 Encode → Short Hash
```

### HashGenerator
```php
// app/Utilities/HashGenerator.php
public static function create(string $url): string
{
    $numericHash = crc32($url);                          // Generate CRC32 hash
    return Base62Converter::encode((string) $numericHash); // Convert to Base62
}
```

### Base62Converter
```php
// app/Utilities/Base62Converter.php
// Character set: 0-9, a-z, A-Z (62 characters)
private static $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

public static function encode(string $val): string
{
    $str = '';
    do {
        $m = bcmod($val, '62');
        $str = self::$chars[$m] . $str;
        $val = bcdiv(bcsub($val, $m), '62');
    } while (bccomp($val, '0') > 0);
    return $str;
}
```

### Collision Handling
```php
// app/Utilities/UrlShortenerService.php
if (Link::where('hash', $hash)->exists()) {
    $hash = Str::random(6); // Fallback to random string
}
```

---

## Authentication & Authorization

### User Registration & Login
- Standard Laravel Auth scaffolding (`Auth::routes()`)
- Email verification not enforced
- API token auto-generated on user creation

### Admin Access
- No UI for creating admin users
- Must manually set `is_admin = true` in database
- Or run `php artisan db:seed` for default admin:
  - Email: `admin@url.com`
  - Password: `password`

### API Authentication
- Token-based authentication (`auth:api` middleware)
- Token available in user settings page
- Regenerate token: POST `/generate-token`

---

## API Endpoints

### GET `/api/links/{hash}`
Retrieve link information by hash.

**Headers:**
```
Authorization: Bearer {api_token}
Accept: application/json
```

**Response (200):**
```json
{
    "error": false,
    "link": {
        "url": "https://example.com/long-url",
        "hash": "abc123",
        "is_private": false,
        "created_at": "07-11-2019 09:03",
        "user": null
    }
}
```

### POST `/api/links`
Create a new shortened URL.

**Headers:**
```
Authorization: Bearer {api_token}
Content-Type: application/json
```

**Body:**
```json
{
    "url": "https://example.com/very-long-url",
    "is_private": false,
    "allowed_email": "user1@email.com, user2@email.com"
}
```

**Response (201):**
```json
{
    "error": false,
    "link": {
        "url": "https://example.com/very-long-url",
        "hash": "xyz789",
        "is_private": false,
        "allowed_email": ["user1@email.com", "user2@email.com"],
        "created_at": "08-11-2019 13:12",
        "user": {
            "name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `app/Http/Controllers/LinksController.php` | Homepage & URL processing |
| `app/Utilities/UrlShortenerService.php` | Core shortening logic |
| `app/Utilities/HashGenerator.php` | Hash generation |
| `app/Utilities/Base62Converter.php` | Base62 encoding |
| `app/Models/Link.php` | Link model with relationships |
| `app/Models/Visitor.php` | Visitor tracking model |
| `resources/views/create.blade.php` | Homepage view |
| `resources/views/layouts/app.blade.php` | Main layout template |
| `resources/js/app.js` | Frontend JavaScript |
| `routes/web.php` | Web routes |
| `routes/api.php` | API routes |

---

## CLI Commands

```bash
# Shorten a URL via CLI
php artisan url:short --help

# Resolve a shortened URL via CLI
php artisan url:resolve --help
```

---

## Testing

### Unit Tests
```bash
# Configure .env.testing with database
./vendor/bin/phpunit
```

### Browser Tests (Laravel Dusk)
```bash
php artisan dusk:install
php artisan dusk
```

---

## Development Setup

```bash
# Clone and install
git clone <repo> url
cd url
composer install
npm install
npm run dev

# Configure
cp .env.example .env
php artisan key:generate

# Database
# Set DB credentials in .env
php artisan migrate
php artisan db:seed  # Optional: creates admin user

# Run
php artisan serve
# Visit http://127.0.0.1:8000
```

---

## Summary

This URL shortener is a well-structured Laravel application with:

- ✅ Clean architecture using contracts/interfaces
- ✅ Visitor tracking with device detection
- ✅ Private URL support with email whitelisting
- ✅ RESTful API with token authentication
- ✅ Admin panel for oversight
- ✅ Both CLI and web interfaces
- ✅ Docker support for deployment

The homepage (`/`) serves as the main entry point where users can shorten URLs, optionally with custom hashes and privacy settings. The form handles both authenticated and anonymous users, with links being associated with the logged-in user when available.


