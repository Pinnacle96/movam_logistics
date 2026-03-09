## 1. Architecture design

```mermaid
graph TD
    A[User Browser] --> B[Vue 3 Frontend Application]
    B --> C[Pinia State Management]
    C --> D[API Layer]
    D --> E[Laravel Backend]
    E --> F[Supabase Database]
    E --> G[Mapbox API]
    E --> H[Payment Gateway]

    subgraph "Frontend Layer"
        B
        C
        D
    end

    subgraph "Backend Layer"
        E
    end

    subgraph "Data & External Services"
        F
        G
        H
    end
```

## 2. Technology Description

* **Frontend**: Vue 3 + Tailwind CSS + Pinia + Vite

* **Initialization Tool**: vite-init

* **Backend**: Laravel 11 (PHP)

* **Database**: Supabase (PostgreSQL)

* **Authentication**: Laravel Sanctum + Supabase Auth

* **Maps**: Mapbox API for geolocation and routing

* **Real-time**: Laravel Echo with Pusher

## 3. Route definitions

| Route               | Purpose                                         |
| ------------------- | ----------------------------------------------- |
| /                   | Customer home page with product browsing        |
| /login              | User authentication with role-based redirection |
| /admin/dashboard    | Admin system overview and user management       |
| /merchant/dashboard | Merchant product and order management           |
| /rider/dashboard    | Rider delivery management and earnings          |
| /customer/profile   | Customer profile and order history              |
| /cart               | Shopping cart and checkout process              |
| /orders/:id/track   | Real-time order tracking                        |
| /merchant/products  | Product catalog management                      |
| /merchant/orders    | Order processing interface                      |
| /rider/deliveries   | Available delivery requests                     |

## 4. API definitions

### 4.1 Authentication APIs

```
POST /api/auth/login
```

Request:

| Param Name | Param Type | isRequired | Description                               |
| ---------- | ---------- | ---------- | ----------------------------------------- |
| email      | string     | true       | User email address                        |
| password   | string     | true       | User password                             |
| role       | string     | true       | User role (admin/merchant/rider/customer) |

Response:

| Param Name | Param Type | Description                         |
| ---------- | ---------- | ----------------------------------- |
| token      | string     | JWT authentication token            |
| user       | object     | User profile data                   |
| role       | string     | User role for dashboard redirection |

Example:

```json
{
  "email": "merchant@example.com",
  "password": "securepassword123",
  "role": "merchant"
}
```

### 4.2 Order Management APIs

```
POST /api/orders/create
```

Request:

| Param Name        | Param Type | isRequired | Description                            |
| ----------------- | ---------- | ---------- | -------------------------------------- |
| items             | array      | true       | Array of product items with quantities |
| delivery\_address | object     | true       | Delivery address details               |
| payment\_method   | string     | true       | Payment method selection               |
| scheduled\_time   | string     | false      | Optional scheduled delivery time       |

### 4.3 Product APIs

```
GET /api/products
```

Query Parameters:

| Param Name   | Param Type | isRequired | Description                 |
| ------------ | ---------- | ---------- | --------------------------- |
| category     | string     | false      | Filter by category          |
| merchant\_id | string     | false      | Filter by specific merchant |
| search       | string     | false      | Search term for products    |
| page         | number     | false      | Pagination page number      |

## 5. Server architecture diagram

```mermaid
graph TD
    A[Client / Frontend] --> B[API Controller Layer]
    B --> C[Service Layer]
    C --> D[Repository Layer]
    D --> E[(Supabase Database)]
    C --> F[External Services]
    F --> G[Mapbox API]
    F --> H[Payment Gateway]
    F --> I[Notification Service]

    subgraph "Laravel Backend"
        B
        C
        D
    end

    subgraph "External Services"
        F
        G
        H
        I
    end
```

## 6. Data model

### 6.1 Data model definition

