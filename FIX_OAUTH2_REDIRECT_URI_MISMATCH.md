# 🔧 Fix OAuth2 Redirect URI Mismatch Error

## 🚨 Problem
You're getting this error:
```
Bu uygulama geçersiz bir istek gönderdiğinden oturum açamazsınız.
Error 400: redirect_uri_mismatch
```

This means the redirect URI in your Google Cloud Console doesn't match what we're sending in the OAuth2 request.

## 🔍 Current Configuration

**Your .env file has:**
```
GOOGLE_REDIRECT_URI=http://localhost:8000/oauth2/callback
```

**Your Laravel route exists at:**
```
Route::get('/oauth2/callback', function (Request $request) { ... });
```

## ✅ Solution Steps

### Step 1: Check Google Cloud Console
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project
3. Go to **APIs & Services** → **Credentials**
4. Find your OAuth 2.0 Client ID
5. Click **Edit** (pencil icon)

### Step 2: Add/Update Authorized Redirect URIs
In the **Authorized redirect URIs** section, make sure you have:

```
http://localhost:8000/oauth2/callback
```

**Important:** The URI must match EXACTLY, including:
- Protocol: `http://` (not `https://`)
- Domain: `localhost`
- Port: `8000`
- Path: `/oauth2/callback`

### Step 3: Save Changes
1. Click **Save** in Google Cloud Console
2. Wait 1-2 minutes for changes to propagate

### Step 4: Test the Fix
Run this command to test:

```bash
curl -X GET "http://localhost:8000/api/v1/google/auth-url" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

## 🧪 Alternative Testing Methods

### Method 1: Use the HTML Test Page
1. Open: `http://localhost:8000/google-meet-test`
2. Click "Get Authorization URL"
3. Click "Start OAuth2 Flow"
4. Complete the OAuth2 flow

### Method 2: Manual Browser Test
1. Get the auth URL from the API
2. Open it in your browser
3. Complete the authorization
4. You should be redirected to: `http://localhost:8000/oauth2/callback`

## 🔧 Common Issues & Solutions

### Issue 1: Port Mismatch
**Problem:** Google Console has port 3000, but Laravel runs on 8000
**Solution:** Update Google Console to use port 8000

### Issue 2: Protocol Mismatch
**Problem:** Google Console has `https://`, but Laravel runs on `http://`
**Solution:** Use `http://` for local development

### Issue 3: Path Mismatch
**Problem:** Google Console has `/callback`, but Laravel expects `/oauth2/callback`
**Solution:** Update Google Console to use `/oauth2/callback`

### Issue 4: Domain Mismatch
**Problem:** Google Console has `127.0.0.1`, but Laravel uses `localhost`
**Solution:** Use `localhost` in both places

## 📋 Complete Google Console Configuration

For local development, your Google Console should have:

**Authorized JavaScript origins:**
```
http://localhost:8000
```

**Authorized redirect URIs:**
```
http://localhost:8000/oauth2/callback
```

## 🚀 Production Configuration

For production, update your `.env` file:

```env
GOOGLE_REDIRECT_URI=https://yourdomain.com/oauth2/callback
```

And add to Google Console:
```
https://yourdomain.com/oauth2/callback
```

## ✅ Verification Steps

1. **Check .env file:**
   ```bash
   grep "GOOGLE_REDIRECT_URI" .env
   ```

2. **Test API endpoint:**
   ```bash
   curl -X GET "http://localhost:8000/api/v1/google/auth-url" \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

3. **Test OAuth2 flow:**
   - Open the auth URL in browser
   - Complete authorization
   - Should redirect to success page

## 🆘 Still Having Issues?

If you're still getting the error:

1. **Double-check the exact URI** in Google Console
2. **Wait 2-3 minutes** after making changes
3. **Clear browser cache** and try again
4. **Check Laravel logs** for more details:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## 📞 Need Help?

If the issue persists, please share:
1. The exact redirect URI from your Google Console
2. The exact redirect URI from your .env file
3. Any error messages from Laravel logs
