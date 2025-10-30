# Caseer Academy Support System

A Laravel-based support ticketing system for Caseer Academy that automatically creates support tickets from order webhooks and integrates with the main Caseer Academy API for student management.

---

## 🎯 Features

### Dashboard & Analytics
- ✅ Real-time ticket statistics (total, today, by status)
- ✅ Visual charts showing ticket distribution
- ✅ Recent tickets widget with quick actions
- ✅ Customer and team member counters

### Order & Ticket Management
- ✅ Automatic ticket creation from order webhooks
- ✅ Support for regular, guest, and phone-less orders
- ✅ Full ticket CRUD with priority and status tracking
- ✅ Ticket notes and attachments
- ✅ Assign tickets to support team members

### Student Management (API-Driven)
- ✅ View latest students from Caseer Academy
- ✅ Search students by name, email, or username
- ✅ Create new student accounts
- ✅ View student details and enrolled courses
- ✅ Reset student passwords remotely
- ✅ Persistent search results

### Team Management
- ✅ Create and manage support team members
- ✅ Secure password hashing and management
- ✅ Reset user passwords
- ✅ Role-based access control

### Customer Management
- ✅ Auto-created from order webhooks
- ✅ Linked to support tickets
- ✅ Metadata tracking for guest orders

---

## 🛠️ Tech Stack

- **Framework:** Laravel 12
- **Admin Panel:** Filament PHP v3.3
- **Database:** SQLite (easily switchable to MySQL/PostgreSQL)
- **PHP:** 8.4+
- **Authentication:** Laravel Sanctum
- **Code Quality:** Laravel Pint

---

## 📦 Installation

### 1. Clone & Install
```bash
cd /Users/caseer/Sites/whatsapp
composer install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup
```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# Create admin user
php artisan make:filament-user
```

### 4. Start Development Server
```bash
php artisan serve
```

Access admin panel at: `http://localhost:8000/admin`

---

## 🔗 API Integration

### Caseer Academy API Configuration

Configure in Filament Admin → Student Management settings or directly in code:

```php
// CaseerAcademyService.php uses:
Base URL: https://caseer.academy/wp-json/my-app/v1
Authentication: X-Secret-Key: C@533r3c
```

### Order Webhook Setup

Configure your main Caseer Academy website to send order webhooks to:

**Local Development (with ngrok):**
```
https://your-ngrok-url.ngrok-free.dev/webhook/order-status
```

**Production:**
```
https://your-domain.com/webhook/order-status
```

#### Webhook Payload Format
```json
{
  "event": "order_status_changed",
  "order_id": 12345,
  "old_status": "pending",
  "new_status": "completed",
  "total": "99.99",
  "currency": "USD",
  "customer": {
    "id": 12345,
    "email": "customer@example.com",
    "phone": "252638888872",
    "name": "John Doe"
  },
  "items": [
    {
      "name": "Laravel Masterclass",
      "quantity": 1
    }
  ]
}
```

**Supported Scenarios:**
- ✅ Regular orders with full customer info
- ✅ Guest orders (customer.id = 0, email = "")
- ✅ Orders without phone numbers

---

## 📡 Available API Endpoints

### Student Management
```
GET  /api/students              - List latest 10 students
GET  /api/students/search       - Search students (?search_term=...)
POST /api/students              - Create new student
GET  /api/students/{id}         - Get student details
POST /api/students/{id}/password - Reset student password
```

### Tickets
```
POST /api/tickets/from-order    - Create ticket from external system
```

All endpoints require `X-Secret-Key` header for authentication.

---

## 🗂️ Project Structure

```
app/
├── Filament/
│   ├── Resources/
│   │   ├── CustomerResource.php     # Customer management
│   │   ├── TicketResource.php       # Ticket system
│   │   └── StudentResource.php      # Student management (API-driven)
│   └── Pages/
│       └── (No custom pages currently)
├── Http/Controllers/
│   ├── Api/
│   │   ├── StudentController.php    # Student API endpoints
│   │   └── TicketController.php     # Ticket API endpoints
│   └── OrderWebhookController.php   # Order webhook handler
├── Models/
│   ├── Customer.php                 # Order customers
│   ├── Ticket.php                   # Support tickets
│   ├── Student.php                  # API data wrapper
│   └── User.php                     # Admin users
└── Services/
    └── CaseerAcademyService.php     # Caseer Academy API client

database/
├── migrations/
│   ├── create_customers_table.php
│   ├── create_tickets_table.php
│   └── add_course_name_to_tickets_table.php
└── factories/
    ├── CustomerFactory.php
    └── TicketFactory.php
```

---

## 🧪 Testing

