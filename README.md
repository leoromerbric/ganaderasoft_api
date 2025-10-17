# GanaderaSoft API Gateway

Laravel API Gateway for the GanaderaSoft livestock management system, built with PHP 8.1 and following Laravel best practices.

## Features

### Core API Structure
- **RESTful API endpoints** using Laravel 10 framework
- **Authentication system** with Laravel Sanctum for token-based auth
- **Modular architecture** designed for easy extension with new features
- **Permission-based access control** with admin and propietario (owner) roles

### Database Integration
- Uses the existing MySQL database schema from `ganaderasoft-28-5-25 - corregido.sql`
- **Eloquent models** for Users, Propietarios, and Fincas with proper relationships
- **Database migrations** compatible with existing structure

### API Endpoints

**Authentication:**
- `POST /api/auth/register` - Register new users
- `POST /api/auth/login` - User authentication
- `GET /api/auth/profile` - Get user profile (authenticated)
- `POST /api/auth/logout` - Logout and revoke token

**Finca Management (authenticated):**
- `GET /api/fincas` - List farms (filtered by user permissions)
- `POST /api/fincas` - Create new farm
- `GET /api/fincas/{id}` - Get farm details
- `PUT /api/fincas/{id}` - Update farm
- `DELETE /api/fincas/{id}` - Soft delete farm

**System:**
- `GET /api/health` - API health check

## Docker Deployment

### Multi-container setup with Docker Compose
- **PHP 8.1 with FPM** in production-ready container
- **nginx** as web server for optimal performance
- **MySQL 8.0** database with automatic schema import
- **PHPMyAdmin** for database management
- All services properly networked and configured

### Usage

Deploy the complete system with:
```bash
docker compose up -d
```

Access points:
- API: http://localhost:8000
- PHPMyAdmin: http://localhost:8080
- MySQL: localhost:3306

## Architecture

The system uses a modern nginx + PHP-FPM architecture:
- **nginx** handles static files and proxies PHP requests
- **PHP-FPM** processes PHP requests efficiently
- **MySQL** provides reliable data storage
- **Laravel Sanctum** manages API authentication

## Security & Validation
- **Input validation** for all endpoints
- **CSRF protection** for web routes
- **Rate limiting** on API endpoints
- **Proper error handling** with consistent JSON responses

## Project Structure

```
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php      # Authentication logic
│   │   └── FincaController.php     # Farm CRUD operations
│   └── Models/
│       ├── User.php                # User model with relationships
│       ├── Propietario.php         # Farm owner model
│       └── Finca.php              # Farm model
├── Dockerfile                      # PHP 8.1 + FPM container
├── nginx.conf                      # nginx configuration
├── docker-compose.yml              # Multi-service orchestration
└── README.md                       # This documentation
```

The system is production-ready and follows Laravel conventions for easy maintenance and extension.
