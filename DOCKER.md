# Docker Setup for SaaS Starter Pack

This project uses Docker for local development with the following services:
- PHP 8.4-FPM
- Nginx
- PostgreSQL 16
- Redis 7
- Mailpit (for email testing)

## Prerequisites
- Docker
- Docker Compose

## Getting Started

1. **Build and start the containers:**
   ```bash
   docker-compose up -d --build
   ```

2. **Install dependencies (if not done during build):**
   ```bash
   docker-compose exec php composer install
   ```

3. **Run database migrations:**
   ```bash
   docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
   ```

4. **Access the application:**
   - Application: http://localhost:8000
   - Mailpit (email testing): http://localhost:8025

## Useful Commands

### View logs
```bash
docker-compose logs -f
```

### Access PHP container
```bash
docker-compose exec php bash
```

### Run Symfony commands
```bash
docker-compose exec php php bin/console [command]
```

### Stop containers
```bash
docker-compose down
```

### Stop and remove volumes (clean slate)
```bash
docker-compose down -v
```

## Services

- **PHP**: Port 9000 (internal)
- **Nginx**: Port 8000 (http://localhost:8000)
- **PostgreSQL**: Port 5432
- **Redis**: Port 6379
- **Mailpit SMTP**: Port 1025
- **Mailpit Web UI**: Port 8025 (http://localhost:8025)

## Environment Variables

The following environment variables are configured in `docker-compose.yml`:
- `DATABASE_URL`: PostgreSQL connection
- `MESSENGER_TRANSPORT_DSN`: Redis for Symfony Messenger
- `MAILER_DSN`: Mailpit for email testing
