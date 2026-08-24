# Mutah API - Project Handoff

Last reviewed: 2026-08-23

## 1. Project Overview

Mutah is a Laravel 12 API for renting products/items. The current backend covers:

- User registration and login.
- Bearer-token authentication with Laravel Sanctum.
- User profile display and update.
- Public product listing with search, category filtering, and pagination.
- Authenticated product creation, update, and deletion.
- Saved items / favorites management.
- Rental request creation with date conflict prevention.
- Owner-side approval/rejection flow for rental requests.
- Rental cancellation with a 20% default fee and pending refund tracking.
- Payments and receipt review by administrators.
- Identity verification upload and manual review flow, ready for Colab integration.
- Monthly subscriptions with receipt review, usage limits, expiry, and notifications.
- Admin dashboard API with platform statistics and pending work queues.

The frontend is only the default Vite/Laravel starter setup at this stage. The main work is currently in the API/backend.

## 2. Technology and Configuration

- PHP: `^8.2`
- Laravel: `^12.0`
- Authentication: `laravel/sanctum ^4.0`
- Database: MySQL is currently configured and migrations are applied.
- Frontend tooling: Vite 7, Tailwind CSS 4, Axios.
- API prefix: `/api/v1`
- Database tables use uppercase names for the main entities: `Users`, `Products`, `SubscriptionPlans`, `IdentityVerifications`.
- IDs are UUID strings, not auto-increment integers.

Important: Passport is not installed. Authentication uses Sanctum, so do not add Passport interfaces or Passport imports.

## 3. Main Files

### Routes

- `routes/api.php`: API routes.
- `routes/web.php`: only the default `/` welcome page.

### Controllers

- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/ProfileController.php`
- `app/Http/Controllers/Api/ProductController.php`
- `app/Http/Controllers/Api/SavedItemController.php`
- `app/Http/Controllers/Api/RentalRequestController.php`
- `app/Http/Controllers/Api/NotificationController.php`
- `app/Http/Controllers/Api/PaymentController.php`
- `app/Http/Controllers/Api/IdentityVerificationController.php`
- `app/Http/Controllers/Api/AdminIdentityVerificationController.php`
- `app/Http/Controllers/Api/SubscriptionController.php`
- `app/Http/Controllers/Api/AdminSubscriptionController.php`
- `app/Http/Controllers/Api/AdminDashboardController.php`

### Requests and Resources

- `app/Http/Requests/Api/Auth/RegisterRequest.php`
- `app/Http/Requests/Api/Auth/LoginRequest.php`
- `app/Http/Requests/Api/Product/StoreProductRequest.php`
- `app/Http/Requests/Api/Product/UpdateProductRequest.php`
- `app/Http/Requests/Api/User/UpdateProfileRequest.php`
- `app/Http/Requests/Api/SavedItem/StoreSavedItemRequest.php`
- `app/Http/Resources/Api/ProductResource.php`
- `app/Http/Resources/Api/SavedItemResource.php`
- `app/Http/Requests/Api/IdentityVerification/StoreIdentityVerificationRequest.php`
- `app/Http/Requests/Api/Subscription/StoreSubscriptionRequest.php`

### Models

- `app/Models/User.php`
- `app/Models/Product.php`
- `app/Models/SubscriptionPlan.php`
- `app/Models/SavedItem.php`
- `app/Models/RentalRequest.php`
- `app/Models/Payment.php`
- `app/Models/Notification.php`
- `app/Models/IdentityVerification.php`
- `app/Models/Subscription.php`

### Database

- `database/migrations/`: schema migrations.
- `database/seeders/SubscriptionPlanSeeder.php`: creates a standard plan.
- `database/seeders/DemoDataSeeder.php`: creates 5 demo users and 15 demo products.
- `database/seeders/DatabaseSeeder.php`: calls `SubscriptionPlanSeeder` and `DemoDataSeeder`.
- `app/Services/SubscriptionService.php`: monthly subscription status and usage rules.
- `app/Services/IdentityVerificationService.php`: external model integration boundary.
- `app/Console/Commands/ExpireSubscriptions.php`: expires subscriptions and sends notifications.

## 4. Current API Routes

All routes are under `/api/v1`.

### Public routes

| Method | Endpoint | Controller | Status |
| --- | --- | --- | --- |
| POST | `/register` | `AuthController@register` | Implemented; auto-creates standard plan if missing |
| POST | `/login` | `AuthController@login` | Implemented |
| GET | `/products` | `ProductController@index` | Implemented |
| GET | `/products/{id}` | `ProductController@show` | Implemented; returns active product details |

### Protected routes

Send header: `Authorization: Bearer <access_token>`.

| Method | Endpoint | Controller | Status |
| --- | --- | --- | --- |
| POST | `/logout` | `AuthController@logout` | Implemented |
| GET | `/profile` | `ProfileController@show` | Implemented with placeholder statistics |
| POST | `/profile` | `ProfileController@update` | Implemented; password updates to `password_hash` |
| POST | `/products` | `ProductController@store` | Implemented; multipart upload required |
| PUT or POST | `/products/{product}` | `ProductController@update` | Implemented with owner authorization |
| POST | `/products/{product}/toggle-status` | `ProductController@toggleStatus` | Implemented; freeze/unfreeze product |
| DELETE | `/products/{product}` | `ProductController@destroy` | Implemented with owner authorization |
| GET | `/saved-items` | `SavedItemController@index` | Implemented; paginated list of current user's saved products |
| POST | `/saved-items` | `SavedItemController@store` | Implemented; body `{ product_id }`, idempotent via `firstOrCreate` |
| DELETE | `/saved-items/{product}` | `SavedItemController@destroy` | Implemented; removes by product id, 404 if not saved |
| POST | `/products/{product}/toggle-save` | `SavedItemController@toggle` | Implemented; one-tap save/unsave, returns `is_saved` boolean |
| GET | `/rental-requests` | `RentalRequestController@index` | Implemented; paginated requests for current renter |
| GET | `/rental-requests/my` | `RentalRequestController@myRequests` | Implemented; requests related to current user as renter or owner |
| POST | `/rental-requests` | `RentalRequestController@store` | Implemented; creates a pending request with date conflict prevention |
| PATCH | `/rental-requests/{rentalRequest}/status` | `RentalRequestController@updateStatus` | Implemented; accepts or rejects requests if owner |
| PATCH | `/rental-requests/{rentalRequest}/cancel` | `RentalRequestController@cancel` | Implemented; cancellation after verified payment with 20% default fee |
| GET | `/subscription-plans` | `SubscriptionController@plans` | Implemented |
| GET | `/my-subscription` | `SubscriptionController@current` | Implemented |
| POST | `/subscriptions` | `SubscriptionController@store` | Implemented; receipt upload |
| GET | `/notifications` | `NotificationController@index` | Implemented |
| GET | `/notifications/unread-count` | `NotificationController@unreadCount` | Implemented |
| PATCH | `/notifications/{id}/read` | `NotificationController@markAsRead` | Implemented |
| PATCH | `/notifications/read-all` | `NotificationController@markAllAsRead` | Implemented |
| POST | `/identity-verifications` | `IdentityVerificationController@store` | Implemented; private image storage |
| GET | `/identity-verifications/current` | `IdentityVerificationController@current` | Implemented |
| GET | `/payments` | `PaymentController@index` | Implemented |
| POST | `/payments` | `PaymentController@store` | Implemented; receipt upload |
| GET | `/admin/dashboard` | `AdminDashboardController@index` | Implemented; admin role required |
| GET | `/admin/payments` | `PaymentController@adminIndex` | Implemented; admin role required |
| PATCH | `/admin/payments/{payment}/verify` | `PaymentController@verify` | Implemented; admin role required |
| PATCH | `/admin/payments/{payment}/reject` | `PaymentController@reject` | Implemented; admin role required |
| GET | `/admin/identity-verifications` | `AdminIdentityVerificationController@index` | Implemented; admin role required |
| PATCH | `/admin/identity-verifications/{identityVerification}/approve` | `AdminIdentityVerificationController@approve` | Implemented |
| PATCH | `/admin/identity-verifications/{identityVerification}/reject` | `AdminIdentityVerificationController@reject` | Implemented |
| GET | `/admin/subscriptions` | `AdminSubscriptionController@index` | Implemented; admin role required |
| PATCH | `/admin/subscriptions/{subscription}/approve` | `AdminSubscriptionController@approve` | Implemented; starts a one-month subscription |
| PATCH | `/admin/subscriptions/{subscription}/reject` | `AdminSubscriptionController@reject` | Implemented |

## 5. API Request Details

### Register

`POST /api/v1/register`

Content-Type: `application/json`

Required fields:

```json
{
  "full_name": "Ahmed Salem",
  "username": "ahmed_salem",
  "email": "ahmed@example.com",
  "phone": "0599111111",
  "governorate": "gaza",
  "district": "Al Rimal",
  "password": "password123",
  "password_confirmation": "password123",
  "terms": true
}
```

Valid governorates: `north`, `gaza`, `middle`, `khanyonis`, `rafah`.

The response returns `access_token`, `token_type`, and the created user.

### Login

`POST /api/v1/login`

```json
{
  "login": "ahmed_salem",
  "password": "12345678"
}
```

The `login` field accepts either username or email.

### List products

`GET /api/v1/products`

Optional query parameters:

- `category`: filters by category. Use `all` to disable category filtering.
- `search`: searches in title and description.
- Pagination is enabled with 12 products per page.

### Create product

`POST /api/v1/products`

Authentication: required.

Content-Type: `multipart/form-data`.

Fields:

- `title`: required string.
- `category`: required enum value.
- `description`: required string.
- `price_per_hour`: required numeric value.
- `deposit_amount`: required numeric value.
- `images[]`: required, 1 to 4 image files, jpeg/jpg/png/webp, max 2 MB each.
- `available_dates[]`: required dates in `Y-m-d` format.
- `start_time`: optional `H:i`.
- `end_time`: optional `H:i`.
- `is_all_day`: optional boolean.

Categories:

`cameras`, `clothes`, `electronics`, `items`, `camping`, `medical items`, `instruments`, `books`, `house items`.

Uploaded images are stored on the public disk under `storage/products` and returned as URLs.

### Update/delete/toggle product

All operations use route model binding and require the authenticated user to own the product.

- `PUT /api/v1/products/{product}`
- `POST /api/v1/products/{product}`
- `POST /api/v1/products/{product}/toggle-status` - Freeze/unfreeze product
- `DELETE /api/v1/products/{product}`

### Rental request flow

`POST /api/v1/rental-requests`

Authentication: required.

Request body:

```json
{
  "product_id": "uuid-of-product",
  "start_time": "2026-09-01 10:00:00",
  "end_time": "2026-09-01 12:00:00"
}
```

Rules:

- renter cannot request their own product
- product must be active
- request date range cannot overlap with an already accepted rental
- new request is created with `owner_status = pending`

Owner action:

`PATCH /api/v1/rental-requests/{rentalRequest}/status`

```json
{
  "status": "accepted"
}
```

Valid statuses: `accepted`, `rejected`.

## 6. Database Schema

Current migrations create:

- `SubscriptionPlans`: plan type, price, monthly listing/rental limits, commission rate, and reporting flag.
- `Users`: UUID user, profile data, password in `password_hash`, status, role, and required `plan_id` foreign key.
- `IdentityVerifications`: private image paths, verification status, model response, and manual review data.
- `Products`: owner, title, category, JSON images/dates, schedule, prices, and status.
- `rental_requests`: renter, product, start/end time, owner status, cancellation and refund fields.
- `payments`: rental, payer, price/deposit totals, receipt, payment status, cancellation fee, and refund fields.
- `saved_items`: user/product saved relation.
- `notifications`: user notifications and optional reference ID.
- `subscriptions`: user plan, receipt, monthly usage, lifecycle dates, and admin review fields.
- `personal_access_tokens`: Sanctum tokens.

Existing model relations:

- `User -> products()`
- `User -> subscriptionPlan()`
- `User -> savedItems()`
- `User -> rentalRequests()`, `notifications()`, `identityVerifications()`, `subscriptions()`
- `Product -> owner()`, `rentalRequests()`
- `SavedItem -> user()`
- `SavedItem -> product()`
- `RentalRequest -> renter()`, `product()`, `payments()`, `cancelledBy()`
- `Payment -> rental()`, `payer()`
- `Subscription -> user()`, `plan()`, `reviewer()`

Models and API controllers for rentals, payments, notifications, identity verification, and subscriptions are implemented.

## 7. Seed Data and Current Database State

Migration status was checked on 2026-08-19: all migrations are marked as `Ran`.

`DemoDataSeeder` contains:

- 5 demo users.
- Password for demo users: `12345678`.
- Usernames: `ahmed_salem`, `mohammed_n`, `mahmoud_m`, `khaled_a`, `omar_r`.
- 15 products with Unsplash image URLs.
- Products are distributed between the 5 demo users.
- Admin account created/updated by `DemoDataSeeder`:
  - Username: `admin`
  - Email: `admin@mutah.test`
  - Password: `Admin@123456`
  - Role: `admin`

Recommended intended order:

```bash
php artisan db:seed --class=SubscriptionPlanSeeder
php artisan db:seed --class=DemoDataSeeder
```

`php artisan db:seed` now works correctly. It calls `SubscriptionPlanSeeder` and `DemoDataSeeder` in the correct order.

## 8. Current Business Rules

- New users start on the Standard plan.
- Paid subscriptions start for one month when an admin approves the receipt.
- Only one active subscription is kept per user; approving a new one expires the previous active record.
- Listing and rental usage is tracked per user and resets monthly.
- Product and rental creation enforce the current plan limits.
- A renter can cancel an accepted rental before it starts when payment is verified; the default cancellation fee is 20%.
- Product deletion is blocked while a confirmed rental is current or future.

## 9. Known Issues / Next Work

1. ~~`AuthController@register` assumes a standard plan exists.~~ **FIXED**: Now uses `firstOrCreate` to auto-create the standard plan.
2. ~~`ProfileController@update` writes the password into `password`.~~ **FIXED**: Now correctly writes to `password_hash`.
3. ~~`ProfileController@show` reads `$subscriptionPlan->name`.~~ **FIXED**: Now correctly reads `plan_type`.
4. Profile rental counts, earnings, and held deposits are placeholders set to zero.
5. ~~Product update validation uses `product_images` while product creation uses `images`.~~ **FIXED**: Now standardized to `images` with dedicated `UpdateProductRequest`.
6. ~~Product update has no dedicated FormRequest.~~ **FIXED**: Created `UpdateProductRequest` with strict validation.
7. Colab is not connected yet. Implement the HTTP call and response mapping in `IdentityVerificationService`.
8. The admin dashboard is currently an API endpoint; no visual dashboard UI is implemented.
9. Refund completion is not automated; cancellation records `refund_status = pending`.
10. No dedicated admin middleware exists; admin controllers currently check `role = admin` directly.
11. Expand tests for authentication, product CRUD, usage limits, expiry scheduling, and notifications.
12. ~~The category value `clouthes` is misspelled.~~ **FIXED**: Migrated to `clothes` with a new migration `2026_08_19_000001_fix_category_clothes_typo`.
13. New endpoint added: `POST /products/{product}/toggle-status` for freezing/unfreezing products.
14. Subscription endpoints, admin review, monthly expiry command, cancellation flow, and admin dashboard are implemented.

## 10. Useful Commands

```bash
# Install dependencies
composer install
npm install

