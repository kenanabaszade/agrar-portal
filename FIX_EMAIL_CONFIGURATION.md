# Fix Email Configuration - URGENT

## Problem Found

Your email is not being sent because of an **incorrect MAIL_HOST configuration**.

### Error from Logs:
```
Connection could not be established with host "ssl://mail.info@umudabbasli.online:465"
```

## The Issue

Your `.env` file has:
```env
MAIL_HOST=mail.info@umudabbasli.online  ❌ WRONG
```

## The Fix

Update your `.env` file:

### Option 1: If using your own domain mail server
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.umudabbasli.online
MAIL_PORT=465
MAIL_USERNAME=info@umudabbasli.online
MAIL_PASSWORD=your-password-here
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=info@umudabbasli.online
MAIL_FROM_NAME="Aqrar Portal"
```

### Option 2: If using Gmail (Recommended for testing)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Aqrar Portal"
```

**For Gmail:**
1. Enable 2-Factor Authentication
2. Go to: https://myaccount.google.com/apppasswords
3. Generate an "App Password"
4. Use that app password in `MAIL_PASSWORD` (not your regular password)

### Option 3: For testing (logs to file instead of sending)
```env
MAIL_MAILER=log
```

Then check emails in: `storage/logs/laravel.log`

## Key Points

1. **MAIL_HOST** = Just the hostname (e.g., `smtp.gmail.com`, `mail.umudabbasli.online`)
2. **MAIL_USERNAME** = Your email address (e.g., `info@umudabbasli.online`)
3. **MAIL_FROM_ADDRESS** = Your email address (e.g., `info@umudabbasli.online`)
4. **MAIL_HOST** should NEVER contain `@` symbol

## After Fixing

1. Clear config cache:
   ```bash
   php artisan config:clear
   ```

2. Test the contact form again

3. Check logs:
   ```bash
   # Windows PowerShell
   Get-Content storage/logs/laravel.log -Tail 50 | Select-String -Pattern "contact"
   ```

## Quick Test

After fixing, you can test with:

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Test email', function ($message) {
    $message->to('your-email@example.com')
            ->subject('Test Email');
});

echo "Email sent! Check your inbox (and spam folder).\n";
```

