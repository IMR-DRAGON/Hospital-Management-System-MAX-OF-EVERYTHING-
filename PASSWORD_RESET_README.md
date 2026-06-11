# Password Reset Feature Setup

This document explains how to set up and use the password reset feature for the HMS (Hospital Management System).

## Features Added

1. **Forgot Password Page** (`forgot-password.php`) - Users can request a password reset
2. **Password Reset Page** (`reset-password.php`) - Users can set a new password
3. **Database Table** - Stores password reset tokens with expiration
4. **Email Integration** - Sends password reset links via email
5. **Updated Login Page** - Added "Forgot Password?" link

## Setup Instructions

### 1. Database Setup

Run the SQL script `create_password_reset_table.sql` in your MySQL database:

```sql
-- This will create the password_reset_tokens table and add necessary columns
source create_password_reset_table.sql;
```

Or manually execute the SQL commands in your database management tool.

### 2. Email Configuration

Edit `includes/email_config.php` to configure your email settings:

#### Option A: Using PHP's built-in mail() function (Recommended for testing)
```php
define('USE_SMTP', false);
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'HMS System');
```

#### Option B: Using SMTP (For production)
```php
define('USE_SMTP', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

**Note:** For Gmail, you'll need to:
1. Enable 2-factor authentication
2. Generate an App Password
3. Use the App Password instead of your regular password

### 3. File Permissions

Ensure the following files are readable by your web server:
- `forgot-password.php`
- `reset-password.php`
- `includes/email_config.php`

### 4. Testing the Feature

1. Go to the login page (`index.php`)
2. Click "Forgot Password?"
3. Enter a valid username
4. Check your email for the reset link (or see the link displayed on screen)
5. Click the reset link
6. Enter a new password
7. Try logging in with the new password

## Security Features

- **Token Expiration**: Reset tokens expire after 1 hour
- **Unique Tokens**: Each token is cryptographically secure and unique
- **One-time Use**: Tokens are deleted after successful password reset
- **Input Validation**: All user inputs are sanitized and validated
- **Password Requirements**: Minimum 6 characters required

## Database Schema

### password_reset_tokens Table
```sql
CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expiry` (`expiry`)
);
```

### Updated tbl_employee Table
- Added `email` column (VARCHAR(255))
- Added index on `username` column for faster lookups

## Troubleshooting

### Common Issues

1. **Email not sending**: Check your email configuration and server mail settings
2. **Token not working**: Ensure the database table was created correctly
3. **Page not loading**: Check file permissions and PHP error logs

### Debug Mode

To enable debug mode, add this to the top of your PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Logging

Check your server's error logs for any PHP errors or warnings.

## Customization

### Email Template
Edit the email template in `includes/email_config.php`:
```php
define('RESET_BODY_TEMPLATE', 'Your custom email template here...');
```

### Token Expiration
Change the token expiration time in `forgot-password.php`:
```php
$expiry = date('Y-m-d H:i:s', strtotime('+2 hours')); // Change from +1 hour
```

### Password Requirements
Modify password validation in `reset-password.php`:
```php
if(strlen($new_password) >= 8) { // Change from 6 to 8
```

## Support

If you encounter any issues, please check:
1. PHP error logs
2. Database connection
3. Email server configuration
4. File permissions

## Security Notes

- In production, consider using HTTPS for all password reset links
- Regularly clean up expired tokens from the database
- Monitor for suspicious password reset requests
- Consider implementing rate limiting for password reset requests
