# Mutah API Documentation

Last reviewed: 2026-08-24

## Base URL

Local XAMPP:

```text
http://localhost/Mutah/public/api/v1
```

Laravel development server:

```text
http://127.0.0.1:8000/api/v1
```

Replace the base URL with the deployed domain in production.

## Authentication

Public endpoints do not require authentication.

Protected endpoints require:

```http
Authorization: Bearer ACCESS_TOKEN
Accept: application/json
```

The token is returned by `register` and `login` in the `access_token` field.

Admin endpoints require a token belonging to a user whose `role` is `admin`.

## Standard Response Format

Successful responses generally include:

```json
{
    "success": true,
    "data": {}
}
```

Validation errors return HTTP `422`. Unauthorized requests return HTTP `401` or `403`. Conflict operations return HTTP `409`.

## 1. Authentication and Password Reset

### Register

```http
POST /register
Content-Type: application/json
```

Body:

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

Governorates:

```text
north, gaza, middle, khanyonis, rafah
```

The response returns a Sanctum token. New users start on the Standard plan.

### Login

```http
POST /login
Content-Type: application/json
```

Body:

```json
{
    "login": "ahmed_salem",
    "password": "password123"
}
```

`login` accepts either username or email.

### Logout

```http
POST /logout
Authorization: Bearer ACCESS_TOKEN
```

### Forgot Password

```http
POST /forgot-password
Content-Type: application/json
```

Body:

```json
{
    "email": "ahmed@example.com"
}
```

The API sends a reset notification if the email exists. The response intentionally does not reveal whether an email is registered.

### Reset Password

```http
POST /reset-password
Content-Type: application/json
```

Body:

