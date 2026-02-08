# Agri Marketplace Platform

A mobile-first agricultural brokerage platform where farmers post produce listings, buyers post purchase requests, and admins act as brokers to facilitate deals with escrow payments.

## 📚 Documentation

- **[Managed Marketplace Architecture](./MANAGED_MARKETPLACE.md)** - Complete guide to the managed marketplace model
- **[Phase 2 Implementation](./PHASE2_IMPLEMENTATION.md)** - Details on deal workflow and payment creation
- **[Phase 1 Implementation](./PHASE1_IMPLEMENTATION.md)** - Initial platform setup and models
- **[Migration Guide](./MIGRATION_GUIDE.md)** - Upgrade path from old to new systems

## Architecture

- **Backend**: Laravel 12 with PHP 8.4, PostgreSQL database, JWT authentication
- **Frontend**: Flutter Android app with Provider state management
- **Infrastructure**: Docker containerization, RESTful API

## Features

### Core Functionality
- **User Management**: Role-based authentication (farmer/buyer/admin/agent)
- **Managed Marketplace**: Admin-driven deal creation with buyer/farmer confirmation workflow
- **Broker Operations**: Admins match supply/demand, create deals, manage escrow
- **Transaction Management**: Secure payments with escrow system
- **Logistics**: Delivery tracking and verification
- **Dispute Resolution**: Admin-mediated conflict resolution

### Technical Features
- JWT-based authentication with role middleware
- RESTful API with pagination and filtering
- Real-time marketplace data
- Audit logging for all operations
- Mobile-first responsive design
- Automatic payment creation and escrow management
- Multi-stage deal confirmation workflow

## Quick Start

### Prerequisites
- Docker and Docker Compose
- Flutter SDK (for mobile development)
- Android Studio (for Android development)

### Backend Setup

1. **Clone and navigate to backend directory**
   ```bash
   cd backend
   ```

2. **Start Docker environment**
   ```bash
   docker-compose up -d
   ```

3. **Install PHP dependencies**
   ```bash
   docker-compose exec app composer install
   ```

4. **Generate application key**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

5. **Run database migrations**
   ```bash
   docker-compose exec app php artisan migrate
   ```

6. **Seed initial data**
   ```bash
   docker-compose exec app php artisan db:seed
   ```

7. **Run tests**
   ```bash
   docker-compose exec app php artisan test
   ```

### Mobile App Setup

1. **Navigate to Flutter app directory**
   ```bash
   cd flutter_app
   ```

2. **Install Flutter dependencies**
   ```bash
   flutter pub get
   ```

3. **Update API base URL** (if needed)
   - Edit `lib/services/api_service.dart`
   - Change `baseUrl` to match your backend URL

4. **Run on Android emulator**
   ```bash
   flutter run
   ```

## API Endpoints

### Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout

### Marketplace
- `GET /api/products` - List all products
- `GET /api/farmer-listings` - List farmer listings (with filters)
- `POST /api/farmer-listings` - Create farmer listing
- `GET /api/buyer-requests` - List buyer requests (with filters)
- `POST /api/buyer-requests` - Create buyer request

### Admin Operations
- `GET /admin/dashboard` - Dashboard statistics
- `POST /admin/deals` - Create deal between listing and request
- `PUT /admin/deals/{id}/status` - Update deal status
- `POST /admin/transactions/{id}/release` - Release escrow funds
- `POST /admin/transactions/{id}/refund` - Refund escrow funds

## Database Schema

### Core Tables
- `users` - User accounts with roles
- `products` - Available produce types
- `farmer_listings` - Supply side listings
- `buyer_requests` - Demand side requests
- `deals` - Matched supply/demand with negotiation
- `transactions` - Payment transactions with escrow
- `audit_logs` - Complete audit trail

### Supporting Tables
- `logistics_jobs` - Delivery assignments
- `delivery_verifications` - Delivery confirmations
- `disputes` - Conflict resolution records

## Development Workflow

1. **Backend Development**
   - Make changes in `backend/` directory
   - Run tests: `docker-compose exec app php artisan test`
   - Update API documentation

2. **Mobile Development**
   - Make changes in `flutter_app/` directory
   - Test on Android emulator: `flutter run`
   - Update API service for new endpoints

3. **Database Changes**
   - Create migrations: `php artisan make:migration`
   - Update models and relationships
   - Run migrations: `php artisan migrate`

## Production Deployment

### Environment Setup
1. Set up production PostgreSQL database
2. Configure environment variables in `.env`
3. Set `APP_ENV=production`
4. Configure proper JWT secrets
5. Set up SSL certificates

### Docker Production
```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.prod
    environment:
      - APP_ENV=production
  db:
    # Production PostgreSQL config
  nginx:
    # Production web server
```

### Security Considerations
- Use strong JWT secrets
- Enable HTTPS in production
- Configure CORS properly
- Implement rate limiting
- Set up proper logging and monitoring

## Testing

### Backend Tests
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=AuthTest

# Run with coverage
php artisan test --coverage
```

### Mobile Tests
```bash
# Run Flutter tests
flutter test

# Run integration tests
flutter drive --target=test_driver/app.dart
```

## Contributing

1. Fork the repository
2. Create feature branch
3. Make changes with tests
4. Ensure all tests pass
5. Submit pull request

## License

This project is licensed under the MIT License.