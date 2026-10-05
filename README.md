# Inventory

A small Laravel app for tracking stock levels of physical products. Login required,
one kind of user, no roles.

This is a starting baseline built before the final schema and spec landed, so the
shape of the data is deliberately easy to change.

## What it does

- Dashboard: product count, units in stock, stock value at cost, low-stock and
  out-of-stock counts, plus the latest movements
- Products: create, edit, delete, search by name / SKU / location, filter by
  category and by status, sort by SKU, name or stock level
- Stock movements: stock in, stock out, and adjustment (set an exact counted level)
- Full per-product history showing the change, the running balance, the reference
  and who filed it
- Movement log across all products with product, type and date filters
- Categories: optional, deleting one leaves its products in place

## How stock is stored

Stock is a ledger, not a single editable number.

Every change writes one row to `stock_movements` holding a signed `quantity`
(positive adds, negative removes) and the `balance_after`. `products.stock_on_hand`
is maintained alongside it inside the same transaction, so listing and low-stock
filtering stay one indexed query instead of a sum over the whole history.

Because the figure is derived, it can be checked:

    php artisan inventory:reconcile --dry-run   # report any product out of step
    php artisan inventory:reconcile             # recompute from the ledger

All writes go through `App\Services\StockService`, which locks the product row
(`lockForUpdate`) before reading the balance, so two people filing movements at the
same moment cannot overwrite each other. Stock out is refused if it would take the
level below zero.

## Running it locally

Requires PHP 8.2+, Composer and Node.

    composer install
    npm install
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite        # or point DB_* at MySQL
    php artisan migrate --seed            # creates the login below
    php artisan db:seed --class=DemoSeeder   # optional sample catalogue
    npm run build
    php artisan serve

Login: `demo@example.com` / `password`

### MySQL instead of SQLite

Set these in `.env` and re-run `php artisan migrate`:

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=inventory
    DB_USERNAME=...
    DB_PASSWORD=...

The migrations use no SQLite-only features.

## Tests

    php artisan test

46 tests cover the stock arithmetic (including the overdraw guard, adjustments and
ledger reconciliation), the auth gate on every route, product validation, the
filters and the search.

## Layout

    app/Models/{Product,Category,StockMovement}.php
    app/Services/StockService.php          every stock write goes through here
    app/Http/Controllers/                  Dashboard, Product, StockMovement, Category
    app/Http/Requests/ProductRequest.php
    app/Console/Commands/ReconcileStock.php
    database/migrations/2026_10_05_*       categories, products, stock_movements
    database/seeders/DemoSeeder.php        sample catalogue
    resources/views/{products,movements,categories}/

Auth screens, the layout and the UI components are Laravel Breeze (Blade +
Tailwind + Alpine), kept as-is apart from the navigation links.
