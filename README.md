# 🛒 E-Commerce Order Processing & Inventory API

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" />
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/Redis-7.0-DC382D?style=for-the-badge&logo=redis&logoColor=white" />
  <img src="https://img.shields.io/badge/Docker-ready-2496ED?style=for-the-badge&logo=docker&logoColor=white" />
</p>

<p align="center">
  A production-grade RESTful API backend for e-commerce order processing and inventory management.<br/>
  Built with <strong>Laravel 13.1</strong>, <strong>MySQL</strong>, <strong>Redis</strong>, and <strong>Laravel Sanctum</strong>.
</p>

---

## 📌 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Quick Start](#-quick-start)
- [Environment Variables](#-environment-variables)
- [API Overview](#-api-overview)
- [Database Schema & Architecture](#-database-schema--architecture)
- [Caching Strategy & Invalidation](#-caching-strategy--invalidation)
- [Database Optimization & Indexing](#-database-optimization--indexing)
- [System Design & Key Technical Decisions](#-system-design--key-technical-decisions)
- [Running Tests](#-running-tests)
- [Default Credentials](#-default-credentials)

---

## ✨ Features

- ✅ Product & category management with nested categories
- ✅ Multi-variant products with attribute support (Size, Color, etc.)
- ✅ Real-time inventory & stock tracking
- ✅ Concurrent order protection with pessimistic locking
- ✅ Idempotent order creation (retry-safe)
- ✅ Order lifecycle — create, cancel, status updates
- ✅ Stock reservation system (no hard deduction until completion)
- ✅ Redis caching for product & inventory reads
- ✅ Async email notifications via Laravel Queue
- ✅ Role-based access control (Spatie Laravel Permission)
- ✅ Cursor-based pagination for high-performance listing
- ✅ API rate limiting per endpoint group
- ✅ Full audit trail with order status history
- ✅ Docker support

---

## 🛠 Tech Stack

| Layer            | Technology                     |
| ---------------- | ------------------------------ |
| Framework        | Laravel 11                     |
| Language         | PHP 8.2                        |
| Database         | MySQL 8.0                      |
| Cache / Queue    | Redis 7                        |
| Authentication   | Laravel Sanctum                |
| Authorization    | Spatie Laravel Permission      |
| Architecture     | Service-Repository Pattern     |
| Containerization | Docker & Docker Compose        |
| API Style        | RESTful, Versioned (`/api/v1`) |

---

## 📁 Project Structure

```
app/
├── Events/                  # OrderPlaced, OrderCancelled
├── Exceptions/              # InsufficientStockException, DuplicateOrderException
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── Admin/           # Product, Category, Inventory, Order, Report
│   │   └── Customer/        # Auth, Order, Profile
│   ├── Middleware/          # AdminMiddleware, CustomerMiddleware
│   ├── Requests/            # Form request validation per feature
│   └── Resources/           # API resource transformers
├── Jobs/                    # Queued jobs
├── Listeners/               # Event listeners (ShouldQueue)
├── Models/                  # Eloquent models
├── Repositories/
│   ├── Contracts/           # Interfaces
│   └── Eloquent/            # Implementations
└── Services/                # Business logic layer

database/
├── migrations/
├── seeders/
└── factories/

routes/
└── api.php                  # Versioned, rate-limited API routes

tests/
├── Feature/                 # API endpoint tests
└── Unit/                    # Service & repository unit tests
```

---

## 🚀 Quick Start

### Option A — Docker (Recommended)

```bash
# 1. Clone the repository
git clone https://github.com/your-username/ecommerce-api.git
cd ecommerce-api

# 2. Copy environment file
cp .env.example .env

# 3. Start all containers (app, mysql, redis, nginx)
docker-compose up -d

# 4. Install PHP dependencies
docker-compose exec app composer install

# 5. Generate application key
docker-compose exec app php artisan key:generate

# 6. Run migrations and seeders
docker-compose exec app php artisan migrate --seed

# 7. Start queue worker
docker-compose exec app php artisan queue:work --queue=emails,default
```

> API is ready at: `http://localhost:8000/api/v1`

---

### Option B — Local Setup

```bash
# 1. Clone the repository
git clone https://github.com/your-username/ecommerce-api.git
cd ecommerce-api

# 2. Install dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Configure your .env (see Environment Variables section)

# 5. Generate application key
php artisan key:generate

# 6. Run migrations
php artisan migrate

# 7. Seed roles, permissions, and admin user
php artisan db:seed

# 8. Start queue worker (new terminal tab)
php artisan queue:work --queue=emails,default

# 9. Start development server
php artisan serve
```

> API is ready at: `http://127.0.0.1:8000/api/v1`

---

## ⚙️ Environment Variables

```env
APP_NAME="E-Commerce API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=

# Redis — used for cache AND queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (for order confirmation emails)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="noreply@ecommerce.com"

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

---

## 📡 API Overview

### Base URL

```
http://localhost:8000/api/v1
```

### Authentication

All protected routes require a Bearer token in the `Authorization` header:

```
Authorization: Bearer {your-token}
```

---

### Admin Endpoints

| Method | Endpoint                             | Description                              |
| ------ | ------------------------------------ | ---------------------------------------- |
| POST   | `/admin/login`                       | Admin login                              |
| POST   | `/admin/logout`                      | Admin logout                             |
| GET    | `/admin/categories`                  | List categories                          |
| POST   | `/admin/categories`                  | Create category                          |
| PUT    | `/admin/categories/{id}`             | Update category                          |
| DELETE | `/admin/categories/{id}`             | Delete category                          |
| GET    | `/admin/products`                    | List products (search, filter, paginate) |
| POST   | `/admin/products`                    | Create product with variants             |
| GET    | `/admin/products/{id}`               | Product detail                           |
| PUT    | `/admin/products/{id}`               | Update product                           |
| DELETE | `/admin/products/{id}`               | Soft delete product                      |
| POST   | `/admin/products/{id}/images`        | Upload product image                     |
| GET    | `/admin/inventory`                   | Stock list with filters                  |
| PATCH  | `/admin/inventory/{variantId}/stock` | Update stock quantity                    |
| GET    | `/admin/inventory/low-stock`         | Low stock alerts                         |
| GET    | `/admin/orders`                      | All orders (filter by status/date)       |
| GET    | `/admin/orders/{id}`                 | Order detail with history                |
| PATCH  | `/admin/orders/{id}/status`          | Update order status                      |
| POST   | `/admin/orders/{id}/cancel`          | Cancel order                             |
| GET    | `/admin/reports/sales`               | Sales report                             |
| GET    | `/admin/reports/inventory`           | Inventory report                         |

---

### Customer Endpoints

| Method | Endpoint                       | Description           |
| ------ | ------------------------------ | --------------------- |
| POST   | `/customer/register`           | Customer registration |
| POST   | `/customer/login`              | Customer login        |
| POST   | `/customer/logout`             | Logout                |
| GET    | `/customer/profile`            | View profile          |
| PATCH  | `/customer/profile`            | Update profile        |
| POST   | `/customer/orders`             | Place new order       |
| GET    | `/customer/orders`             | My order history      |
| GET    | `/customer/orders/{id}`        | Order detail          |
| POST   | `/customer/orders/{id}/cancel` | Cancel order          |

---

### Public Endpoints

| Method | Endpoint           | Description                                |
| ------ | ------------------ | ------------------------------------------ |
| GET    | `/categories`      | Browse all categories                      |
| GET    | `/categories/{id}` | Category with products                     |
| GET    | `/products`        | Browse products (search, filter, paginate) |
| GET    | `/products/{id}`   | Product detail with variants               |

---

### Sample — Place Order

**Request**

```http
POST /api/v1/customer/orders
Authorization: Bearer {token}
Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000
Content-Type: application/json

{
  "items": [
    { "product_variant_id": 1, "quantity": 2 },
    { "product_variant_id": 4, "quantity": 1 }
  ],
  "shipping_address": {
    "name": "John Doe",
    "address": "123 Main St",
    "city": "Dhaka",
    "zip": "1207"
  },
  "payment_method": "cash_on_delivery",
  "notes": "Please call before delivery"
}
```

**Response `201 Created`**

```json
{
    "success": true,
    "message": "Order placed successfully.",
    "data": {
        "id": 101,
        "order_number": "ORD-20240615-XK92AB",
        "status": "pending",
        "subtotal": "1200.00",
        "tax_amount": "60.00",
        "discount_amount": "0.00",
        "total_amount": "1260.00",
        "items": [
            {
                "product_name": "Cotton T-Shirt",
                "variant_sku": "TSH-RED-XL",
                "quantity": 2,
                "unit_price": "600.00",
                "subtotal": "1200.00"
            }
        ],
        "payment": {
            "payment_method": "cash_on_delivery",
            "amount": "1260.00",
            "currency": "BDT",
            "status": "pending"
        },
        "created_at": "2024-06-15T10:30:00.000000Z"
    }
}
```

**Response `409 Conflict` (duplicate request)**

```json
{
    "success": false,
    "message": "Duplicate order request.",
    "data": {
        "order_number": "ORD-20240615-XK92AB"
    }
}
```

**Response `422 Unprocessable` (out of stock)**

```json
{
    "success": false,
    "message": "Insufficient stock for variant ID: 1. Available: 1, Requested: 2"
}
```

---

## 🗄️ Database Schema & Architecture

### Entity Relationship Diagram

```
users ──────────────────────────────────────────< orders
  │                                                  │
  │  (roles: admin / staff / customer via Spatie)   ├──< order_items >─── product_variants
  │                                                  │         │
  └──< customers                                     ├──< order_status_histories
        (phone, shipping_address)                    └──< payments

categories (self-referential, nested)
  └──< products
         ├──< product_images
         ├──< product_attribute_values >── attribute_values ──< attributes
         └──< product_variants
                  ├──── inventories (1:1)
                  ├──< variant_attribute_values >── attribute_values
                  └──< product_images (variant-specific)
```

---

### Table Descriptions

#### `users` — Authentication for all actors

| Column    | Type    | Notes                  |
| --------- | ------- | ---------------------- |
| name      | string  | Full name              |
| email     | string  | Unique, used for login |
| password  | string  | Bcrypt hashed          |
| is_active | boolean | Account toggle         |

#### `customers` — Customer profile (extends users)

| Column           | Type   | Notes                    |
| ---------------- | ------ | ------------------------ |
| user_id          | bigint | FK → users (unique 1:1)  |
| phone            | string | Optional, unique         |
| shipping_address | json   | Default delivery address |

#### `products` — Base product record

| Column      | Type      | Notes                                 |
| ----------- | --------- | ------------------------------------- |
| category_id | bigint    | FK → categories (restrictOnDelete)    |
| slug        | string    | Unique URL identifier                 |
| is_active   | boolean   | Visibility toggle                     |
| deleted_at  | timestamp | Soft delete — preserves order history |

#### `product_variants` — Purchasable SKU with price

| Column           | Type    | Notes                       |
| ---------------- | ------- | --------------------------- |
| sku              | string  | Unique stock-keeping unit   |
| price            | decimal | Current selling price       |
| compare_at_price | decimal | Original price (sale badge) |
| cost_price       | decimal | For margin calculation      |

#### `inventories` — Stock levels (1:1 with variant)

| Column              | Type    | Notes                        |
| ------------------- | ------- | ---------------------------- |
| quantity            | integer | Total physical stock         |
| reserved_quantity   | integer | Soft-held for pending orders |
| low_stock_threshold | integer | Alert trigger (default: 5)   |

> `available = quantity - reserved_quantity`

#### `orders` — Master order record

| Column           | Type   | Notes                                        |
| ---------------- | ------ | -------------------------------------------- |
| order_number     | string | ORD-20240101-ABC123                          |
| idempotency_key  | string | Unique per request — prevents duplicates     |
| shipping_address | json   | Snapshot at order time                       |
| status           | enum   | pending → processing → completed / cancelled |

#### `order_items` — Snapshot of purchase details

| Column       | Type    | Notes                                     |
| ------------ | ------- | ----------------------------------------- |
| product_name | string  | Snapshot — survives product rename        |
| variant_sku  | string  | Snapshot — survives SKU change            |
| unit_price   | decimal | DB price at order time — not client price |

#### `order_status_histories` — Immutable audit log

| Column          | Type      | Notes                           |
| --------------- | --------- | ------------------------------- |
| from_status     | enum      | null if initial placement       |
| to_status       | enum      | New status                      |
| changed_by_type | string    | 'admin' / 'customer' / 'system' |
| note            | text      | Reason for change               |
| created_at      | timestamp | No updated_at — append-only     |

---

## ⚡ Caching Strategy & Invalidation

### Cache Driver — Redis

Redis is used over file/database cache because:

- Sub-millisecond read latency
- Atomic operations (safe for concurrent access)
- Built-in TTL expiry
- Shared across multiple app instances (horizontally scalable)

---

### What Is Cached

| Cache Key                | TTL    | Data                                          |
| ------------------------ | ------ | --------------------------------------------- |
| `product:id:{id}`        | 30 min | Full product + variants + images + attributes |
| `product:slug:{slug}`    | 30 min | Same product, keyed by slug for SEO URLs      |
| `inventory:variant:{id}` | 5 min  | Stock levels for a specific variant           |

**Product list pages are not cached** — filter/search/sort combinations are too varied to key efficiently. Cached only at the individual product level.

---

### Invalidation Rules

| Event                            | Cache Keys Cleared                       |
| -------------------------------- | ---------------------------------------- |
| Product updated                  | `product:id:{id}`, `product:slug:{slug}` |
| Product deleted                  | `product:id:{id}`, `product:slug:{slug}` |
| Order placed (stock reserved)    | `inventory:variant:{id}` for each item   |
| Order cancelled (stock released) | `inventory:variant:{id}` for each item   |
| Order completed (stock deducted) | `inventory:variant:{id}` for each item   |
| Admin restock                    | `inventory:variant:{id}`                 |

```php
// Cache write
Cache::remember("product:id:{$id}", 1800, fn() => Product::with([...])->findOrFail($id));

// Cache invalidation
Cache::forget("product:id:{$product->id}");
Cache::forget("product:slug:{$product->slug}");
Cache::forget("inventory:variant:{$variantId}");
```

---

## 🔍 Database Optimization & Indexing

### Index Strategy

```sql
-- products
INDEX idx_products_category_active (category_id, is_active)
-- "active products in category X" — covers both filter conditions in one index

INDEX idx_products_active_created  (is_active, created_at)
-- "latest active products" — active filter + date sort

INDEX idx_products_deleted_active  (deleted_at, is_active)
-- SoftDeletes-aware queries skip deleted records efficiently

-- orders
UNIQUE idx_orders_idempotency      (idempotency_key)
-- Duplicate detection O(1) + race condition guard at DB level

INDEX  idx_orders_customer_status  (customer_id, status)
-- "my pending orders" — most common customer query

INDEX  idx_orders_status_created   (status, created_at)
-- Admin: "all processing orders this week"

INDEX  idx_orders_created_at       (created_at)
-- Date-range sales reporting

-- inventories
UNIQUE idx_inventory_variant_id   (product_variant_id)
-- Enforces 1:1 at database level, not just application level

INDEX  idx_inventory_quantity      (quantity)
-- Low stock alert: WHERE quantity <= low_stock_threshold

-- order_status_histories
INDEX idx_osh_order_created        (order_id, created_at)
-- Chronological status timeline for one order

INDEX idx_osh_to_status_created    (to_status, created_at)
-- "All recent cancellations" — admin dashboard
```

---

### Performance Techniques

**1. Select specific columns — no `SELECT *`**

```php
Product::select(['id', 'category_id', 'name', 'slug', 'is_active'])
    ->with(['category:id,name,slug'])
```

**2. Eager loading — prevent N+1**

```php
// 2 queries regardless of result count
Product::with(['category:id,name,slug', 'primaryImage:id,product_id,path,disk'])->get();
```

**3. Cursor pagination — consistent O(log n) at any scale**

```php
// Offset: LIMIT 15 OFFSET 10000 scans 10,015 rows — gets slower with pages
// Cursor: WHERE id < {cursor} LIMIT 15 — always hits primary key index
Product::orderBy('id', 'desc')->cursorPaginate(15);
```

**4. Bulk insert — single query for multiple records**

```php
$order->items()->insert($itemsArray); // one INSERT, not N inserts
```

**5. Pessimistic locking — serialize concurrent stock access**

```php
Inventory::where('product_variant_id', $id)->lockForUpdate()->firstOrFail();
// Row locked until transaction commits — no race condition possible
```

**6. Computed availability in SQL**

```php
$query->whereRaw('(quantity - reserved_quantity) > 0');
// MySQL evaluates in query — no PHP loop over results
```

---

## 🏗️ System Design & Key Technical Decisions

### Architecture Flow

```
HTTP Request
     │
     ▼
  Route (/api/v1)  →  throttle:X,1
     │
     ▼
  auth:sanctum  →  AdminMiddleware / CustomerMiddleware
     │
     ▼
  Controller  →  FormRequest validation
     │
     ▼
  Service  →  business logic & orchestration
     │
     ▼
  Repository  →  queries + Redis cache
     │
     ├──► MySQL  (persistent storage)
     └──► Redis  (cache + job queue)
```

---

### Decision 1 — Service-Repository Pattern

**Why:** Separates concerns — controllers handle HTTP only, services handle business rules, repositories handle data access. Each layer is independently unit-testable.

---

### Decision 2 — Single `users` Table + Spatie Roles

**Why:** Sanctum is built for a single users table. Separate admin/customer tables require hacking the Sanctum token model, custom auth providers, and fragile middleware. One table with Spatie roles is cleaner, testable, and scalable.

|                    | Two Tables ❌  | One Table + Spatie ✅ |
| ------------------ | -------------- | --------------------- |
| Sanctum support    | Requires hacks | Native                |
| Code duplication   | High           | None                  |
| Permission control | Manual         | Fine-grained          |

---

### Decision 3 — `reserved_quantity` Soft Reservation

**Why:** Deducting stock immediately on placement means cancellations require manual restock — race conditions possible. Soft reservation separates intent from fulfillment.

```
Order Placed    → reserved_quantity += qty   (total quantity unchanged)
Order Cancelled → reserved_quantity -= qty   (stock available again)
Order Completed → quantity -= qty            (permanent deduction)
                  reserved_quantity -= qty

available = quantity - reserved_quantity
```

---

### Decision 4 — Pessimistic Locking Prevents Overselling

**Problem:** Two concurrent requests for the last item both read stock = 1, both reserve — oversell occurs.

**Solution:** `lockForUpdate()` holds a row-level lock. Second request waits until first commits — then sees updated `reserved_quantity`.

```php
DB::transaction(function () use ($variantId, $qty) {
    $inventory = Inventory::where('product_variant_id', $variantId)
        ->lockForUpdate()   // ← serializes concurrent access
        ->firstOrFail();

    if (! $inventory->canFulfill($qty)) {
        throw new InsufficientStockException();
    }

    $inventory->increment('reserved_quantity', $qty);
});
```

---

### Decision 5 — Idempotent Order Creation

**Problem:** Network timeouts cause clients to retry → duplicate orders.

**Solution:** Unique `Idempotency-Key` per request. If key already exists → return existing order (HTTP 409). Unique DB constraint acts as last-resort guard.

```
1st request → key not found → create order → store key
Retry       → key found     → return existing order (409)
```

---

### Decision 6 — Price from Database, Not Request

**Problem:** Client could manipulate `unit_price` in request body.

**Solution:** Price always fetched from `product_variants` table at order time and snapshotted. Client-provided prices are ignored.

---

### Decision 7 — Async Queue for Post-Order Jobs

**Why:** Email sending synchronously inside the order transaction slows API response. Queue dispatches jobs to Redis — API responds in ~50ms, email sends in background.

```
Transaction commits → event(new OrderPlaced) → Redis queue → email job → email sent
                         ↑ API already responded 200ms ago
```

---

### Decision 8 — `order_items` Snapshot Pattern

**Why:** Product name, SKU, and price at the time of purchase must be preserved. If an admin renames a product or changes a price after an order was placed, historical orders must remain accurate.

---

### Rate Limiting

| Endpoint               | Limit  | Reason                        |
| ---------------------- | ------ | ----------------------------- |
| Login (admin/customer) | 10/min | Brute force protection        |
| Order creation         | 5/min  | Prevent bot/spam orders       |
| Customer routes        | 30/min | Normal usage headroom         |
| Admin routes           | 60/min | Management operations         |
| Public browse          | 60/min | High-traffic product browsing |

---

### Security Summary

| Threat                    | Solution                           |
| ------------------------- | ---------------------------------- |
| Unauthorized access       | Sanctum Bearer token               |
| Role escalation           | Spatie permission per route        |
| Price manipulation        | Price read from DB only            |
| Duplicate orders          | Idempotency key + unique index     |
| Race condition / oversell | Pessimistic lock in transaction    |
| Mass assignment           | Explicit `$fillable` on all models |
| Sensitive data leak       | `$hidden` on password, deleted_at  |
| Brute force login         | Rate limiting (10 req/min)         |
| SQL injection             | Eloquent parameter binding         |

---

## 🧪 Running Tests

```bash
# Run all tests
php artisan test
```

---

## 🔑 Default Credentials

| Role     | Email              | Password |
| -------- | ------------------ | -------- |
| Admin    | admin@email.com    | 12345678 |
| Customer | customer@email.com | 12345678 |