```mermaid
erDiagram
    USER ||--o{ ORDER : places
    USER ||--o{ MERCHANT : owns
    USER ||--o{ RIDER : works_as
    MERCHANT ||--o{ PRODUCT : offers
    ORDER ||--o{ ORDER_ITEM : contains
    PRODUCT ||--o{ ORDER_ITEM : included_in
    ORDER ||--o{ DELIVERY : assigned_to
    RIDER ||--o{ DELIVERY : handles
    USER ||--o{ WALLET : has
    ORDER ||--o{ TRANSACTION : generates

    USER {
        string id PK
        string email UK
        string password_hash
        string name
        string phone
        string role
        string status
        timestamp created_at
        timestamp updated_at
    }
    
    MERCHANT {
        string id PK
        string user_id FK
        string business_name
        string address
        string category
        boolean is_verified
        decimal rating
        timestamp created_at
    }
    
    PRODUCT {
        string id PK
        string merchant_id FK
        string name
        string description
        decimal price
        string image_url
        boolean is_available
        integer stock_quantity
        timestamp created_at
    }
    
    ORDER {
        string id PK
        string user_id FK
        string merchant_id FK
        string status
        decimal total_amount
        string delivery_address
        string payment_method
        timestamp order_time
        timestamp estimated_delivery
    }
    
    ORDER_ITEM {
        string id PK
        string order_id FK
        string product_id FK
        integer quantity
        decimal unit_price
        string special_instructions
    }
    
    DELIVERY {
        string id PK
        string order_id FK
        string rider_id FK
        string status
        string pickup_location
        string delivery_location
        decimal distance
        timestamp pickup_time
        timestamp delivery_time
    }
    
    WALLET {
        string id PK
        string user_id FK
        decimal balance
        string currency
        timestamp last_updated
    }
    
    TRANSACTION {
        string id PK
        string order_id FK
        string wallet_id FK
        decimal amount
        string type
        string status
        timestamp created_at
    }
```

### 6.2 Data Definition Language

User Table (users)

```sql
-- create table
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'merchant', 'rider', 'customer')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'deleted')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- create indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);

-- grant permissions
GRANT SELECT ON users TO anon;
GRANT ALL PRIVILEGES ON users TO authenticated;
```

Product Table (products)

```sql
-- create table
CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    merchant_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
    image_url VARCHAR(500),
    is_available BOOLEAN DEFAULT true,
    stock_quantity INTEGER DEFAULT 0 CHECK (stock_quantity >= 0),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- create indexes
CREATE INDEX idx_products_merchant_id ON products(merchant_id);
CREATE INDEX idx_products_available ON products(is_available);
CREATE INDEX idx_products_price ON products(price);

-- grant permissions
GRANT SELECT ON products TO anon;
GRANT ALL PRIVILEGES ON products TO authenticated;
```

Order Table (orders)

```sql
-- create table
CREATE TABLE orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL,
    merchant_id UUID NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'preparing', 'ready', 'assigned', 'picked_up', 'delivered', 'cancelled')),
    total_amount DECIMAL(10,2) NOT NULL CHECK (total_amount >= 0),
    delivery_address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    order_time TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    estimated_delivery TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- create indexes
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_merchant_id ON orders(merchant_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at DESC);

-- grant permissions
GRANT SELECT ON orders TO anon;
GRANT ALL PRIVILEGES ON orders TO authenticated;
```

Delivery Table (deliveries)

```sql
-- create table
CREATE TABLE deliveries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL UNIQUE,
    rider_id UUID,
    status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed')),
    pickup_location TEXT NOT NULL,
    delivery_location TEXT NOT NULL,
    distance DECIMAL(8,2),
    pickup_time TIMESTAMP WITH TIME ZONE,
    delivery_time TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- create indexes
CREATE INDEX idx_deliveries_order_id ON deliveries(order_id);
CREATE INDEX idx_deliveries_rider_id ON deliveries(rider_id);
CREATE INDEX idx_deliveries_status ON deliveries(status);

-- grant permissions
GRANT SELECT ON deliveries TO anon;
GRANT ALL PRIVILEGES ON deliveries TO authenticated;
```

