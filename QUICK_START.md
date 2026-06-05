# Quick Start Guide - Role-Based Task Management System

## 5-Minute Setup

### Prerequisites
- PHP 8.3+
- MySQL 8.0+
- Node.js 18+
- Composer

### Installation

```bash
# 1. Clone or navigate to project
cd /Users/javedk/practice/demo

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Copy environment file
cp .env.example .env

# 5. Generate app key
php artisan key:generate

# 6. Create database
mysql -u root -p -e "CREATE DATABASE demo IF NOT EXISTS;"

# 7. Run migrations
php artisan migrate

# 8. Seed test data
php artisan db:seed

# 9. Build frontend
npm run build
```

## Running the Application

### Development Mode

```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Run queue worker
php artisan queue:work

# Terminal 3: Run scheduler
php artisan schedule:run --verbose

# Terminal 4: Build frontend with hot reload
npm run dev
```

### Access the Application

- **API Base URL**: http://localhost:8000/api
- **Frontend**: http://localhost:3000 (if React dev server running)

## Test API Endpoints

### 1. Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

Save the token from response.

### 2. Get Projects (replace TOKEN with actual token)

```bash
curl -X GET http://localhost:8000/api/projects \
  -H "Authorization: Bearer TOKEN"
```

### 3. Create Task

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": 1,
    "name": "New Task",
    "description": "Task description",
    "priority": "high",
    "status": "todo",
    "deadline": "2026-06-20 18:00:00",
    "assigned_to_id": 4,
    "estimated_hours": 8
  }'
```

### 4. Submit Work Log

```bash
curl -X POST http://localhost:8000/api/tasks/1/work-logs \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Completed design mockups",
    "hours_worked": 6
  }'
```

### 5. Get Dashboard Statistics

```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer TOKEN"
```

## Test Users

```
Admin:
  Email: admin@example.com
  Password: password123
  Role: admin

Manager 1:
  Email: manager1@example.com
  Password: password123
  Role: manager

Employee 1:
  Email: employee1@example.com
  Password: password123
  Role: employee

(Similar for employee2-5)
```

## Database Credentials

```
Host: 127.0.0.1
User: root
Password: Javed@123
Database: demo
```

## Project Structure

```
app/
├── Console/
│   └── Kernel.php              (Scheduled jobs)
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── ProjectController.php
│   │       ├── TaskController.php
│   │       ├── WorkLogController.php
│   │       ├── WorkLogCommentController.php
│   │       ├── ActivityLogController.php
│   │       └── ReportController.php
│   └── Requests/
├── Jobs/
│   └── CheckDeadlinesAndNotify.php
├── Models/
│   ├── User.php
│   ├── Project.php
│   ├── Task.php
│   ├── WorkLog.php
│   ├── WorkLogComment.php
│   ├── ActivityLog.php
│   └── DeadlineNotification.php
├── Notifications/
│   ├── DeadlineReminderNotification.php
│   └── OverdueTaskNotification.php
└── Policies/

database/
├── migrations/          (All schemas)
└── seeders/            (Test data)

routes/
├── api.php            (API endpoints)
└── web.php            (Frontend routes)

resources/js/
└── Pages/             (React components)
    ├── Auth/
    ├── Dashboard/
    ├── Projects/
    ├── Tasks/
    ├── WorkLogs/
    ├── ActivityLogs/
    └── Reports/
```

## Features Implemented

✅ **Phase 1**: Database schema & migrations
✅ **Phase 2**: Models & Controllers with RBAC
✅ **Phase 3**: Work logs & comments system
✅ **Phase 4**: Email notifications & background jobs
✅ **Phase 5**: API routes & authentication
✅ **Phase 6**: Reports & analytics endpoints
✅ **Phase 7**: Testing preparation

## Remaining Tasks for Full Deployment

- [ ] React UI components (Phase 5 - Frontend)
- [ ] Email configuration (SMTP/SendGrid)
- [ ] Redis queue setup (production)
- [ ] Policies for fine-grained authorization
- [ ] Comprehensive test suite
- [ ] API rate limiting
- [ ] Deployment to production server

## Troubleshooting

### Migrations fail
```bash
php artisan migrate:refresh --seed
```

### Clear cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Check scheduler
```bash
php artisan schedule:list
```

### Test notifications
```bash
php artisan tinker
> Mail::raw('Test', function ($m) { $m->to('test@example.com'); });
```

## API Response Examples

### Success Response
```json
{
  "id": 1,
  "name": "Website Redesign",
  "status": "active",
  "created_at": "2026-06-05T03:16:02.000000Z"
}
```

### Error Response
```json
{
  "message": "Unauthorized",
  "exception": "AuthorizationException"
}
```

### Paginated Response
```json
{
  "data": [...],
  "links": {
    "first": "http://localhost:8000/api/projects?page=1",
    "last": "http://localhost:8000/api/projects?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/projects?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

## Performance Tips

1. **Eager Load Relations**
   - Always load related models to avoid N+1 queries

2. **Use Pagination**
   - Never return unlimited results

3. **Index Frequently Queried Columns**
   - Foreign keys, status, deadline, etc.

4. **Cache Reports**
   - Cache expensive dashboard calculations

5. **Queue Heavy Operations**
   - Email sending, exports, notifications

## Next Steps

1. Review API documentation in `API_DOCUMENTATION.md`
2. Create React components for UI
3. Configure email service for production
4. Set up Redis for queue caching
5. Implement rate limiting
6. Write comprehensive tests
7. Deploy to production

## Support

For issues or questions, refer to:
- Laravel Documentation: https://laravel.com/docs
- Sanctum API Auth: https://laravel.com/docs/sanctum
- React Documentation: https://react.dev
- Inertia.js: https://inertiajs.com

---

**System Status**: ✅ Backend API Ready for Frontend Integration
