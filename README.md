# Expense Tracker - PHP Web Version

A modern web-based expense tracking application built with PHP, Vue.js, and MySQL.

## Features

✅ **User Authentication** - Secure register/login with bcrypt password hashing  
✅ **Email Verification** - Activation link sent by email before first login  
✅ **Dashboard** - View total expenses, forecasts, and spending by category  
✅ **Add Expenses** - Track expenses with category, amount, date, and description  
✅ **Recurring Expenses** - Automatic expense generation (daily, weekly, monthly, yearly)  
✅ **Budget Management** - Set budgets for different categories with alerts  
✅ **Budget Alerts** - Notifications when approaching or exceeding budget limits  
✅ **Advanced Analytics** - Monthly summaries, trends, forecasts, and CSV export  
✅ **Responsive Design** - Works on desktop and mobile browsers  
✅ **30+ REST API Endpoints** - Complete backend for programmatic access

## Tech Stack

- **Backend**: PHP 7.4+
- **Frontend**: Vue.js 3
- **Database**: MySQL/MariaDB
- **Server**: Apache with mod_rewrite

## Quick Setup

### 1. Prerequisites

- **PHP 7.4 or higher** - [Download](https://www.php.net/downloads)
- **MySQL/MariaDB** - [Download](https://dev.mysql.com/downloads/mysql/) or [MariaDB](https://mariadb.org/download/)
- **Apache with mod_rewrite** (usually included with PHP installations)

### 2. Database Setup

```bash
# Open MySQL/MariaDB command line
mysql -u root -p

# Then run the schema SQL file
source /path/to/database/schema.sql
```

Or import the `database/schema.sql` file using phpMyAdmin.

### 3. Configure PHP

Edit `config/Database.php` and update your database credentials:

```php
private $host = 'localhost';
private $dbName = 'expense_tracker';
private $dbUser = 'root';
private $dbPassword = 'your_password';
```

### 3.1 Configure free email sending (required for activation links)

This project supports two free options in [config/Mailer.php](config/Mailer.php):

1. **Resend API (recommended free tier)** via `RESEND_API_KEY`
2. **PHP mail() SMTP** via local `php.ini` + `sendmail.ini`

Set these environment variables in Apache/XAMPP or your shell:

```bash
APP_URL=http://localhost/php/public
MAIL_FROM_ADDRESS=yourname@gmail.com
MAIL_FROM_NAME="Expense Tracker"
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxx
ADMIN_VERIFY_KEY=change-this-admin-secret
```

If `RESEND_API_KEY` is set, the app sends activation emails through Resend API.
If not, it falls back to PHP `mail()`.

For XAMPP on Windows fallback mode, configure free SMTP in `php.ini` and `sendmail.ini` (for example Gmail SMTP with an app password) so PHP `mail()` can send real emails.

### 4. Run with PHP Built-in Server

```bash
# Navigate to the project directory
cd /path/to/expense-tracker-php

# Start PHP server
php -S localhost:8000 -t php
```

Then open your browser and go to:
```
http://localhost:8000
```

### 5. Or Use Apache

1. Copy the `php` folder to your Apache `htdocs` directory
2. Configure your Apache vhost to point to the `php/public` folder
3. Enable mod_rewrite: `a2enmod rewrite`
4. Restart Apache: `sudo service apache2 restart`

## File Structure

```
php/
├── public/
│   ├── index.html          # Main Vue.js app
│   └── api.php             # API router
├── app/
│   ├── Controllers/        # Request handlers
│   │   ├── ExpenseController.php
│   │   └── BudgetController.php
│   └── Models/             # Database models
│       ├── Expense.php
│       └── Budget.php
├── config/
│   └── Database.php        # Database connection
├── database/
│   └── schema.sql          # Database schema
└── README.md               # This file
```

## API Endpoints

### Expenses

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api.php/expenses` | Get all expenses |
| GET | `/api.php/expenses/[id]` | Get single expense |
| POST | `/api.php/expenses` | Create expense |
| PUT | `/api.php/expenses/[id]` | Update expense |
| DELETE | `/api.php/expenses/[id]` | Delete expense |
| GET | `/api.php/expenses-by-category` | Get totals by category |

### Budgets

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api.php/budgets` | Get all budgets |
| POST | `/api.php/budgets` | Create budget |
| PUT | `/api.php/budgets/[id]` | Update budget |
| DELETE | `/api.php/budgets/[id]` | Delete budget |

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api.php/auth/register` | Register user and send activation email |
| GET | `/api.php/auth/verify?token=...` | Activate account via email token |
| POST | `/api.php/auth/resend-verification` | Resend activation email |
| POST | `/api.php/auth/admin-verify` | Manually verify a user email (requires admin key) |
| POST | `/api.php/auth/login` | Login only after email is verified |

### Manual verification page

Open `/admin-verify.html` in your browser when mail delivery is unavailable.
Provide target email + `ADMIN_VERIFY_KEY` to mark account as verified.

## Example API Usage

### Add an Expense
```bash
curl -X POST http://localhost:8000/api.php/expenses \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 25.50,
    "category": "Food",
    "description": "Lunch",
    "date": "2026-03-15"
  }'
```

### Get All Expenses
```bash
curl http://localhost:8000/api.php/expenses
```

## Future Enhancements

- 👥 User authentication and registration
- 💬 Chat integration with AI
- 👨‍👩‍👧‍👦 Share expenses with friends
- 📱 Mobile app version
- 📊 Advanced analytics and reports
- 🔔 Budget alerts and notifications

## Troubleshooting

### MySQL Connection Error
- Verify MySQL is running
- Check database credentials in `config/Database.php`
- Ensure database exists: `CREATE DATABASE expense_tracker;`

### 404 Errors on API
- Enable Apache mod_rewrite
- Check `.htaccess` file if using Apache
- Verify PHP can access the `api.php` file

### Database Import Issue
- Make sure you're in the MySQL prompt (not bash)
- Use absolute path to schema.sql file
- Or copy-paste the SQL commands directly

## License

MIT License - Feel free to use this for personal or commercial projects

## Support

For issues or questions, please refer to the original React Native Expense Tracker documentation or create an issue in the repository.
