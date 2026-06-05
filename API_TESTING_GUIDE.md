# API Testing Guide - Postman Collection & Examples

## Prerequisites
- Laravel server running on http://localhost:8000
- MySQL database populated with seeders
- cURL or Postman installed

## Base URL
```
http://localhost:8000/api
```

## Authentication
All endpoints (except login) require:
```
Authorization: Bearer {TOKEN}
```

---

## 1. AUTHENTICATION TESTS

### 1.1 Login - Admin

**Request:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

**Expected Response (200):**
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

**Save token as:** `ADMIN_TOKEN`

### 1.2 Login - Manager

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "manager1@example.com",
    "password": "password123"
  }'
```

**Save token as:** `MANAGER_TOKEN`

### 1.3 Login - Employee

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "employee1@example.com",
    "password": "password123"
  }'
```

**Save token as:** `EMPLOYEE_TOKEN`

### 1.4 Get Current User

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 1.5 Logout

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

---

## 2. PROJECT TESTS

### 2.1 List Projects - Admin (All)

```bash
curl -X GET 'http://localhost:8000/api/projects' \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Expected:** 3 projects

### 2.2 List Projects - Manager (Only Assigned)

```bash
curl -X GET 'http://localhost:8000/api/projects' \
  -H "Authorization: Bearer $MANAGER_TOKEN"
```

**Expected:** 2 projects (assigned to this manager)

### 2.3 List Projects - Employee (Should Fail)

```bash
curl -X GET 'http://localhost:8000/api/projects' \
  -H "Authorization: Bearer $EMPLOYEE_TOKEN"
```

**Expected (403):**
```json
{
  "message": "Unauthorized"
}
```

### 2.4 Get Single Project

```bash
curl -X GET 'http://localhost:8000/api/projects/1' \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 2.5 Create Project - Admin

```bash
curl -X POST http://localhost:8000/api/projects \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Mobile App v2",
    "description": "Build mobile app version 2",
    "start_date": "2026-06-10",
    "end_date": "2026-08-15",
    "status": "planning",
    "assigned_manager_id": 2
  }'
```

**Expected (201):** Project created with ID

### 2.6 Create Project - Employee (Should Fail)

```bash
curl -X POST http://localhost:8000/api/projects \
  -H "Authorization: Bearer $EMPLOYEE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Project",
    "description": "Test",
    "start_date": "2026-06-10",
    "end_date": "2026-08-15",
    "status": "planning"
  }'
```

**Expected (403):** Unauthorized

### 2.7 Update Project

```bash
curl -X PUT http://localhost:8000/api/projects/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "active"
  }'
```

### 2.8 Delete Project

```bash
curl -X DELETE http://localhost:8000/api/projects/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

---

## 3. TASK TESTS

### 3.1 List Tasks - Admin (All)

```bash
curl -X GET 'http://localhost:8000/api/tasks' \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 3.2 List Tasks - Manager (Only in Assigned Projects)

```bash
curl -X GET 'http://localhost:8000/api/tasks' \
  -H "Authorization: Bearer $MANAGER_TOKEN"
```

### 3.3 List Tasks - Employee (Only Assigned to Them)

```bash
curl -X GET 'http://localhost:8000/api/tasks' \
  -H "Authorization: Bearer $EMPLOYEE_TOKEN"
```

### 3.4 Filter Tasks by Status

```bash
curl -X GET 'http://localhost:8000/api/tasks?status=in_progress' \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 3.5 Filter Tasks by Priority

```bash
curl -X GET 'http://localhost:8000/api/tasks?priority=critical' \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 3.6 Create Task

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id": 1,
    "name": "API Documentation",
    "description": "Write API documentation",
    "priority": "high",
    "status": "todo",
    "deadline": "2026-06-20 18:00:00",
    "assigned_to_id": 4,
    "estimated_hours": 12
  }'
```

### 3.7 Update Task

```bash
curl -X PUT http://localhost:8000/api/tasks/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "priority": "critical",
    "estimated_hours": 16
  }'
```

### 3.8 Update Task Status

```bash
curl -X PATCH http://localhost:8000/api/tasks/1/status \
  -H "Authorization: Bearer $EMPLOYEE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_progress"
  }'
```

### 3.9 Get Task Details

```bash
curl -X GET http://localhost:8000/api/tasks/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

---

## 4. WORK LOG TESTS

### 4.1 List Work Logs for Task

```bash
curl -X GET 'http://localhost:8000/api/tasks/1/work-logs' \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 4.2 Submit Work Log - Employee

```bash
curl -X POST http://localhost:8000/api/tasks/1/work-logs \
  -H "Authorization: Bearer $EMPLOYEE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Completed homepage design mockup and saved to Figma",
    "hours_worked": 8
  }'
```

**Expected (201):** Work log created

### 4.3 Submit Work Log - Manager (Should Fail)

```bash
curl -X POST http://localhost:8000/api/tasks/1/work-logs \
  -H "Authorization: Bearer $MANAGER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Test",
    "hours_worked": 1
  }'
