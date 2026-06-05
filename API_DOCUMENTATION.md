# Role-Based Project & Task Management System - API Documentation

## Overview

This is a comprehensive **Role-Based Project & Task Management System** built with Laravel 13 and React/Inertia.js. It allows organizations to manage projects, assign tasks, track work logs, and receive automated deadline notifications.

### Features

✅ **Role-Based Access Control (RBAC)**
- Admin: Full system access
- Project Manager: Manages assigned projects and employees
- Employee: Works on assigned tasks

✅ **Project Management**
- Create, edit, view, and archive projects
- Track project progress and completion percentage
- Assign Project Managers to projects

✅ **Task Management**
- Create and assign tasks with priorities (Low/Medium/High/Critical)
- Track task status (Todo/In Progress/In Review/Completed/Blocked)
- Estimate hours and set deadlines
- View task timeline and history

✅ **Work Logs & Comments**
- Employees submit work logs with hours and optional attachments
- Project Managers review and reply to work logs
- Full conversation history maintained

✅ **Automated Notifications**
- Deadline reminders (48h, 24h, 12h, 1h before deadline)
- Overdue alerts to employees and managers
- Email notifications via Laravel Mail

✅ **Activity Audit Logging**
- Track all system actions (create, update, delete, status changes)
- User action history with timestamps
- Previous and new values tracked for audit compliance

✅ **Reports & Analytics**
- Project completion reports
- Employee productivity reports
- Dashboard statistics by role
- Task and work log analytics

## Technology Stack

- **Backend**: Laravel 13 with Sanctum (API authentication)
- **Frontend**: React 18 + Inertia.js + Tailwind CSS
- **Database**: MySQL (SQLite for development)
- **Queue**: Database queue (upgradeable to Redis)
- **Email**: Log driver (configurable to SMTP/SendGrid)

## Database Schema

### Core Tables

```
users
├── id (PK)
├── name
├── email (UNIQUE)
├── password (hashed)
├── role (admin | manager | employee)
├── status (active | inactive | suspended)
├── timestamps

projects
├── id (PK)
├── name
├── description
├── start_date
├── end_date
├── status (planning | active | completed | archived)
├── assigned_manager_id (FK users)
├── created_by_id (FK users)
├── timestamps

tasks
├── id (PK)
├── project_id (FK projects)
├── name
├── description
├── priority (low | medium | high | critical)
├── status (todo | in_progress | in_review | completed | blocked)
├── deadline (datetime)
├── assigned_to_id (FK users)
├── estimated_hours (decimal)
├── created_by_id (FK users)
├── timestamps

work_logs
├── id (PK)
├── task_id (FK tasks)
├── employee_id (FK users)
├── description
├── hours_worked (decimal)
├── attachment_path
├── timestamps

work_log_comments
├── id (PK)
├── work_log_id (FK work_logs)
├── user_id (FK users)
├── comment (text)
├── timestamps

activity_logs
├── id (PK)
├── user_id (FK users, nullable)
├── action
├── entity_type
├── entity_id
├── previous_value (JSON)
├── new_value (JSON)
├── created_at

deadline_notifications
├── id (PK)
├── task_id (FK tasks)
├── employee_id (FK users)
├── notification_type (48h | 24h | 12h | 1h | overdue)
├── sent_at (timestamp)
├── UNIQUE (task_id, employee_id, notification_type)
```

## API Endpoints

### Authentication

```
POST   /api/auth/login              - Login with email/password
POST   /api/auth/logout             - Logout (requires auth)
GET    /api/auth/me                 - Get current user (requires auth)
```

### Projects (RBAC Protected)

```
GET    /api/projects                - List projects (paginated, filtered)
POST   /api/projects                - Create project (admin/manager only)
GET    /api/projects/{id}           - Get project details
PUT    /api/projects/{id}           - Update project (admin/creator only)
DELETE /api/projects/{id}           - Delete project (admin only)
```

**Query Parameters:**
- `status` - Filter by status (planning, active, completed, archived)
- `manager_id` - Filter by assigned manager
- `date_from` - Filter by start date
- `date_to` - Filter by end date
- `per_page` - Results per page (default: 15)

### Tasks (RBAC Protected)

```
GET    /api/tasks                   - List tasks (role-based filtering)
POST   /api/tasks                   - Create task
GET    /api/tasks/{id}              - Get task details
PUT    /api/tasks/{id}              - Update task
PATCH  /api/tasks/{id}/status       - Update task status only
DELETE /api/tasks/{id}              - Delete task
```

**Query Parameters:**
- `project_id` - Filter by project
- `status` - Filter by status
- `priority` - Filter by priority
- `assigned_to` - Filter by assignee
- `deadline_from` - Filter by deadline range
- `deadline_to` - Filter by deadline range

### Work Logs (RBAC Protected)

```
GET    /api/tasks/{task}/work-logs              - List work logs for task
POST   /api/tasks/{task}/work-logs              - Submit work log (employee only)
GET    /api/tasks/{task}/work-logs/{id}         - Get work log details
PUT    /api/tasks/{task}/work-logs/{id}         - Update work log (author only)
DELETE /api/tasks/{task}/work-logs/{id}         - Delete work log (author only)
```

