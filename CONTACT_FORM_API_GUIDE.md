# Contact Form API Guide

## Overview
The contact form allows users to send messages that will be emailed to all admin users in the system.

## API Endpoint

**POST** `/api/v1/contact`

**Authentication:** Required (Bearer Token)

## Request Body

```json
{
  "name": "Ad Soyad",           // Required: string, max 255 characters
  "email": "user@example.com",  // Required: valid email, max 255 characters
  "phone": "+994 XX XXX XX XX", // Optional: string, max 50 characters
  "category": "Kateqoriya",     // Optional: string, max 255 characters
  "subject": "Mesaj mövzusu",   // Required: string, max 255 characters
  "message": "Mesaj mətni"      // Required: string
}
```

## Response

### Success (201 Created)
```json
{
  "message": "Mesajınız uğurla göndərildi",
  "contact_message": {
    "id": 1,
    "name": "Ad Soyad",
    "email": "user@example.com",
    "phone": "+994 XX XXX XX XX",
    "category": "Kateqoriya",
    "subject": "Mesaj mövzusu",
    "message": "Mesaj mətni",
    "created_at": "2025-11-12T17:41:06.000000Z",
    "updated_at": "2025-11-12T17:41:06.000000Z"
  },
  "emails_sent": 2
}
```

### Error (401 Unauthorized)
```json
{
  "message": "Unauthenticated."
}
```

### Error (422 Validation Error)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email must be a valid email address."]
  }
}
```

### Error (500 Server Error)
```json
{
  "message": "Mesaj göndərilərkən xəta baş verdi",
  "error": "Error message details"
}
```

## Frontend Example (JavaScript/React)

```javascript
async function submitContactForm(formData) {
  try {
    const token = localStorage.getItem('token'); // Get auth token
    
    if (!token) {
      throw new Error('Authentication required. Please login first.');
    }

    const response = await fetch('http://localhost:8000/api/v1/contact', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        name: formData.name,
        email: formData.email,
        phone: formData.phone || null,
        category: formData.category || null,
        subject: formData.subject,
        message: formData.message,
      }),
    });

    const result = await response.json();

    if (response.ok) {
      console.log('Message sent successfully:', result);
      return { success: true, data: result };
    } else {
      console.error('Error sending message:', result);
      return { success: false, errors: result.errors || result.message };
    }
  } catch (error) {
    console.error('Network error:', error);
    return { success: false, error: 'Network error occurred' };
  }
}

// Usage example
const handleSubmit = async (e) => {
  e.preventDefault();
  
  const formData = {
    name: document.getElementById('name').value,
    email: document.getElementById('email').value,
    phone: document.getElementById('phone').value,
    category: document.getElementById('category').value,
    subject: document.getElementById('subject').value,
    message: document.getElementById('message').value,
  };

  const result = await submitContactForm(formData);
  
  if (result.success) {
    alert('Mesajınız uğurla göndərildi!');
    // Reset form or redirect
  } else {
    alert('Xəta: ' + (result.errors || result.error));
  }
};
```

## Frontend Example (Vue.js)

```vue
<template>
  <form @submit.prevent="submitForm">
    <div>
      <label>Ad və Soyad *</label>
      <input v-model="form.name" type="text" required />
    </div>
    
    <div>
      <label>E-poçt ünvanı *</label>
      <input v-model="form.email" type="email" required />
    </div>
    
    <div>
      <label>Telefon nömrəsi</label>
      <input v-model="form.phone" type="tel" />
    </div>
    
    <div>
      <label>Kateqoriya</label>
      <select v-model="form.category">
        <option value="">Kateqoriya seçin</option>
        <option value="sual">Sual</option>
        <option value="təklif">Təklif</option>
        <option value="şikayət">Şikayət</option>
      </select>
    </div>
    
    <div>
      <label>Mövzu *</label>
      <input v-model="form.subject" type="text" required />
    </div>
    
    <div>
      <label>Mesaj *</label>
      <textarea v-model="form.message" required></textarea>
    </div>
    
    <button type="submit" :disabled="loading">
      {{ loading ? 'Göndərilir...' : 'Mesajı Göndər' }}
    </button>
  </form>
</template>

<script setup>
import { ref } from 'vue';

const form = ref({
  name: '',
  email: '',
  phone: '',
  category: '',
  subject: '',
  message: '',
});

const loading = ref(false);

const submitForm = async () => {
  loading.value = true;
  
  try {
    const response = await fetch('http://localhost:8000/api/v1/contact', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(form.value),
    });

    const result = await response.json();

    if (response.ok) {
      alert('Mesajınız uğurla göndərildi!');
      // Reset form
      form.value = {
        name: '',
        email: '',
        phone: '',
        category: '',
        subject: '',
        message: '',
      };
    } else {
      alert('Xəta: ' + (result.errors ? JSON.stringify(result.errors) : result.message));
    }
  } catch (error) {
    alert('Xəta baş verdi: ' + error.message);
  } finally {
    loading.value = false;
  }
};
</script>
```

## Email Notifications

When a contact message is submitted:
1. The message is saved to the `contact_messages` table
2. An email notification is sent to **all admin users** (users with `user_type = 'admin'`)
3. The email includes:
   - User's name, email, phone (if provided), category (if provided)
   - Message subject and content
   - Timestamp
   - Direct reply link to the user's email

## Notes

- **Authentication is required** - Users must be logged in to send contact messages
- The endpoint requires a valid Bearer token in the Authorization header
- All admin users will receive the email notification
- Failed email sends are logged but don't prevent the message from being saved
- The response includes `emails_sent` count indicating how many admins received the email

