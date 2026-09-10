# store-inventory-system

A small Laravel 10 application to record customer orders against a product
catalog and keep stock in sync.

## Features

- Product catalog with unique code, price, tax %, stock on hand
- Customer records (unique email)
- Order creation with computed subtotal, tax, grand total
- Automatic stock deduction with **row-level locking** (no overselling)
- Queued job for order confirmation email (logged, not sent)
- Low-stock alert endpoint with configurable threshold
- Billing UI matching the provided wireframe
- Test suite covering happy path + edge cases

## Tech Stack

- Laravel 10
- MySQL (or SQLite for tests)
- Database queue driver
- TailwindCSS (via CDN) for the UI

## Setup

```bash
git clone <repo-url>
cd store-inventory-system
composer install
cp .env.example .env
php artisan key:generate

# Configure DB in .env
# DB_DATABASE=store_inventory
# QUEUE_CONNECTION=database

php artisan migrate:fresh --seed
php artisan storage:link


Terminal 1 — Web server:
php artisan serve

Terminal 2 — Queue worker (required for job execution):
php artisan queue:work

API Endpoints :

1. Create Order:

POST /api/orders
Content-Type: application/json

{
  "customer_email": "thomas@example.com",
  "customer_name": "Thomas Anderson",
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 2, "quantity": 1 }
  ]
}

201 Created on success, 422 on insufficient stock or validation failure.

2. Order History:

GET /api/customers/{email}/orders

3. Low Stock Products:

GET /api/products/low-stock?threshold=10

Tests:
php artisan test

## AI-Assisted Development — Prompt Log

I used AI as a pair programming assistant throughout this project. All 
prompts and responses are saved in the `public/prompt_screenshots` folder as screenshots.

The prompt log shows how I directed the build:

1. **Schema design** — I described the entities and asked for migrations 
   with proper foreign keys.
2. **Seeders** — Small ask for sample data.
3. **UI layout** — I specified the nav bar, buttons and Orders listing.
4. **Edit flow** — I identified the wrong-entry use case and asked for 
   an edit UI.
5. **Architecture** — I asked for a Service layer + Form Requests to keep 
   controllers thin.
6. **Customers CRUD** — I asked for modal-based CRUD on a single page.
7. **Billing + Invoice** — I described the full billing flow and the 
   auto-create customer behavior.
8. **API routes** — I specified the 3 endpoints (order create, low stock, 
   order history).
9. **Test cases** — I enumerated exact scenarios including the 
   concurrent-orders-no-oversell test.
10. **Products CRUD** — I asked for the same pattern as other modules.

I directed the architecture, chose patterns, identified edge cases 
(stock restoration, concurrent orders, auto-create customer), and used AI 
for the implementation of those decisions.