```json
{
    "email": "ahmed@example.com",
    "token": "TOKEN_FROM_EMAIL",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

The token is valid for 60 minutes. Existing Sanctum tokens are revoked after a successful reset.

## 2. Profile

### Get Profile

```http
GET /profile
Authorization: Bearer ACCESS_TOKEN
```

Returns user information, verification status, current plan, product count, and profile statistics.

### Update Profile

```http
POST /profile
Authorization: Bearer ACCESS_TOKEN
Content-Type: multipart/form-data
```

Fields:

```text
full_name: string
username: string
email: email
phone: string
governorate: north|gaza|middle|khanyonis|rafah
district: string
password: optional, minimum 8 characters
avatar: optional image, jpeg/jpg/png/webp, maximum 2 MB
```

## 3. Products / Items

### List Active Products

```http
GET /products
```

Optional query parameters:

```text
search= camera
category= cameras
page= 1
```

Categories:

```text
cameras, clothes, electronics, items, camping, medical items,
instruments, books, house items
```

### Get Product Details

```http
GET /products/{product_id}
```

Only active products are returned publicly.

### List My Products

```http
GET /my-products
Authorization: ******
```

Returns only products created by the authenticated user. Unlike the public
`/products` endpoint, this endpoint also includes frozen products so the user
can manage their own listings. Updating, freezing, and deleting a product is
also restricted to its owner.

### Create Product

```http
POST /products
Authorization: Bearer ACCESS_TOKEN
Content-Type: multipart/form-data
```

Fields:

```text
title: required string
description: required string
category: required category
price_per_hour: required numeric, minimum 0
deposit_amount: required numeric, minimum 0
images[]: 1 to 4 images, jpeg/jpg/png/webp, maximum 2 MB each
available_dates[]: dates in Y-m-d format
start_time: optional H:i
end_time: optional H:i
is_all_day: optional boolean
```

The user's subscription monthly listing limit is checked before creation.

### Update Product

```http
PUT /products/{product_id}
Authorization: Bearer ACCESS_TOKEN
Content-Type: multipart/form-data
```

`POST` is also accepted for this endpoint. Only the owner can update the product.

### Freeze or Activate Product

```http
POST /products/{product_id}/toggle-status
Authorization: Bearer ACCESS_TOKEN
```

Toggles the product between `active` and `frozen`. A frozen product is hidden
from the public products list, but is not deleted and remains available through
the authenticated user's `/my-products` endpoint.

### Delete Product

```http
DELETE /products/{product_id}
Authorization: Bearer ACCESS_TOKEN
```

Only the owner can delete the product. Deletion is blocked with HTTP `409` if there is an accepted rental that is current or future.

## 4. Saved Items

### List Saved Items

```http
GET /saved-items
Authorization: Bearer ACCESS_TOKEN
```

### Save Product

```http
POST /saved-items
Authorization: Bearer ACCESS_TOKEN
Content-Type: application/json
```

Body:

```json
{
    "product_id": "PRODUCT_UUID"
}
```

### Remove Saved Product

```http
DELETE /saved-items/{product_id}
Authorization: Bearer ACCESS_TOKEN
```

### Toggle Saved State

```http
POST /products/{product_id}/toggle-save
Authorization: Bearer ACCESS_TOKEN
```

Returns `is_saved` as a boolean.

## 5. Rental Requests

### Create Rental Request

```http
POST /rental-requests
Authorization: Bearer ACCESS_TOKEN
Content-Type: application/json
```

Body:

```json
{
    "product_id": "PRODUCT_UUID",
    "start_time": "2026-09-01 10:00:00",
    "end_time": "2026-09-01 12:00:00"
}
```

Rules:

- The renter cannot rent their own product.
- The product must be active.
- The date range cannot overlap an accepted rental.
- The user's monthly rental limit is checked.
- New requests start with `owner_status = pending`.

### List My Renter Requests

```http
GET /rental-requests
Authorization: Bearer ACCESS_TOKEN
```

### List Requests Related to User

```http
GET /rental-requests/my
Authorization: Bearer ACCESS_TOKEN
```

Returns requests where the user is the renter or the product owner.

### Accept or Reject Request

```http
PATCH /rental-requests/{rental_request_id}/status
Authorization: Bearer ACCESS_TOKEN
Content-Type: application/json
```

Body:

```json
{
    "status": "accepted"
}
```

Allowed statuses:

```text
accepted, rejected
```

Only the product owner can perform this action.

### Cancel Accepted Rental

```http
PATCH /rental-requests/{rental_request_id}/cancel
Authorization: Bearer ACCESS_TOKEN
Content-Type: application/json
```

Body:

```json
{
    "reason": "لم أعد بحاجة إلى المنتج"
}
```

Cancellation is allowed only when:

- The current user is the renter.
- The request is accepted.
- The rental has not started.
- A verified payment exists.

The default cancellation fee is 20%. The rental becomes `cancelled`, the payment becomes `partially_refunded`, and the refund remains `pending` until the money is transferred.

## 6. Payments

### Submit Rental Payment Receipt

```http
POST /payments
Authorization: Bearer ACCESS_TOKEN
Content-Type: multipart/form-data
```

Fields:

```text
rental_id: required UUID
price_snapshot: required numeric
rental_price_total: required numeric
deposit_amount: required numeric
grand_total: required numeric
receipt_image: required image, jpeg/jpg/png/webp, maximum 2 MB
```

The payment starts as `pending`.

Payment statuses:

```text
pending, verified, failed, partially_refunded
```

### List My Payments

```http
GET /payments
Authorization: Bearer ACCESS_TOKEN
```

### Admin List Payments

```http
GET /admin/payments
Authorization: Bearer ADMIN_TOKEN
```

### Admin Verify Payment

```http
PATCH /admin/payments/{payment_id}/verify
Authorization: Bearer ADMIN_TOKEN
```

### Admin Reject Payment

```http
PATCH /admin/payments/{payment_id}/reject
Authorization: Bearer ADMIN_TOKEN
```

## 7. Notifications

### List Notifications

```http
GET /notifications
Authorization: Bearer ACCESS_TOKEN
```

### Unread Count

```http
GET /notifications/unread-count
Authorization: Bearer ACCESS_TOKEN
```

### Mark One as Read

```http
PATCH /notifications/{notification_id}/read
Authorization: Bearer ACCESS_TOKEN
```

### Mark All as Read

```http
PATCH /notifications/read-all
Authorization: Bearer ACCESS_TOKEN
```

Notifications are created for rental status, payments, identity verification, subscriptions, and plan expiry.

## 8. Identity Verification

The Colab model is intentionally excluded for now. Every uploaded request goes directly to manual review by an administrator.

### Submit Identity Verification

```http
POST /identity-verifications
Authorization: Bearer ACCESS_TOKEN
Content-Type: multipart/form-data
```

Fields:

```text
id_image: required image, jpeg/jpg/png/webp, maximum 5 MB
selfie_image: required image, jpeg/jpg/png/webp, maximum 5 MB
```

New requests start with `status = manual_review`. Images are stored privately and the request appears in the admin review list.

### Get Current Verification

```http
GET /identity-verifications/current
Authorization: Bearer ACCESS_TOKEN
```

Possible application statuses:

```text
manual_review, verified, approved, rejected
```

### Admin List Verifications

```http
GET /admin/identity-verifications
Authorization: Bearer ADMIN_TOKEN
```

Optional filter:

```text
?status=manual_review
```

### Admin Approve Verification

```http
PATCH /admin/identity-verifications/{verification_id}/approve
Authorization: Bearer ADMIN_TOKEN
```

### Admin Reject Verification

```http
PATCH /admin/identity-verifications/{verification_id}/reject
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json
```

Body:

```json
{
    "admin_note": "صورة الهوية غير واضحة"
}
```

## 9. Subscription Plans

### List Plans

```http
GET /subscription-plans
Authorization: Bearer ACCESS_TOKEN
```

Current seeded plans:

| Plan     | Price | Monthly Listings | Monthly Rentals | Commission |
| -------- | ----: | ---------------: | --------------: | ---------: |
| standard |  0.00 |                1 |               5 |        10% |
| plus     | 29.00 |                3 |              10 |         5% |
| pro      | 69.00 |                8 |              20 |       2.5% |

### Get Current Subscription

```http
GET /my-subscription
Authorization: Bearer ACCESS_TOKEN
```

### Submit Subscription Receipt

```http
POST /subscriptions
Authorization: Bearer ACCESS_TOKEN
Content-Type: multipart/form-data
```

Fields:

```text
plan_id: required UUID
receipt_image: required image, jpeg/jpg/png/webp, maximum 5 MB
```

A Standard plan does not require a subscription request. Paid requests start as `pending`.

### Admin List Subscriptions

```http
GET /admin/subscriptions
Authorization: Bearer ADMIN_TOKEN
```

Optional filter:

```text
?status=pending
```

### Admin Approve Subscription

```http
PATCH /admin/subscriptions/{subscription_id}/approve
Authorization: Bearer ADMIN_TOKEN
```

Approval:

- Activates the plan for one month.
- Starts the month at approval time.
- Expires any previous active subscription for that user.
- Updates the user's current `plan_id`.

### Admin Reject Subscription

```http
PATCH /admin/subscriptions/{subscription_id}/reject
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json
```

Body:

```json
{
    "admin_note": "الإيصال غير واضح"
}
```

## 10. Admin Dashboard

### Dashboard Summary

```http
GET /admin/dashboard
Authorization: Bearer ADMIN_TOKEN
```

Returns:

- Total users.
- Active products.
- Pending rental requests.
- Pending payments.
- Pending and active subscriptions.
- Manual identity reviews.
- Recent rental requests.
- Pending payments.
- Pending subscriptions.
- Identity reviews.

The current admin dashboard is an API endpoint. A visual dashboard UI is not included yet.

## 11. Admin Demo Account

Created or updated by `DemoDataSeeder`:

```text
Username: admin
Email: admin@mutah.test
Password: Admin@123456
Role: admin
```

Change this password before production deployment.

## 12. Useful Postman Variables

```text
base_url = http://localhost/Mutah/public/api/v1
access_token = user token
admin_token = admin token
product_id = product UUID
rental_request_id = rental request UUID
payment_id = payment UUID
subscription_id = subscription UUID
verification_id = identity verification UUID
notification_id = notification UUID
```

For file uploads, choose `Body > form-data` and set image fields to type `File`. Do not manually set the multipart boundary.

## 13. Operational Notes

Run migrations:

```bash
php artisan migrate
```

Seed plans and demo data:

```bash
php artisan db:seed
```

Run tests:

```bash
php artisan test
```

Run subscription expiry checks:

```bash
php artisan subscriptions:expire
```

The scheduler must run on the server for expiry notifications and automatic fallback to Standard:

```bash
php artisan schedule:run
```

Current test status at the time of this document:

```text
16 tests passed
59 assertions passed
```
