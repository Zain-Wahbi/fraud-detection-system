# Fraud Detection Dashboard (Laravel)

Bilingual (Arabic/English) operations dashboard for monitoring and simulating transactions scored by the [ML API](../ml-api/README.md).

## Features

- Live transaction simulation
- Chart.js-based fraud analytics
- Transaction blocklist management
- Full Arabic (RTL) and English UI

## Setup

```bash
cd laravel-dashboard
composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate --seed
```

## Running

```bash
php artisan serve
npm run dev
```

## Connecting to the ML API

Set the following in your `.env` file so the dashboard can reach the FastAPI service:

```
FRAUD_API_URL=http://localhost:8001
```

Make sure the [ML API](../ml-api/README.md) is running before using the live simulation or scoring features.
