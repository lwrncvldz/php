# 🚀 Expense Tracker Deployment Guide

Getting your Expense Tracker online is straightforward. Follow these steps to deploy your application to a live server (like Hostinger, cPanel, Namecheap, etc.).

---

## 1. Prepare Your Database

1. Open your hosting control panel (e.g., cPanel).
2. Go to **MySQL Databases** and create a new database (e.g., `youruser_expensetracker`).
3. Create a **Database User** and generate a strong password.
4. **Assign the User** to the Database, granting **ALL PRIVILEGES**.
5. Open **phpMyAdmin** in your hosting panel, select your new database, and go to the **Import** tab.
6. Upload the `database/schema.sql` file from this project to automatically create all the necessary tables.

---

## 2. Update Configuration Files

Before uploading your files, you need to update two config files to match your live server.

### A. Database Config (`config/Database.php`)
Open `config/Database.php` and update lines 7-10 with the credentials you created in Step 1:
```php
private $host = 'localhost'; // Usually remains 'localhost' on shared hosting
private $dbName = 'youruser_expensetracker'; // From Step 1
private $dbUser = 'youruser_dbuser';         // From Step 1
private $dbPassword = 'your_strong_password'; // From Step 1
```

### B. Email Config (`config/mail.php`)
If you want the email verification to work (so users can register), open `config/mail.php` and put in your SMTP details. 

If using **Gmail** (Free):
1. Go to Google Account -> Security -> 2-Step Verification.
2. Create an **App Password** for "Mail".
3. Put your Gmail and the 16-character App Password into `config/mail.php`.

If you prefer to disable email verification to get started faster, set `'enabled' => false` in that file.

---

## 3. Upload the Files

1. Open your hosting panel's **File Manager** (or use FTP like FileZilla).
2. navigate to your website's public folder (usually `public_html` or `www`).
3. Upload **ALL** the folders and files from this project:
   - `app/`
   - `config/`
   - `database/`
   - `public/`
   - `vendor/`
4. **Important Routing Note:** The actual website lives inside the `public/` folder. For the cleanest URL (e.g., `yourwebsite.com` instead of `yourwebsite.com/public`), you have two options depending on your host:
   
   **Option A (Best):** In your hosting dashboard, change your domain's "Document Root" to point directly to the `/public_html/public` folder.
   
   **Option B (Easy):** Move everything *inside* the `public/` folder directly to your `public_html` root, and ensure the `.htaccess` and `api.php` files come along with it. (If you do this, you will need to update the paths in `api.php` so it can find the `app/`, `config/`, and `vendor/` folders which will now be one level higher).

## 4. Test Your Live App

1. Go to your domain name (e.g., `https://yourwebsite.com`).
2. You should see the sleek new login page.
3. Try registering a new account with a real email to verify that the emails arrive and the database connection works perfectly!

🎉 **You're all set!**