### Work Log Comments (RBAC Protected)

```
GET    /api/work-logs/{log}/comments            - List comments
POST   /api/work-logs/{log}/comments            - Add comment (manager/admin only)
PUT    /api/work-logs/{log}/comments/{id}       - Update comment (author only)
DELETE /api/work-logs/{log}/comments/{id}       - Delete comment (author only)
```

### Activity Logs (Admin Only)

```
GET    /api/activity-logs                       - List activity logs
GET    /api/activity-logs/{id}                  - Get log details
```

**Query Parameters:**
- `user_id` - Filter by user
- `action` - Filter by action type
- `entity_type` - Filter by entity type
- `date_from` - Filter by date range
- `date_to` - Filter by date range

### Reports (RBAC Protected)

```
GET    /api/reports/projects/{id}               - Project report
GET    /api/reports/employees/{id}              - Employee report
GET    /api/dashboard/stats                     - Dashboard statistics
```

## API Examples

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "role": "admin",
    "status": "active"
  },
  "token": "1|rNwfqDHl8dUFRD7CpSz3y2QzeCWafgU2L7CkuljE87717780"
}
```

### Get Projects

```bash
curl -X GET 'http://localhost:8000/api/projects?per_page=10&status=active' \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Create Task

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": 1,
    "name": "Design UI Mockups",
    "description": "Create high-fidelity mockups",
    "priority": "high",
    "status": "todo",
    "deadline": "2026-06-15 18:00:00",
    "assigned_to_id": 5,
    "estimated_hours": 16
  }'
```

### Submit Work Log

```bash
curl -X POST http://localhost:8000/api/tasks/1/work-logs \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Completed homepage design and sent to manager for review",
    "hours_worked": 8
  }'
```

### Get Dashboard Stats

```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Role-Based Access Control

### Admin Permissions
✅ Create/Edit/Delete projects, tasks, users
✅ View all projects, tasks, activity logs
✅ Assign managers to projects
✅ Assign employees to tasks
✅ Generate all reports
✅ Access admin settings

### Project Manager Permissions
✅ View assigned projects only
✅ Create/Update/Delete tasks in assigned projects
✅ Review and reply to employee work logs
✅ View project progress reports
✅ View assigned employee reports
❌ Create/Delete users
❌ Access other managers' projects

### Employee Permissions
✅ View assigned tasks only
✅ Submit and update own work logs
✅ View manager comments on logs
✅ Receive deadline notifications
❌ Create projects or tasks
❌ Assign tasks
❌ View other employees' tasks

## Background Jobs & Scheduling

### Deadline Notification Job

The system automatically runs a job every hour to check task deadlines and send notifications:

```
CheckDeadlinesAndNotify
├── Checks all incomplete tasks
├── Sends reminders 48h, 24h, 12h, 1h before deadline
├── Sends overdue notifications
└── Records notification history
```

To manually run the scheduler (for development):

```bash
php artisan schedule:run
```

To run jobs in the queue:

```bash
php artisan queue:work
```

## Testing

### Run Tests

```bash
php artisan test
```

### Test Users (Pre-seeded)

```
Admin:
  Email: admin@example.com
  Password: password123

Manager 1:
  Email: manager1@example.com
  Password: password123

Employee 1-5:
  Email: employee1@example.com to employee5@example.com
  Password: password123
```

## Installation & Setup

```bash
# Install dependencies
composer install
npm install

# Create environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Build frontend
npm run build

# Start development
npm run dev
php artisan serve
php artisan queue:work
php artisan schedule:run
```

## Environment Configuration

Key environment variables in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=demo
DB_USERNAME=root
DB_PASSWORD=Javed@123

QUEUE_CONNECTION=database    # Change to 'redis' for production
MAIL_MAILER=log             # Change to 'smtp' for production

SANCTUM_STATEFUL_DOMAINS=localhost
SESSION_DOMAIN=localhost
```

## Production Deployment

For production deployment, update:

```env
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql          # Use managed MySQL
QUEUE_CONNECTION=redis       # Use Redis for better performance
MAIL_MAILER=mailgun          # Use SendGrid/Mailgun/AWS SES

SANCTUM_STATEFUL_DOMAINS=yourdomain.com
```

## Security Features

✅ Role-Based Authorization (Policies)
✅ Input Validation on all endpoints
✅ SQL Injection Prevention (ORM)
✅ CSRF Protection (Inertia handles)
✅ Password Hashing (bcrypt)
✅ API Token Authentication (Sanctum)
✅ Activity Audit Logging
✅ File Upload Validation
✅ Rate Limiting (optional)

## Performance Optimization

- Eager loading of relationships
- Query pagination
- Database indexing on foreign keys and frequently filtered columns
- Redis caching (when configured)
- Async job queuing for notifications

## Support & Troubleshooting

### Common Issues

**"Table 'personal_access_tokens' doesn't exist"**
- Run: `php artisan migrate`

**Notifications not sending**
- Check: `MAIL_MAILER` in `.env`
- Verify queue is running: `php artisan queue:work`

**CORS errors**
- Configure `SANCTUM_STATEFUL_DOMAINS` in `.env`

## License

MIT License - See LICENSE file for details
