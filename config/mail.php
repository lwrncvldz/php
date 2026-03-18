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
    'username' => 'lawrencedump57@gmail.com',
    
    // Your Gmail App Password (16 characters, no spaces)
    // IMPORTANT: Generate this from Google Account -> Security -> App Passwords
    'password' => '5393183708243782',
    
    // The "From" address and name that recipients will see
    'from_email' => 'lawrencedump57@gmail.com',
    'from_name' => 'Expense Tracker'
];
