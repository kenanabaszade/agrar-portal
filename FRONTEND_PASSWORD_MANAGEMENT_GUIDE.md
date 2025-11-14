## Frontend Password Management Guide

This guide explains every password-related flow the frontend must implement:

- Authenticated users changing their own passwords
- Verification that the old password is correct before accepting a new one
- Full “forgot password” (loss password) recovery with OTP + reset token

All URLs assume the API base path `/api/v1`.

---

## 1. Change Password (Authenticated Users)

- **Endpoint:** `POST /profile/change-password`
- **Auth:** Bearer token (user must be logged in)

### Request body
```json
{
  "current_password": "OldPass123",
  "new_password": "NewPass456",
  "new_password_confirmation": "NewPass456"
}
```

### Backend behavior
1. Validates that all three fields exist and `new_password` is at least 8 chars.
2. Runs `Hash::check(current_password, user.password_hash)`.  
   - If the check fails API returns `422` with message `Current password is incorrect`.
3. Hashes the new password (`Hash::make`) and updates the user.
4. Returns `200` + success message.

### Frontend checklist
1. Fetch logged-in user data (token stored already).
2. Build a form with three inputs:
   - Current password
   - New password
   - Confirm new password
3. Submit payload exactly as above.
4. On `422` show inline error (wrong old password or mismatch).
5. On `200` show toast/snackbar confirming success and clear the form.

### Suggested UI copy
- Success: “Şifrəniz uğurla dəyişdirildi.”
- Error: show API `message` string plus field-level validation errors if returned.

---

## 2. Forgot / Lost Password Flow (Public)

Routes in order:
1. `POST /auth/forgot-password`
2. `POST /auth/verify-password-reset-otp`
3. `POST /auth/reset-password`
4. (Optional) `POST /auth/resend-password-reset-otp`

### Step 1 – Request reset
- **Body:** `{ "email": "user@example.com" }`
- **Response:** always `200` with generic message.
- **Backend actions:** creates/updates a 64-char reset token in `password_reset_tokens`, generates 6-digit OTP, saves OTP + expiry (10 min) on user, emails OTP.
- **Frontend:** after success show OTP input screen; store the email in state.

### Step 2 – Verify OTP
- **Body:** `{ "email": "...", "otp": "123456" }`
- **Success response:**
```json
{
  "message": "OTP verified successfully. You can now reset your password.",
  "token": "<64-char-reset-token>",
  "email": "user@example.com"
}
```
- **Frontend:** persist the returned `token` (memory, store, or URL param) and redirect to the “set new password” view.
- **Error handling:**  
  - `400` -> OTP missing/expired/no request.  
  - `422` -> wrong OTP.  
  - Show countdown until expiry and allow “resend OTP” after a short cooldown.

### Step 3 – Reset password
- **Body:**
```json
{
  "email": "user@example.com",
  "token": "<64-char-reset-token>",
  "password": "NewPass456",
  "password_confirmation": "NewPass456"
}
```
- **Backend checks:** token exists for that email and is ≤24h old, password confirmation matches.
- **Success:** `200` + “Password reset successfully…” message; token is deleted so it can’t be reused.
- **Frontend:** after success redirect to `/login` and optionally pre-fill email.

### Step 4 – Resend OTP (optional)
- **Body:** `{ "email": "user@example.com" }`
- Only works if a reset token already exists. Otherwise API returns `400` forcing user to start from step 1.

---

## 3. Security Notes

- Change-password flow always validates the previous password via `Hash::check`.
- Forgot-password flow requires BOTH the emailed OTP (10 min expiry) and the database reset token (24 h expiry). Losing either means starting over.
- OTPs are cleared after successful verification; tokens are deleted after password reset.
- All new passwords must meet backend validation (`min:8` and confirmation).

---

## 4. Frontend UX Flow Summary

1. **Forgot Password page** – email input ➜ call `/auth/forgot-password`.
2. **OTP page** – display email, OTP input, timer, resend button ➜ call `/auth/verify-password-reset-otp`.
3. **Set Password page** – requires `email` + `token`, two password fields ➜ call `/auth/reset-password`.
4. **Logged-in settings page** – allow change password form ➜ call `/profile/change-password`.

Keep state navigation-friendly by passing `email`/`token` in URL query params or local storage (ensure it’s cleared once done).

---

## 5. Example Fetch Helpers

```javascript
export async function changePassword(payload, token) {
  return fetch('/api/v1/profile/change-password', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(payload)
  });
}

export async function requestPasswordReset(email) {
  return fetch('/api/v1/auth/forgot-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email })
  });
}

export async function verifyResetOtp(email, otp) {
  return fetch('/api/v1/auth/verify-password-reset-otp', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, otp })
  });
}

export async function resetPassword(email, token, password, confirm) {
  return fetch('/api/v1/auth/reset-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email,
      token,
      password,
      password_confirmation: confirm
    })
  });
}
```

Use these helpers inside your UI components, handle `response.ok`, and surface validation errors from the JSON payload so users understand what went wrong.

