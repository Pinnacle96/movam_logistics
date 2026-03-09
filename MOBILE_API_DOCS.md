# Mobile App API Documentation (New Features)

This document summarizes the new API endpoints implemented to support the mobile application features (Reviews, Referrals, Address Book, Notifications, and Configuration).

## 1. App Configuration (On Launch)
Call this endpoint immediately when the app launches to check for updates, maintenance mode, and feature flags.

- **GET** `/api/config`
- **Response:**
  ```json
  {
      "android": {
          "min_version": "1.0.0",
          "latest_version": "1.0.0",
          "force_update": false,
          "store_url": "..."
      },
      "ios": { ... },
      "maintenance_mode": false,
      "support": {
          "phone": "+234...",
          "email": "support@movam.com",
          "whatsapp": "..."
      },
      "features": {
          "referrals_enabled": true,
          "wallet_enabled": true
      }
  }
  ```

## 2. Ratings & Reviews
Customers can rate Merchants and Riders after an order is `delivered`.

- **POST** `/api/reviews` (Auth: Customer)
  - Body:
    ```json
    {
        "order_id": 1,
        "type": "merchant", // or "rider"
        "rating": 5, // 1-5
        "comment": "Great service!"
    }
    ```
- **GET** `/api/reviews/{type}/{id}` (Public)
  - `type`: `merchant` or `rider`
  - `id`: Merchant ID or Rider ID (not User ID)
  - Returns paginated reviews.

## 3. Referral System
Users can share their referral code and see their referral history.

- **GET** `/api/referrals` (Auth: Any)
  - Returns:
    ```json
    {
        "referral_code": "REF123",
        "total_referrals": 5,
        "referrals": [ ...list of referred users... ]
    }
    ```
- **Registration**:
  - Add `referral_code` (optional) to `/api/register/customer`, `/api/register/merchant`, or `/api/register/rider`.

## 4. Address Book
Customers can save multiple addresses (Home, Work, etc.).

- **GET** `/api/addresses` (List all)
- **POST** `/api/addresses` (Create)
  - Body:
    ```json
    {
        "label": "Home",
        "address": "123 Main St",
        "lat": 6.5244,
        "lng": 3.3792,
        "is_default": true
    }
    ```
- **PUT** `/api/addresses/{id}` (Update)
- **DELETE** `/api/addresses/{id}` (Delete)

**Usage in Order:**
- When placing an order (`POST /api/orders`), you can now send `address_id` instead of `delivery_address`, `delivery_lat`, and `delivery_lng`.
  ```json
  {
      "items": [...],
      "address_id": 5 // Use saved address ID
  }
  ```

## 5. Notification Center
In-app notification history.

- **GET** `/api/notifications` (List history)
- **GET** `/api/notifications/unread-count` (Get badge count)
- **POST** `/api/notifications/mark-all-read`
- **POST** `/api/notifications/{id}/read` (Mark single as read)

## 6. Admin Broadcast
Admin can send push notifications to all users.

- **POST** `/api/admin/notifications/broadcast` (Auth: Admin)
  - Body:
    ```json
    {
        "title": "Promo Alert",
        "body": "50% off delivery today!",
        "target_role": "all" // or "customer", "merchant", "rider"
    }
    ```