```

**Expected (403):** Unauthorized

### 4.4 Update Work Log

```bash
curl -X PUT http://localhost:8000/api/tasks/1/work-logs/1 \
  -H "Authorization: Bearer $EMPLOYEE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Updated description",
    "hours_worked": 7.5
  }'
```

### 4.5 Get Work Log Details

```bash
curl -X GET http://localhost:8000/api/tasks/1/work-logs/1 \
  -H "Authorization: Bearer $EMPLOYEE_TOKEN"
```

---

## 5. WORK LOG COMMENT TESTS

### 5.1 Get Comments on Work Log

```bash
curl -X GET 'http://localhost:8000/api/work-logs/1/comments' \
  -H "Authorization: Bearer $MANAGER_TOKEN"
```

### 5.2 Add Comment - Manager

```bash
curl -X POST http://localhost:8000/api/work-logs/1/comments \
  -H "Authorization: Bearer $MANAGER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "comment": "Great work! The mockup looks excellent. Please move to next phase."
  }'
```

**Expected (201):** Comment created

### 5.3 Add Comment - Employee (Should Fail)

```bash
curl -X POST http://localhost:8000/api/work-logs/1/comments \
  -H "Authorization: Bearer $EMPLOYEE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "comment": "Test"
  }'
```

**Expected (403):** Unauthorized

---

## 6. ACTIVITY LOG TESTS

### 6.1 List Activity Logs - Admin Only

```bash
curl -X GET 'http://localhost:8000/api/activity-logs' \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 6.2 Filter by User

```bash
curl -X GET 'http://localhost:8000/api/activity-logs?user_id=1' \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 6.3 Filter by Entity Type

```bash
curl -X GET 'http://localhost:8000/api/activity-logs?entity_type=Task' \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 6.4 List Activity Logs - Manager (Should Fail)

```bash
curl -X GET 'http://localhost:8000/api/activity-logs' \
  -H "Authorization: Bearer $MANAGER_TOKEN"
```

**Expected (403):** Unauthorized

---

## 7. REPORT TESTS

### 7.1 Project Report

```bash
curl -X GET http://localhost:8000/api/reports/projects/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Response includes:**
```json
{
  "project_id": 1,
  "total_tasks": 3,
  "completed_tasks": 0,
  "completion_percentage": 0,
  "overdue_tasks": 0
}
```

### 7.2 Employee Report

```bash
curl -X GET http://localhost:8000/api/reports/employees/4 \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

### 7.3 Dashboard Stats - Admin

```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Response includes:**
```json
{
  "total_projects": 3,
  "total_tasks": 5,
  "active_employees": 5,
  "overdue_tasks": 0,
  "completed_tasks": 1,
  "total_hours_logged": 8,
  "project_completion_avg": 20
}
```

### 7.4 Dashboard Stats - Manager

```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer $MANAGER_TOKEN"
```

**Response includes:**
```json
{
  "managed_projects": 2,
  "active_tasks": 3,
  "upcoming_deadlines": 0,
  "overdue_tasks": 0,
  "completed_tasks": 0,
  "employee_productivity": [...]
}
```

### 7.5 Dashboard Stats - Employee

```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer $EMPLOYEE_TOKEN"
```

**Response includes:**
```json
{
  "assigned_tasks": 1,
  "tasks_in_progress": 1,
  "completed_tasks": 0,
  "tasks_due_soon": 0,
  "overdue_tasks": 0,
  "recent_work_logs": [...]
}
```

---

## 8. ERROR HANDLING TESTS

### 8.1 Invalid Email/Password

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "wrong@example.com",
    "password": "wrongpassword"
  }'
```

**Expected (422):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

### 8.2 Missing Required Fields

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Task without required fields"
  }'
```

**Expected (422):** Validation errors

### 8.3 Unauthorized Access

```bash
curl -X GET http://localhost:8000/api/projects/1 \
  -H "Authorization: Bearer invalid_token"
```

**Expected (401):** Unauthenticated

### 8.4 Not Found

```bash
curl -X GET http://localhost:8000/api/projects/99999 \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

**Expected (404):** Not found

---

## Postman Collection

Import this JSON into Postman:

```json
{
  "info": {
    "name": "Task Management API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Auth",
      "item": [
        {
          "name": "Login Admin",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/auth/login",
            "body": {
              "mode": "raw",
              "raw": "{\"email\": \"admin@example.com\", \"password\": \"password123\"}"
            }
          }
        }
      ]
    }
  ]
}
```

---

## Testing Checklist

- [ ] Authentication works for all roles
- [ ] Admin sees all projects
- [ ] Manager sees only assigned projects
- [ ] Employee cannot see projects
- [ ] Employee can submit work logs
- [ ] Manager can comment on work logs
- [ ] Activity logs recorded for all actions
- [ ] Reports generate correctly
- [ ] Dashboard stats by role
- [ ] Error handling works
- [ ] Authorization prevents unauthorized access

---

**All tests should pass!** ✅