# Run migrations
php artisan migrate

# Inspect routes
php artisan route:list --path=api

# Run the current tests
php artisan test

# Run subscription expiry and notifications manually
php artisan subscriptions:expire

# Start Laravel server
php artisan serve

# Start Vite in another terminal
npm run dev

# Create the public storage symlink for uploaded images
php artisan storage:link
```

## 11. Verification Status

Latest verification on 2026-08-23:

```text
13 tests passed
46 assertions passed
```

Coverage includes subscription submission/approval, prevention of two active subscriptions, payment submission/verification, identity submission/manual approval, admin authorization, rental cancellation, and product deletion rules.

## 12. Suggested Next Implementation Order

1. ~~Fix `DatabaseSeeder` and make all seeders idempotent.~~ **DONE**
2. ~~Add `ProductController@show()`.~~ **DONE**
3. ~~Fix registration when the default plan is missing.~~ **DONE**
4. ~~Fix profile password and plan display fields.~~ **DONE**
5. Connect `IdentityVerificationService` to the external Colab `/verify` endpoint.
6. Add dedicated admin middleware and apply it to admin routes.
7. Add automated refund completion or integrate a payment provider.
8. Build the visual admin dashboard and connect it to `/admin/dashboard`.
9. Expand tests for auth, product CRUD, limits, expiry scheduling, and notifications.
10. Prepare a complete Postman collection and deployment environment variables.
11. Replace placeholder profile statistics with real queries.
12. Connect the frontend after the API contracts are covered by tests.
