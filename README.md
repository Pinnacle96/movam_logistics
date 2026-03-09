# Movam Logistics Platform

Movam is a production-ready, high-concurrency logistics and delivery platform designed for modern marketplaces. It provides a seamless experience for customers, merchants, and riders, powered by a robust backend and real-time tracking capabilities.

## 🚚 Platform Overview

- **Backend API:** Laravel 12 with Octane for high-concurrency handling.
- **Admin Console:** Vue.js 3 + Tailwind CSS dashboard for total platform management.
- **Customer Features:** Prepaid ordering, real-time tracking, wallet system, and referrals.
- **Merchant Features:** Order fulfillment, inventory management, and revenue analytics.
- **Rider Features:** Assignment dispatch, Mapbox navigation, and earnings withdrawals.

## 🛠 Technology Stack

- **Framework:** Laravel 12.x
- **Concurrency:** Laravel Octane (Swoole/RoadRunner)
- **Real-time:** Laravel Reverb / WebSockets
- **Authentication:** Laravel Sanctum (SPA/Mobile)
- **Database:** MySQL 8.0+ / PostgreSQL
- **Caching & Queues:** Redis & RabbitMQ
- **Frontend:** Vue.js 3 (Composition API), Pinia, Tailwind CSS
- **Maps:** Mapbox SDK integration

## ✨ Core Features

### 💰 Wallet & Payment System
- **Prepaid Only:** Ensures revenue is captured before delivery.
- **Automatic Fund Splitting:** Commissions, delivery fees, and merchant payouts are handled automatically upon delivery.
- **Withdrawals:** Secure withdrawal system for riders and merchants with OTP verification.

### 🔗 Advanced Referral System
- **Model-based Tracking:** Dedicated `Referral` model for tracking status (pending, completed, paid) and reward amounts.
- **Unique Referral Codes:** Automatic generation of user-specific invite codes.

### 📍 Real-time Logistics
- **Dynamic Pricing:** Delivery fees calculated via Mapbox distance matrix.
- **Order Tracking:** Live location updates for customers.
- **Algorithmic Dispatch:** Efficient assignment of orders to nearby riders.

### 🔔 Notification Center
- **Multi-channel:** Support for Push (FCM), Email, and In-app notifications.
- **Admin Broadcast:** Ability to send platform-wide announcements.

## 🚀 Getting Started

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Pinnacle96/movam_logistics.git
   cd movam
   ```

2. **Environment Setup:**
   ```bash
   cp .env.example .env
   # Configure your database and Mapbox/Paystack keys
   ```

3. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

4. **Database Initialization:**
   ```bash
   php artisan migrate --seed
   ```

5. **Run Development Server:**
   ```bash
   php artisan dev
   ```

---

## 👨‍💻 Credits & Developer Info

This project is developed and maintained by **Pinnacle Tech Hub**.

- **Organization:** [Pinnacle Tech Hub](https://pinnacletechhub.com.ng)
- **Lead Developer:** Noah Abayomi
- **Phone:** [+234 703 207 8859](tel:+2347032078859)
- **Email:** [info.pinnacletechhub@gmail.com](mailto:info.pinnacletechhub@gmail.com)
- **Website:** [Pinnacletechhub.com.ng](https://pinnacletechhub.com.ng)

---

## 📄 License

The Movam platform is proprietary software. All rights reserved.
