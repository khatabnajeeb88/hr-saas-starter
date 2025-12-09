# SaaS Starter Pack

A powerful, feature-rich boilerplate designed to jumpstart your SaaS application development. Built with modern technologies like **Symfony 8**, **PHP 8.4**, **Docker**, and **Tailwind CSS**, this starter pack provides a robust foundation for scalable web applications.

## 🚀 Features

-   **Modern Tech Stack**: PHP 8.4, Symfony 8.0, and API Platform 4.2.
-   **Dockerized Environment**: Fully managed development environment with MySQL, Redis, Mailpit, and phpMyAdmin.
-   **Authentication**: Secure JWT authentication and role-based access control.
-   **Multi-Tenancy**: Built-in support for teams and tenant isolation.
-   **Subscriptions**: Subscription management system (Plans, Features) ready for Stripe integration.
-   **Admin Dashboard**: EasyAdmin integration for effortless backend management.
-   **Asset Management**: Webpack Encore with Tailwind CSS for modern, responsive UI.
-   **Internationalization**: Multi-language support (English, Arabic, French) with URL localized routing.
-   **CI/CD**: GitHub Actions workflow for automated testing and building.

## 🛠️ Technology Stack

-   **Backend**: PHP 8.4, Symfony 8
-   **Database**: MySQL 5.7
-   **Cache/Queue**: Redis
-   **Frontend**: Twig, Stimulus, Turbo, Tailwind CSS
-   **API**: API Platform
-   **Dev Tools**: Docker, Mailpit, phpMyAdmin

## 📋 Prerequisites

Ensure you have the following installed on your machine:

-   [Docker](https://www.docker.com/products/docker-desktop)
-   [Docker Compose](https://docs.docker.com/compose/install/)
-   **Node.js** v20+ (for local asset building)
-   **PHP** 8.4+ (required only if running accessing `bin/console` outside Docker)

## ⚡ Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/khatabnajeeb88/saas-starter-pack.git
cd saas-starter-pack
```

### 2. Configure Environment
Copy the example environment file:
```bash
cp .env .env.local
```
Update `.env.local` if you need to customize ports or credentials.

### 3. Start Docker Containers
Build and start the services:
```bash
docker-compose up -d --build
```

### 4. Database Setup
Run migrations to set up the schema:
```bash
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```
(Optional) Seed default subscription data:
```bash
docker-compose exec php php bin/console app:subscription:seed
```

### 5. Build Assets (Frontend)
Install dependencies and build assets:
```bash
npm install
npm run build
```
For development with hot reload:
```bash
npm run watch
```

## 📖 Usage

### Accessing Services
-   **Web App**: [http://localhost:8000](http://localhost:8000)
-   **API**: [http://localhost:8000/api/v1](http://localhost:8000/api/v1)
-   **TopAdmin (phpMyAdmin)**: [http://localhost:8080](http://localhost:8080) (User: `root`, Pass: `root`)
-   **Mailpit (Email Testing)**: [http://localhost:8025](http://localhost:8025)

### Admin Panel
Access the EasyAdmin dashboard at `/admin`.
*Note: You must have `ROLE_ADMIN` to access this area.*

### Subscriptions
Refer to [SUBSCRIPTION_SETUP.md](SUBSCRIPTION_SETUP.md) for detailed instructions on configuring plans, features, and integrating payments.

## 🧪 Testing

The project includes PHPUnit for backend testing.

**Run All Tests**:
```bash
docker-compose exec php php bin/phpunit
```

**Run Specific Test Suite**:
```bash
docker-compose exec php php bin/phpunit --testsuite=E2E
```

## 📦 Deployment

The project is configured for continuous integration via GitHub Actions (`.github/workflows/ci.yml`).
For production deployment:
1.  Ensure `.env` variables are set for production (APP_ENV=prod).
2.  Use a production-ready database (e.g., RDS, Managed MySQL).
3.  Configure a reverse proxy (Nginx/Apache) to serve the application.
4.  Run `composer install --no-dev --optimize-autoloader`.
5.  Run `npm run build`.

## 📄 License
Allows for personal and commercial use.
