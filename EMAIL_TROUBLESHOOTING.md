# Email Troubleshooting Guide

## Issue: Not Receiving Contact Form Emails

If you're not receiving emails when someone submits the contact form, check the following:

## 1. Check Mail Configuration

### Current Mail Driver
```bash
php artisan config:show mail.default
```

### Check .env File
Make sure your `.env` file has proper mail configuration:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

**⚠️ IMPORTANT:** 
- `MAIL_HOST` should be just the hostname (e.g., `smtp.gmail.com` or `mail.yourdomain.com`)
- **DO NOT** include email addresses in `MAIL_HOST` (e.g., ❌ `mail.info@umudabbasli.online` is WRONG)
- **DO** use just the hostname (e.g., ✅ `mail.umudabbasli.online` is CORRECT)
- The email address goes in `MAIL_USERNAME` and `MAIL_FROM_ADDRESS`, NOT in `MAIL_HOST`

**Important:** If using Gmail, you need to:
1. Enable 2-factor authentication
2. Generate an "App Password" (not your regular password)
3. Use the app password in `MAIL_PASSWORD`

### For Testing (Log Driver)
If you want to test without sending real emails, use the log driver:

```env
MAIL_MAILER=log
```

Then check emails in: `storage/logs/laravel.log`

## 2. Check Admin Users

Verify that admin users exist and have valid email addresses:

```bash
php artisan tinker
```

Then run:
```php
$admins = App\Models\User::where('user_type', 'admin')->get(['id', 'email', 'first_name', 'last_name']);
foreach($admins as $admin) {
    echo "ID: {$admin->id}, Email: {$admin->email}, Name: {$admin->first_name} {$admin->last_name}\n";
}
```

## 3. Check Application Logs

Check for email sending errors:

```bash
# Windows PowerShell
Get-Content storage/logs/laravel.log -Tail 50 | Select-String -Pattern "contact|mail|email"

# Linux/Mac
tail -n 100 storage/logs/laravel.log | grep -i "contact\|mail\|email"
```

Look for:
- "Contact message notification emails sent" - Success
- "Failed to send contact message notification email" - Failure

## 4. Test Email Sending

### Test with Tinker
```bash
php artisan tinker
```

```php
use App\Models\ContactMessage;
use App\Models\User;
use App\Mail\ContactMessageNotification;
use Illuminate\Support\Facades\Mail;

// Create a test contact message
$testMessage = ContactMessage::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'subject' => 'Test Subject',
    'message' => 'Test message',
]);

// Get first admin
$admin = User::where('user_type', 'admin')->first();

// Try sending email
try {
    Mail::to($admin->email)->send(new ContactMessageNotification($testMessage));
    echo "Email sent successfully to: {$admin->email}\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## 5. Common Issues and Solutions

### Issue: SMTP Connection Failed
**Solution:** 
- Check firewall settings
- Verify SMTP host and port
- Check if your hosting provider blocks SMTP ports

### Issue: Authentication Failed
**Solution:**
- For Gmail: Use App Password, not regular password
- Check username/password are correct
- Verify 2FA is enabled (for Gmail)

### Issue: Emails Going to Spam
**Solution:**
- Check SPF/DKIM records for your domain
- Use a proper `MAIL_FROM_ADDRESS` matching your domain
- Check spam folder

### Issue: No Admin Users
**Solution:**
Create an admin user:
```bash
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'first_name' => 'Admin',
    'last_name' => 'User',
    'email' => 'admin@yourdomain.com', // Use your real email
    'password_hash' => Hash::make('secure-password'),
    'user_type' => 'admin',
    'email_verified' => true,
    'email_verified_at' => now(),
]);
```

## 6. Quick Test Script

Create a test route to check email configuration:

```php
// routes/web.php (temporary, remove after testing)
Route::get('/test-email', function () {
    try {
        $admin = \App\Models\User::where('user_type', 'admin')->first();
        
        if (!$admin) {
            return 'No admin users found!';
        }
        
        \Illuminate\Support\Facades\Mail::raw('Test email from contact form system', function ($message) use ($admin) {
            $message->to($admin->email)
                    ->subject('Test Email - Contact Form');
        });
        
        return "Test email sent to: {$admin->email}";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});
```

Then visit: `http://localhost:8000/test-email`

## 7. Check Queue (if using queues)

If you're using queues, make sure the queue worker is running:

```bash
php artisan queue:work
```

## 8. Verify Contact Message Was Created

Check if the contact message was saved to database:

```bash
php artisan tinker
```

```php
$messages = App\Models\ContactMessage::latest()->take(5)->get();
foreach($messages as $msg) {
    echo "ID: {$msg->id}, From: {$msg->name} ({$msg->email}), Subject: {$msg->subject}\n";
}
```

## Next Steps

1. **Check your spam folder** - Emails might be there
2. **Use log driver for testing** - Set `MAIL_MAILER=log` in `.env` and check `storage/logs/laravel.log`
3. **Verify admin email** - Make sure admin users have real, valid email addresses
4. **Test with a simple email** - Use the test route above
5. **Check SMTP credentials** - Verify all mail settings in `.env`

If emails are being logged but not sent, the issue is with your SMTP configuration. If nothing appears in logs, check if the contact form submission is working correctly.

