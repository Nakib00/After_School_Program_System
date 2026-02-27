# After School Program System

A powerful and comprehensive school management system tailored for after-school programs. This system streamlines student tracking, assignment automation, attendance management, fee collection, and detailed analytics for parents and administrators.

## 🚀 Key Features

- **User Management**: Multi-role support (Super Admin, Center Admin, Teacher, Student, Parent).
- **Student Lifecycle**: Automatic `enrollment_no` generation (`S-YYMM101`), `monthly_fee` tracking, and progress monitoring.
- **Academic Control**: Bulk assignment distribution, PDF worksheet management, and automated grading workflows.
- **Financial Management**: Bulk fee generation, payment tracking (txn details), and overdue automation.
- **Rich Reporting**: Comprehensive KPIs for all roles, detailed center performance, and student 360-degree views.
- **Center Administration**: Manage multiple centers with isolated data access for center admins.

## 🛠️ Technology Stack

- **Framework**: Laravel 11.x
- **Security**: JWT Authentication (JSON Web Token)
- **Database**: MySQL 8.x
- **Storage**: Local/Cloud Disk for worksheets and submissions

## 🔧 Installation & Setup

1. **Clone the Repo**:

    ```bash
    git clone [repository-url]
    cd After_school_program_system
    ```

2. **Install Dependencies**:

    ```bash
    composer install
    npm install && npm run build
    ```

3. **Environment Setup**:

    ```bash
    cp .env.example .env
    # Update DB_DATABASE, DB_USERNAME, DB_PASSWORD
    # Generate Application Key
    php artisan key:generate
    # Generate JWT Secret
    php artisan jwt:secret
    ```

4. **Database Migration**:

    ```bash
    php artisan migrate --seed
    ```

5. **Run Server**:
    ```bash
    php artisan serve
    ```

---

## 📖 API Documentation

### Base URL

`http://localhost:8000/api`

### 🔒 Authorization

Most endpoints require a **Bearer Token**. Include it in your headers:
`Authorization: Bearer {your_token}`

---

### 1. Authentication & Profile

| Endpoint                    | Method | Description                        | Roles               |
| :-------------------------- | :----- | :--------------------------------- | :------------------ |
| `/login`                    | POST   | Login and receive JWT token        | All                 |
| `/logout`                   | POST   | Invalidate current token           | All                 |
| `/profile`                  | GET    | View current user profile          | All                 |
| `/update-profile`           | POST   | Update name, phone, address, image | All                 |
| `/change-password`          | POST   | Update account password            | All                 |
| `/users/{id}/toggle-status` | PATCH  | Activate/Deactivate a user account | Super, Center Admin |

### 2. Center Management

| Endpoint             | Method               | Description                          | Roles               |
| :------------------- | :------------------- | :----------------------------------- | :------------------ |
| `/center`            | GET \| POST          | List all / Create new center         | Super Admin         |
| `/center/{id}`       | GET \| PUT \| DELETE | View / Update / Remove center        | Super, Center Admin |
| `/center/stats/{id}` | GET                  | View analytics for a specific center | Super, Center Admin |

### 3. Student Management

| Endpoint                 | Method               | Description                                 | Roles                  |
| :----------------------- | :------------------- | :------------------------------------------ | :--------------------- |
| `/student`               | GET \| POST          | List all / Create student (auto-enrollment) | Admin, Teacher         |
| `/student/{id}`          | GET \| PUT \| DELETE | Full profile / Update / Remove              | Admin, Teacher         |
| `/student/{id}/progress` | GET                  | Level progression and metrics               | All                    |
| `/student/{id}/reports`  | GET                  | Full academic/attendance reports            | Admin, Teacher, Parent |

### 4. Fee Management

| Endpoint            | Method     | Description                                        | Roles               |
| :------------------ | :--------- | :------------------------------------------------- | :------------------ |
| `/fees`             | GET        | List fees (filterable by center_id, month, status) | Admin, Parent       |
| `/fees/center/{id}` | GET        | Get ALL fee records for specific center            | Super, Center Admin |
| `/fees/generate`    | POST       | Bulk generate student fees for a month             | Admin               |
| `/fees/{id}`        | GET \| PUT | View details / Update fee record                   | Admin, Parent       |
| `/fees/{id}/pay`    | PUT        | Mark fee as paid with txn details                  | Admin               |
| `/fees/report`      | GET        | Financial collection summary                       | Admin               |

### 5. Assignments & Submissions

| Endpoint                 | Method      | Description                                | Roles          |
| :----------------------- | :---------- | :----------------------------------------- | :------------- |
| `/assignment`            | GET \| POST | Bulk assign work / View history            | Admin, Teacher |
| `/worksheet`             | GET \| POST | Manage PDF worksheet library               | Admin, Teacher |
| `/submission`            | GET \| POST | List all submissions / Student submit work | All            |
| `/submission/{id}/grade` | PATCH       | Grade work with score and feedback         | Admin, Teacher |

### 6. Reports & Analytics

| Endpoint                        | Method | Description                                                  | Roles |
| :------------------------------ | :----- | :----------------------------------------------------------- | :---- |
| `/dashboard/kpis`               | GET    | Vital role-based dashboard stats                             | All   |
| `/reports/center-detailed/{id}` | GET    | Full 360 Overview of Center (Financial/Academic/Operational) | Admin |
| `/reports/fee-collection`       | GET    | Monthly revenue trends                                       | Admin |
| `/reports/attendance`           | GET    | Monthly attendance distribution                              | Admin |

---

## 📁 System Architecture

- **Controllers**: Located in `app/Http/Controllers/Api`
- **Models**: Located in `app/Models`
- **Database**: Migrations in `database/migrations`
- **Traits**: Shared logic for responses in `app/Traits/ApiResponse.php`
