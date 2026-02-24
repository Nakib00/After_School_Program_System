# After School Program System

A comprehensive school management system designed for after-school programs, featuring student tracking, assignment management, attendance marking, fee collection, and detailed performance reporting.

## Tech Stack

- **Backend:** Laravel 11.x
- **Authentication:** JWT (JSON Web Token)
- **Database:** MySQL

---

## API Documentation

### Base URL

`http://your-domain.com/api`

### Authentication API

| Endpoint           | Method | Description                         | Roles         |
| :----------------- | :----- | :---------------------------------- | :------------ |
| `/register`        | POST   | Register a new user                 | Admin, Parent |
| `/login`           | POST   | Authenticate user and get token     | All           |
| `/logout`          | POST   | Invalidate current session          | All           |
| `/profile`         | GET    | Current user information            | All           |
| `/center-admins`   | GET    | List all center admin users         | Super Admin   |
| `/update-profile`  | POST   | Update name, address, profile image | All           |
| `/change-password` | POST   | Update account password             | All           |

**Register Body:** `name, email, password, password_confirmation, role (super_admin, center_admin, parents), phone, address, profile_image`

---

### Center Management

| Endpoint              | Method | Description                | Roles              |
| :-------------------- | :----- | :------------------------- | :----------------- |
| `/center`             | GET    | List all centers           | Super Admin        |
| `/center`             | POST   | Create a new center        | Super Admin        |
| `/center/{id}`        | GET    | View center details        | Super/Center Admin |
| `/center/{id}`        | PUT    | Update center info         | Super Admin        |
| `/center/{id}`        | DELETE | Remove a center            | Super Admin        |
| `/center/stats/{id?}` | GET    | Financial/Count statistics | Super/Center Admin |

---

### Student Management

| Endpoint                    | Method | Description            | Roles                  |
| :-------------------------- | :----- | :--------------------- | :--------------------- |
| `/student`                  | GET    | List students          | Admin, Teacher         |
| `/student`                  | POST   | Create student profile | Admin                  |
| `/student/{id}`             | GET    | Detailed profile       | Admin, Teacher, Parent |
| `/student/{id}`             | PUT    | Update profile         | Admin, Teacher         |
| `/student/{id}/progress`    | GET    | Level completion data  | All                    |
| `/student/{id}/assignments` | GET    | History of assignments | Admin, Teacher, Parent |
| `/student/{id}/attendance`  | GET    | Attendance log         | Admin, Teacher, Parent |
| `/student/{id}/fees`        | GET    | Payment history        | Admin, Parent          |

---

### Teacher Management

| Endpoint                   | Method | Description               | Roles          |
| :------------------------- | :----- | :------------------------ | :------------- |
| `/teacher`                 | GET    | List teachers             | Admin          |
| `/teacher`                 | POST   | Create teacher account    | Admin          |
| `/teacher/{id}`            | GET    | Profile details           | Admin          |
| `/teacher/assign-students` | POST   | Link students to teacher  | Admin          |
| `/teacher/{id}/students`   | GET    | List of assigned students | Admin, Teacher |

---

### Worksheet & Assignments

| Endpoint                   | Method | Description               | Roles          |
| :------------------------- | :----- | :------------------------ | :------------- |
| `/worksheet`               | POST   | Upload PDF worksheet      | Admin, Teacher |
| `/worksheet/{id}/download` | GET    | Get worksheet PDF         | All            |
| `/assignment`              | POST   | Assign to students (Bulk) | Admin, Teacher |
| `/submission`              | POST   | Submit completed work     | Student        |
| `/submission/{id}/grade`   | PATCH  | Grade & Feedback          | Admin, Teacher |
| `/submission/pending`      | GET    | List ungraded work        | Admin, Teacher |

---

### Fee Management

| Endpoint             | Method | Description                  | Roles         |
| :------------------- | :----- | :--------------------------- | :------------ |
| `/fees`              | GET    | List invoice records         | Admin, Parent |
| `/fees/generate`     | POST   | Bulk create monthly invoices | Admin         |
| `/fees/{id}/pay`     | PUT    | Mark as paid (+ txn details) | Admin         |
| `/fees/mark-overdue` | POST   | Update expired unpaid fees   | Admin         |
| `/fees/report`       | GET    | Collection summary           | Admin         |

---

### Reports & Dashboard

| Endpoint                         | Method | Description                  | Roles                  |
| :------------------------------- | :----- | :--------------------------- | :--------------------- |
| `/dashboard/kpis`                | GET    | Role-scoped vital stats      | All                    |
| `/reports/center-performance`    | GET    | Growth & revenue metrics     | Admin                  |
| `/reports/teacher-performance`   | GET    | Grading & student volume     | Admin                  |
| `/reports/student-detailed/{id}` | GET    | Full 360-degree student view | Admin, Teacher, Parent |
| `/reports/fee-collection`        | GET    | Monthly revenue breakdown    | Admin                  |
| `/reports/attendance`            | GET    | Center attendance trends     | Admin                  |
| `/reports/level-progression`     | GET    | Subject/Level mapping        | Admin                  |

---

## Response Format

Success:

```json
{
    "status": "Success",
    "message": "Operation successful.",
    "data": { ... }
}
```

Error:

```json
{
    "status": "Error",
    "message": "Reason for failure",
    "errors": { ... }
}
```
