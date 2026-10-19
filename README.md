# Nexus Library Management System

A futuristic, glassmorphic Library Management System built with PHP and MySQL. Designed for medium-sized libraries with features for book management, user management, transactions, fines, and comprehensive reporting.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)
![License](https://img.shields.io/badge/license-MIT-green)

## Features

### Core Features
- **Book Management**: Add, edit, delete, search, and categorize books
- **User Management**: Member registration, role-based access (Admin, Librarian, Member)
- **Transaction System**: Issue books, return books, renew loans
- **Fine System**: Automatic fine calculation with grace period
- **Reports**: Daily/Monthly reports, overdue tracking, popular books

### UI/UX Features
- Futuristic glassmorphism dark theme
- Responsive design (mobile + desktop)
- Smooth animations and transitions
- Dashboard with stats and charts
- AJAX-based operations

### Security
- Secure password hashing (bcrypt)
- Session-based authentication
- Prepared statements (SQL injection prevention)
- Role-based access control

## Quick Start

### Prerequisites
- XAMPP (or any PHP + MySQL stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Installation

1. **Clone or Download the Project**
   ```
   Download the library folder to your XAMPP htdocs directory:
   C:\xampp\htdocs\library\
   ```

2. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

3. **Create the Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Click "Import" tab
   - Select the `database.sql` file from this project
   - Click "Go"

4. **Configure Database Connection**
   - Edit `config/db.php`
   - Update credentials if needed (default should work with XAMPP):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'library_db');
   ```

5. **Access the Application**
   - Open your browser
   - Go to: http://localhost/library/

## Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | admin123 |
| Librarian | librarian@example.com | admin123 |
| Member | member1@example.com | admin123 |

## Project Structure

```
library/
├── config/
│   └── db.php              # Database configuration
├── includes/
│   ├── header.php          # Common header
│   ├── sidebar.php          # Navigation sidebar
│   ├── topbar.php          # Top navigation bar
│   ├── footer.php          # Common footer
│   └── auth_check.php      # Authentication helper
├── assets/
│   ├── css/
│   │   └── style.css       # Main stylesheet
│   └── js/
│       └── main.js         # Main JavaScript
├── auth/
│   ├── login.php           # Login page
│   └── logout.php          # Logout handler
├── admin/
│   ├── users/              # User management
│   ├── books/              # Book management
│   ├── transactions/      # Transaction management
│   └── reports/            # Reports
├── pages/
│   ├── dashboard.php       # User dashboard
│   ├── books.php           # Browse books
│   ├── profile.php         # User profile
│   └── my_books.php        # Member's borrowed books
├── database.sql            # Database schema + sample data
└── index.php               # Entry point
```

## Database Schema

### Tables Overview

- **users**: Library members and staff
- **books**: Books, magazines, newspapers
- **categories**: Book categories
- **transactions**: Issue/return records
- **fines**: Fine records
- **notifications**: User notifications
- **reservations**: Book reservations
- **vendors**: Book vendors
- **procurements**: Purchase orders
- **maintenance_logs**: Book maintenance records
- **settings**: System settings
- **activity_logs**: User activity logs

## User Roles & Permissions

### Admin
- Full access to all features
- Manage users (add/edit/delete)
- Manage all books
- View all transactions
- Access all reports
- System settings

### Librarian
- Manage books
- Issue/return books
- View transactions
- Generate reports
- Manage fines

### Member
- Browse books
- View own profile
- Track borrowed books
- View own notifications

## Fine Calculation Rules

1. **Grace Period**: 7 days after due date
2. **Fine Rate**: $1.00 per day after grace period
3. **Maximum Cap**: $50.00

Example:
- Due date: Jan 1st
- Return date: Jan 15th (14 days late)
- Grace period: 7 days
- Chargeable days: 14 - 7 = 7 days
- Total fine: 7 × $1.00 = $7.00

## API Endpoints (AJAX)

The system uses AJAX for dynamic operations:

```javascript
// Example: API call
fetch('/api/notifications.php?action=mark_read&id=1')
    .then(response => response.json())
    .then(data => console.log(data));
```

## Configuration Options

Edit `config/db.php` to customize:

```php
// Fine settings
define('FINE_PER_DAY', 1.00);
define('GRACE_PERIOD_DAYS', 7);

// Borrowing limits
define('DEFAULT_BORROW_DAYS_MEMBER', 14);
define('DEFAULT_BORROW_LIMIT_MEMBER', 5);

// Site settings
define('SITE_NAME', 'Nexus Library');
define('SITE_URL', 'http://localhost/library');
```

## Troubleshooting

### Database Connection Error
- Ensure MySQL is running
- Check credentials in `config/db.php`
- Verify database exists in phpMyAdmin

### 500 Internal Server Error
- Check PHP error logs
- Verify .htaccess if using Apache
- Ensure PHP version is 7.4+

### Styles Not Loading
- Check browser console for 404 errors
- Verify paths in `header.php`
- Clear browser cache

## Development

### Adding New Features

1. **New Page**:
   - Create PHP file in appropriate folder
   - Include header/footer
   - Add to sidebar navigation

2. **New API Endpoint**:
   - Create new PHP file or add to existing
   - Return JSON responses
   - Handle POST/GET methods

3. **Database Changes**:
   - Modify `database.sql`
   - Re-import (drop existing tables first)
   - Or run ALTER TABLE statements

### CSS Customization

Edit `assets/css/style.css`:
- CSS Variables at top for theming
- Modular sections for each component
- Glassmorphism effects

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For issues or questions:
- Create an issue on GitHub
- Email: support@nexuslibrary.com

## Credits

- Design: Glassmorphism Dark Theme
- Icons: Font Awesome 6
- Fonts: Google Fonts (Inter, Orbitron)

---

Made with ❤️ for libraries everywhere
