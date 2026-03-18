<?php

/**
 * Mail Configuration
 * 
 * Replace these with your actual Gmail credentials to send activation emails.
 * Make sure to use an "App Password" rather than your normal password if using 2FA.
 */

return [
    // Leave this as true to enable PHPMailer SMTP sending
    'enabled' => false,
    
    // SMTP Host (Gmail)
    'host' => 'smtp.gmail.com',
    
    // SMTP Port (465 for SSL, 587 for TLS)
    'port' => 465, // Try 587 if 465 doesn't work
    
    // Encryption (ssl or tls)
    'encryption' => 'ssl', // Try 'tls' if using port 587
    
    // Your Gmail Address
    'username' => 'your_email@gmail.com',
    
    // Your Gmail App Password (16 characters, no spaces)
    // IMPORTANT: Generate this from Google Account -> Security -> App Passwords
    'password' => 'your_16_char_app_password',
    
    // The "From" address and name that recipients will see
    'from_email' => 'your_email@gmail.com',
    'from_name' => 'Expense Tracker'
];
