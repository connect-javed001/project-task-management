# Role-Based Task Management System

A scalable task and project management system built with Laravel, React, MySQL, and REST APIs.

The application provides:

* Role-Based Access Control (RBAC)
* Project & Task Management
* Work Logs & Activity Tracking
* Background Job Processing
* Deadline Notifications
* Reports & Dashboard Analytics
* Swagger API Documentation

---

# Tech Stack

## Backend

* PHP 8.3+
* Laravel 11
* MySQL 8
* Laravel Queue System
* Laravel Notifications
* Laravel Policies
* Laravel Sanctum/JWT Authentication

## Frontend

* React.js
* Vite
* Axios

## Dev Tools

* Composer
* Node.js 18+
* npm
* Swagger (l5-swagger)

---

# Features

## Authentication & Authorization

* Secure login API
* Role-based permissions
* Admin / Manager / Employee access control

## Project Management

* Create and manage projects
* Assign managers and employees
* Track project progress

## Task Management

* Create tasks
* Assign users
* Priority & status tracking
* Deadline management

## Work Logs

* Daily work submission
* Time tracking
* Work comments & discussions

## Notifications

* Deadline reminders
* Overdue task notifications
* Queue-based background processing

## Reporting & Analytics

* Dashboard statistics
* Task completion metrics
* Productivity tracking

## Audit Trail

* Activity logging system
* User action history

---

# Architecture Decisions & Assumptions

## 1. Laravel as Backend Framework

Laravel was selected because it provides:

* Clean MVC architecture
* Built-in queue system
* Authentication support
* Notification handling
* Eloquent ORM
* Scalable API development

This reduced development time while maintaining clean architecture.

---

## 2. REST API Architecture

The application follows RESTful API principles:

* Resource-based endpoints
* Stateless communication
* JSON responses
* Token-based authentication

Benefits:

* Frontend/backend separation
* Easier mobile integration
* Scalability

---

## 3. Role-Based Access Control (RBAC)

Three primary roles were implemented:

### Admin

* Full system access
* User management
* Project oversight

### Manager

* Manage assigned projects
* Assign tasks
* Review reports

### Employee

* View assigned tasks
* Submit work logs
* Update progress

Authorization logic is centralized using Laravel middleware and policies.

---

## 4. Queue-Based Notification System

Heavy operations such as:

* Email notifications
* Deadline reminders
* Background processing

are handled using Laravel queues.

Benefits:

* Faster API response times
* Better scalability
* Async processing

---

## 5. Database Design

Normalized relational database structure was used.

Key relationships:

* One Project → Many Tasks
* One Task → Many Work Logs
* One User → Many Assigned Tasks

Indexes were added on:

* Foreign keys
* Status fields
* Deadlines

to improve query performance.

---

## 6. Activity Logging

A dedicated activity log table tracks:

* User actions
* Task updates
* Project changes

This improves:

* Auditing
* Debugging
* Accountability

---

## 7. API Documentation

Swagger documentation was integrated using l5-swagger.

Benefits:

* Easier API testing
* Better developer onboarding
* Clear API contracts

Documentation endpoint:
`/api/documentation`

---

## 8. Frontend Separation

Frontend was separated from backend using React.

Advantages:

* Cleaner architecture
* Easier scaling
* Better UI flexibility
* Independent deployments

---

## 9. Scalability Considerations

The system was designed with scalability in mind:

* Queue workers
* Pagination
* Eager loading
* Background jobs
* Modular architecture

---

# Assumptions

* Single organization usage for v1
* Email notifications are sufficient (no SMS/push notifications)
* Users belong to predefined roles only
* Authentication handled via API tokens
* MySQL is used as primary relational database

---

# Out of Scope (v1)

The following features were intentionally excluded from the first version:

* Multi-tenant architecture
* Real-time chat
* File uploads
* OAuth/social login
* Advanced analytics dashboards
* Mobile applications
* WebSocket live updates
* AI-based task recommendations

---

# Installation Guide

## Prerequisites

* PHP 8.3+
* MySQL 8+
* Node.js 18+
* Composer

---

# Setup Instructions

## 1. Clone Repository

```bash
git clone https://github.com/connect-javed001/project-task-management.git
cd project-task-management
```

---

## 2. Install Backend Dependencies

```bash
composer install
```

---

## 3. Install Frontend Dependencies

```bash
npm install
```

---

## 4. Configure Environment

```bash
cp .env.example .env
```

Update database credentials inside `.env`.

---

## 5. Generate Application Key

```bash
php artisan key:generate
```

---

## 6. Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE demo;
```

---

## 7. Run Migrations

```bash
php artisan migrate
```

---

## 8. Seed Sample Data

```bash
php artisan db:seed
```

---

## 9. Build Frontend

```bash
npm run build
```

---

# Running the Application

## Terminal 1 — Laravel Server

```bash
php artisan serve
```

---

## Terminal 2 — Queue Worker

```bash
php artisan queue:work
```

---

## Terminal 3 — Scheduler

```bash
php artisan schedule:work
```

---

## Terminal 4 — Frontend Dev Server

```bash
npm run dev
```

---

# Application URLs

## Backend API

```txt
http://localhost:8000/api
```

## Frontend

```txt
http://localhost:3000
```

## Swagger API Docs

```txt
http://localhost:8000/api/documentation
```

---

# Test Users

## Admin

```txt
Email: admin@example.com
Password: password123
```

## Manager

```txt
Email: manager1@example.com
Password: password123
```

## Employee

```txt
Email: employee1@example.com
Password: password123
```

---

# Example API Endpoints

## Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
-H "Content-Type: application/json" \
-d '{
  "email": "admin@example.com",
  "password": "password123"
}'
```

---

## Get Projects

```bash
curl -X GET http://localhost:8000/api/projects \
-H "Authorization: Bearer TOKEN"
```

---

## Create Task

```bash
curl -X POST http://localhost:8000/api/tasks \
-H "Authorization: Bearer TOKEN" \
-H "Content-Type: application/json" \
-d '{
  "project_id": 1,
  "name": "New Task",
  "priority": "high",
  "status": "todo"
}'
```

---

# Performance Optimizations

* Eager loading to avoid N+1 queries
* Pagination for large datasets
* Queue workers for heavy operations
* Indexed database columns
* Cached reports and analytics

---

# Future Improvements

* Docker support
* CI/CD pipeline
* Unit & feature test coverage
* Redis queue driver
* Real-time notifications
* WebSocket integration
---

# System Status

Backend API and Frontend UI are integrated successfully.

Current status:

* Backend APIs: Completed
* Authentication: Completed
* RBAC: Completed
* Queue System: Completed
* Notifications: Completed
* Frontend Integration: Completed
* Production Deployment: Pending

---

# Author

Javed Khan

Senior Software Developer