### Run All Tests
```bash
php artisan test
```

**Current Status:** ✅ 2/2 tests passing

### Manual Testing
```bash
# Test order webhook
curl -X POST http://localhost:8000/webhook/order-status \
  -H "Content-Type: application/json" \
  -d '{
    "event": "order_status_changed",
    "order_id": 12345,
    "new_status": "completed",
    "customer": {
      "id": 999,
      "email": "test@example.com",
      "name": "Test User",
      "phone": "252638888872"
    },
    "items": [{"name": "Test Course", "quantity": 1}]
  }'
```

---

## 🔧 Development Tools

### ngrok (for webhook testing)
```bash
# Start ngrok tunnel
ngrok http 8000

# Get public URL
curl -s http://localhost:4040/api/tunnels | grep -o 'https://[^"]*ngrok[^"]*'
```

**Current ngrok URL:**
```
https://unintrusive-tifany-imputedly.ngrok-free.dev
```

### Laravel Artisan
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check routes
php artisan route:list

# Tinker (database queries)
php artisan tinker
```

---

## 📊 Database Records (Current State)

After cleanup and testing:
- **Customers:** 2
  - Test Customer (from regular order)
  - Guest Order #15478 (from guest checkout)
- **Tickets:** 2
  - Laravel Masterclass ticket
  - Cloud Computing 101 ticket (guest)

---

## 🎓 Student Management Integration

The system integrates with Caseer Academy's WordPress API to:

1. **View Students** - Display latest 10 students
2. **Search** - Find students by username, email, or name
3. **Create** - Add new student accounts
4. **View Details** - See registration date and enrolled courses
5. **Reset Password** - Change student passwords remotely

All student data is fetched in real-time from the main website API.

---

## 🚀 Production Deployment

### Environment Variables
```env
APP_URL=https://support.caseer.academy
DB_CONNECTION=mysql  # or sqlite
DB_DATABASE=caseer_support

# Caseer Academy API (configure in Filament Settings)
CASEER_API_URL=https://caseer.academy/wp-json/my-app/v1
CASEER_API_SECRET=C@533r3c
```

### Deploy Steps
1. Set up production database
2. Run migrations: `php artisan migrate --force`
3. Create admin user: `php artisan make:filament-user`
4. Configure webhook URL on main website
5. Set up proper SSL certificate
6. Configure Caseer Academy API credentials in Settings

---

## 📖 Documentation

### Quick Start
- **[Quick Start Guide](docs/QUICK_START.md)** - Get started with new features
- **[Features Overview](docs/FEATURES_UPDATE.md)** - Detailed feature documentation
- **[Implementation Summary](docs/IMPLEMENTATION_SUMMARY.md)** - Technical implementation details

### Deployment
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Complete production deployment guide
- **[Integration Guide](docs/INTEGRATION.md)** - API integration instructions
- **`deploy.sh`** - Automated deployment script

### Historical Documentation
Historical project documents are archived in `docs/archive/` for reference.

---

## 🚀 Quick Commands

```bash
# Development
php artisan serve                    # Start dev server
php artisan make:filament-user       # Create admin user
php artisan test                     # Run tests

# Production Deployment
./deploy.sh                          # Automated deployment

# Maintenance
php artisan optimize:clear           # Clear all caches
php artisan filament:optimize        # Optimize Filament
```

---

## 👥 Team Access

### Admin Login
- **URL:** `http://localhost:8000/admin`
- **Default User:** Created during setup with `php artisan make:filament-user`

### Navigation
- **Dashboard** → Overview with stats and charts
- **Support** → Tickets (ticket management with notes and attachments)
- **Support** → Customers (order customers)
- **Student Management** → Students (Caseer Academy integration)
- **System** → Team Members (user management)
- **System** → Settings (API configuration)

---

## 🐛 Troubleshooting

### Issue: Webhook returns 500 error
**Solution:** Check `storage/logs/laravel.log` for detailed errors

### Issue: Student data not showing
**Solution:** Verify Caseer Academy API credentials and network connectivity

### Issue: ngrok not working
**Solution:** Restart ngrok: `ngrok http 8000`

---

## 📞 Support

For development questions, refer to:
- Laravel 12 Documentation
- Filament PHP Documentation
- `documentation.html` for API specs

---

**Version:** 2.1  
**Last Updated:** October 12, 2025  
**Status:** Production Ready ✅

---

## 🎓 Learn More

- **Documentation:** See `docs/` folder for comprehensive guides
- **Laravel:** https://laravel.com/docs/12.x
- **Filament:** https://filamentphp.com/docs/3.x
- **API Reference:** `documentation.html`
