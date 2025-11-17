# Abandoned Cart Reminder System

A Laravel-based solution for sending automated email reminders to customers with abandoned shopping carts. The system sends three configurable email reminders and automatically stops when customers click the email link or finalize their order.

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- MySQL
- Redis

### Installation

1. **Clone and navigate**:
   ```bash
   cd laravel
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Set up environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure `.env`**:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/database/database.sqlite
   
   QUEUE_CONNECTION=redis
   CACHE_DRIVER=redis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   REDIS_CLIENT=phpredis
   
   CART_REMINDER_FIRST_HOURS=4
   CART_REMINDER_SECOND_HOURS=24
   CART_REMINDER_THIRD_HOURS=72
   CART_REMINDER_QUEUE=cart-reminders
   ```

5. **Install Redis**:
   ```bash
   # macOS
   brew install redis && redis-server
   
   # Ubuntu/Debian
   sudo apt-get install redis-server
   sudo systemctl start redis
   
   # Install PHP extension
   pecl install redis
   ```

6. **Run migrations**:
   ```bash
   php artisan migrate
   ```

### Running the Application

**Terminal 1 - Start queue worker**:
```bash
php artisan queue:work redis --queue=cart-reminders
```

**Terminal 2 - Start development server**:
```bash
php artisan serve
```

That's it! The application is now running. Reminders are automatically scheduled when carts are created.

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/CartReminderFlowTest.php
```

**Test Results**: 4 tests, 21 assertions - All passing ✅

Tests cover:
- All three reminders sent automatically
- Reminders stop when email is clicked
- Reminders stop when cart is finalized
- Reminders stop after second when email clicked

## Architecture

The application follows **Domain-Driven Design** with **Clean Architecture**, organized into three layers:

```
app/
├── Domain/           # Business logic (Entities, Value Objects, Services)
├── Application/      # Use cases (Commands, Handlers, Jobs)
└── Infrastructure/   # Framework-specific (Eloquent repositories)
```

This architecture ensures:
- ✅ Separation of concerns
- ✅ SOLID principles
- ✅ Testability
- ✅ Maintainability

## Configuration

### Reminder Intervals

Configure in `.env`:

```env
CART_REMINDER_FIRST_HOURS=4    # Hours after cart creation
CART_REMINDER_SECOND_HOURS=24  # Hours after first reminder
CART_REMINDER_THIRD_HOURS=72   # Hours after second reminder
```

### Redis

Redis is used for:
- **Queue processing**: Background jobs for email reminders
- **Caching**: Performance optimization

To switch to database queue/cache, change in `.env`:
```env
QUEUE_CONNECTION=database
CACHE_STORE=database
```

## Usage

### Creating a Cart

```php
use App\Application\Cart\Commands\CreateCartCommand;
use App\Application\Cart\Handlers\CreateCartHandler;

$handler = app(CreateCartHandler::class);
$cart = $handler(new CreateCartCommand(
    userId: 123,
    email: 'customer@example.com'
));
// First reminder automatically scheduled
```

### Adding Items

```php
use App\Application\Cart\Commands\AddCartItemCommand;
use App\Application\Cart\Handlers\AddCartItemHandler;

$handler = app(AddCartItemHandler::class);
$handler(new AddCartItemCommand(
    cartId: $cart->getId(),
    productId: 101,
    quantity: 2
));
```

### Marking Email Clicked

```php
use App\Application\Cart\Commands\MarkCartEmailClickedCommand;
use App\Application\Cart\Handlers\MarkCartEmailClickedHandler;

$handler = app(MarkCartEmailClickedHandler::class);
$handler(new MarkCartEmailClickedCommand(cartId: $cart->getId()));
// All future reminders cancelled
```

### Finalizing Cart

```php
use App\Application\Cart\Commands\FinalizeCartCommand;
use App\Application\Cart\Handlers\FinalizeCartHandler;

$handler = app(FinalizeCartHandler::class);
$handler(new FinalizeCartCommand(cartId: $cart->getId()));
// Cart finalized, reminders stopped
```

## How It Works

1. **Cart Created** → First reminder scheduled via Redis queue (X hours later)
2. **First Reminder Sent** → Second reminder scheduled (Y hours later)
3. **Second Reminder Sent** → Third reminder scheduled (Z hours later)
4. **Email Clicked** → All future reminders cancelled
5. **Cart Finalized** → All reminders stopped

## Monitoring

- **Queue Jobs**: Monitor via `failed_jobs` table or Laravel Horizon
- **Logs**: Check `storage/logs/laravel.log`
- **Database**: Track reminder status via `carts` table columns:
  - `first_reminder_sent_at`
  - `second_reminder_sent_at`
  - `third_reminder_sent_at`
  - `email_clicked_at`
  - `finalized_at`

## Technology Stack

- **PHP**: 8.2+
- **Laravel**: 12
- **Database**: SQLite (dev), MySQL/PostgreSQL (production)
- **Queue**: Redis
- **Cache**: Redis
- **Testing**: PHPUnit

## Project Structure

```
laravel/
├── app/
│   ├── Application/Cart/      # Use cases (Commands, Handlers, Jobs)
│   ├── Domain/Cart/           # Business logic (Entities, Services)
│   └── Infrastructure/        # Database implementations
├── config/cart.php            # Configuration
├── database/migrations/       # Database schema
└── tests/Feature/             # Feature tests
```

## License

MIT License
