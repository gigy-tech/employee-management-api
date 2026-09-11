# Employee Management API

A simple RESTful API built with Laravel for managing employee records.

## Features

* Create an employee
* View all employees
* View a single employee
* Update an employee
* Delete an employee
* Validate employee information
* Store employee records in a SQLite database

## Technologies Used

* PHP
* Laravel
* SQLite
* REST API
* Git and GitHub
* Visual Studio Code

## Employee Information

Each employee contains:

* ID
* Name
* Email
* Phone
* Department
* Position
* Created date
* Updated date

## API Endpoints

| Method | Endpoint              | Description        |
| ------ | --------------------- | ------------------ |
| GET    | `/api/employees`      | Get all employees  |
| POST   | `/api/employees`      | Create an employee |
| GET    | `/api/employees/{id}` | Get one employee   |
| PUT    | `/api/employees/{id}` | Update an employee |
| PATCH  | `/api/employees/{id}` | Update an employee |
| DELETE | `/api/employees/{id}` | Delete an employee |

## Installation

Clone the repository:

```bash
git clone YOUR_GITHUB_REPOSITORY_URL
```

Move into the project:

```bash
cd employee-management-api
```

Install Laravel dependencies:

```bash
composer install
```

Create the environment file:

```bash
copy .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Create the SQLite database:

```bash
New-Item database\database.sqlite -ItemType File
```

Run migrations:

```bash
php artisan migrate
```

Start the Laravel server:

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

## Example Create Request

### POST

```text
/api/employees
```

Example JSON:

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "0712345678",
    "department": "ICT",
    "position": "Developer"
}
```

## Example Response

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "0712345678",
    "department": "ICT",
    "position": "Developer"
}
```

## Testing

The API was tested using PowerShell requests against the local Laravel server.

The following operations were tested successfully:

* Create employee
* View employees
* Update employee
* Delete employee

## Project Structure

```text
employee-management-api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── EmployeeController.php
│   └── Models/
│       └── Employee.php
├── database/
│   ├── migrations/
│   └── database.sqlite
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
├── .env
├── artisan
├── composer.json
└── README.md
```

## Author

Employee Management API — Day 5 Assignment
