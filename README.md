# Vayka Backend 🏨

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-4.x-orange.svg)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

The robust backend engine powering **Vayka**, a premium hotel discovery and booking platform. Built with a focus on security, scalability, and modern role-based management.

---

## 🚀 Key Features

- **Multi-Role Management**: Comprehensive Admin, Hotel Owner, and Staff panels built with [Filament v4](https://filamentphp.com).
- **Secure Authentication**: API security via [Laravel Sanctum](https://laravel.com/docs/sanctum) with integrated OTP (One-Time Password) verification.
- **Smart Booking Engine**: Real-time availability tracking and automated booking workflows.
- **Payment Processing**: Secure transactions and partial refunds integrated via [Stripe](https://stripe.com).
- **Partner System**: Complete onboarding flow for new hotels, including document verification and admin approval.
- **Real-time Notifications**: Integrated with **Firebase (FCM)** for push notifications and **Gmail API** for transactional emails.
- **AI-Powered Insights**: Leveraging **OpenAI** for intelligent property analysis and user recommendations.

## 🛠️ Technology Stack

- **Core**: Laravel 12.x
- **Admin Infrastructure**: Filament 4 (Panels, Tables, Infolists)
- **Database**: MySQL 8+
- **Security**: Sanctum, Hashed Passwords, Role-Based Access Control (RBAC)
- **Email**: Google OAuth2 / Gmail API Integration
- **Analytics**: Revenue Trends and Booking Statistics using `flowframe/laravel-trend`
- **Mapping**: Leaflet & Google Maps integration for property locations

## 📋 Installation

1. **Clone & Install**:
   ```bash
   composer install
   ```
2. **Setup Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. **Run Migrations**:
   ```bash
   php artisan migrate --seed
   ```
4. **Storage Link**:
   ```bash
   php artisan storage:link
   ```
5. **Start Server**:
   ```bash
   php artisan serve
   ```

## 🛠️ Core Commands

- `php artisan filament:upgrade`: Keep Filament assets up to date.
- `php artisan tinker`: Interactive shell for rapid debugging.
- `php vendor/google/apiclient-services/autoload.php`: Optimize Google Services (keep Gmail only).

---

© 2026 Vayka Platform. All rights reserved.
