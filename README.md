# Spa and Beauty System

A web-based application for managing salon and spa bookings, built with PHP and MySQL.

## Features

- User Authentication (Clients, Salonists, Admins)
- Service Management
- Appointment Booking System
- Digital Receipt Generation
- Role-based Dashboards
- Calendar View for Appointments

## Installation

1. Clone the repository to your web server directory
2. Import the database schema from `config/database.sql`
3. Configure database connection in `config/database.php`
4. Access the application through your web browser

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Directory Structure

```
spa-beauty-system/
├── assets/               # CSS, JS, Images
├── config/              # DB & config files
├── includes/            # Header, footer, nav, auth middleware
├── functions/           # Business logic
├── admin/              # Admin panel
├── client/             # Client panel
├── salonist/           # Salonist panel
├── auth/               # Login/Register/Logout
├── receipts/           # Receipt generation
└── index.php           # Homepage
```

## User Roles

### Client
- Register/Login
- Book appointments
- View booking history
- Download receipts

### Salonist
- Manage appointments
- View schedule
- Mark services as completed

### Admin
- Manage users and services
- View system reports
- Monitor bookings

## Security

- Password encryption using bcrypt
- Session-based authentication
- Input validation and sanitization
- CSRF protection

## License

This project is licensed under the MIT License. 