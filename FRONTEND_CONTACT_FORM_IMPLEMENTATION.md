# Frontend Contact Form Implementation Guide

## Overview
This guide provides complete frontend implementation examples for the contact form ("Mesaj Göndərin"). The endpoint requires authentication (Bearer token).

## API Endpoint

**POST** `/api/v1/contact`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

## Request Body

```json
{
  "name": "Ad Soyad",           // Required: string, max 255 characters
  "email": "user@example.com",  // Required: valid email, max 255 characters
  "phone": "+994 XX XXX XX XX", // Optional: string, max 50 characters
  "category": "Sual",            // Optional: string, max 255 characters
  "subject": "Mesaj mövzusu",   // Required: string, max 255 characters
  "message": "Mesaj mətni"       // Required: string
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
    "category": "Sual",
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

---

## React Implementation

### Complete React Component Example

```jsx
import React, { useState } from 'react';

const ContactForm = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    category: '',
    subject: '',
    message: '',
  });
  
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setSuccess(false);

    try {
      const token = localStorage.getItem('token'); // or your token storage method
      
      if (!token) {
        setError('Siz daxil olmalısınız');
        setLoading(false);
        return;
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
        setSuccess(true);
        // Reset form
        setFormData({
          name: '',
          email: '',
          phone: '',
          category: '',
          subject: '',
          message: '',
        });
        
        // Hide success message after 5 seconds
        setTimeout(() => setSuccess(false), 5000);
      } else {
        if (response.status === 401) {
          setError('Siz daxil olmalısınız. Zəhmət olmasa yenidən daxil olun.');
        } else if (result.errors) {
          const errorMessages = Object.values(result.errors).flat().join(', ');
          setError(errorMessages);
        } else {
          setError(result.message || 'Xəta baş verdi');
        }
      }
    } catch (err) {
      setError('Şəbəkə xətası baş verdi. Zəhmət olmasa yenidən cəhd edin.');
      console.error('Error:', err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="contact-form-container">
      <h2>Mesaj Göndərin</h2>
      
      {success && (
        <div className="alert alert-success">
          Mesajınız uğurla göndərildi!
        </div>
      )}
      
      {error && (
        <div className="alert alert-error">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="name">
            Ad və Soyad <span className="required">*</span>
          </label>
          <input
            type="text"
            id="name"
            name="name"
            value={formData.name}
            onChange={handleChange}
            placeholder="Adınızı yazın"
            required
            disabled={loading}
          />
        </div>

        <div className="form-group">
          <label htmlFor="email">
            E-poçt ünvanı <span className="required">*</span>
          </label>
          <input
            type="email"
            id="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            placeholder="email@example.com"
            required
            disabled={loading}
          />
        </div>

        <div className="form-group">
          <label htmlFor="phone">Telefon nömrəsi</label>
          <input
            type="tel"
            id="phone"
            name="phone"
            value={formData.phone}
            onChange={handleChange}
            placeholder="+994 XX XXX XX XX"
            disabled={loading}
          />
        </div>

        <div className="form-group">
          <label htmlFor="category">
            Kateqoriya <span className="required">*</span>
          </label>
          <select
            id="category"
            name="category"
            value={formData.category}
            onChange={handleChange}
            disabled={loading}
          >
            <option value="">Kateqoriya seçin</option>
            <option value="sual">Sual</option>
            <option value="təklif">Təklif</option>
            <option value="şikayət">Şikayət</option>
            <option value="digər">Digər</option>
          </select>
        </div>

        <div className="form-group">
          <label htmlFor="subject">
            Mövzu <span className="required">*</span>
          </label>
          <input
            type="text"
            id="subject"
            name="subject"
            value={formData.subject}
            onChange={handleChange}
            placeholder="Mesajınızın mövzusunu yazın"
            required
            disabled={loading}
          />
        </div>

        <div className="form-group">
          <label htmlFor="message">
            Mesaj <span className="required">*</span>
          </label>
          <textarea
            id="message"
            name="message"
            value={formData.message}
            onChange={handleChange}
            placeholder="Mesajınızı ətraflı yazın..."
            rows="5"
            required
            disabled={loading}
          />
        </div>

        <button 
          type="submit" 
          className="btn-submit"
          disabled={loading}
        >
          {loading ? 'Göndərilir...' : 'Mesajı Göndər'}
        </button>
      </form>
    </div>
  );
};

export default ContactForm;
```

### CSS Styles (Optional)

```css
.contact-form-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.contact-form-container h2 {
  margin-bottom: 20px;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
}

.required {
  color: #e74c3c;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3498db;
}

.form-group input:disabled,
.form-group select:disabled,
.form-group textarea:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.btn-submit {
  width: 100%;
  padding: 12px;
  background-color: #27ae60;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.3s;
}

.btn-submit:hover:not(:disabled) {
  background-color: #229954;
}

.btn-submit:disabled {
  background-color: #95a5a6;
  cursor: not-allowed;
}

.alert {
  padding: 12px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}
```

---

## Vue.js Implementation

### Complete Vue Component Example

```vue
<template>
  <div class="contact-form-container">
    <h2>Mesaj Göndərin</h2>
    
    <div v-if="success" class="alert alert-success">
      Mesajınız uğurla göndərildi!
    </div>
    
    <div v-if="error" class="alert alert-error">
      {{ error }}
    </div>

    <form @submit.prevent="submitForm">
      <div class="form-group">
        <label>
          Ad və Soyad <span class="required">*</span>
        </label>
        <input
          v-model="form.name"
          type="text"
          placeholder="Adınızı yazın"
          required
          :disabled="loading"
        />
      </div>

      <div class="form-group">
        <label>
          E-poçt ünvanı <span class="required">*</span>
        </label>
        <input
          v-model="form.email"
          type="email"
          placeholder="email@example.com"
          required
          :disabled="loading"
        />
      </div>

      <div class="form-group">
        <label>Telefon nömrəsi</label>
        <input
          v-model="form.phone"
          type="tel"
          placeholder="+994 XX XXX XX XX"
          :disabled="loading"
        />
      </div>

      <div class="form-group">
        <label>
          Kateqoriya <span class="required">*</span>
        </label>
        <select
          v-model="form.category"
          :disabled="loading"
        >
          <option value="">Kateqoriya seçin</option>
          <option value="sual">Sual</option>
          <option value="təklif">Təklif</option>
          <option value="şikayət">Şikayət</option>
          <option value="digər">Digər</option>
        </select>
      </div>

      <div class="form-group">
        <label>
          Mövzu <span class="required">*</span>
        </label>
        <input
          v-model="form.subject"
          type="text"
          placeholder="Mesajınızın mövzusunu yazın"
          required
          :disabled="loading"
        />
      </div>

      <div class="form-group">
        <label>
          Mesaj <span class="required">*</span>
        </label>
        <textarea
          v-model="form.message"
          placeholder="Mesajınızı ətraflı yazın..."
          rows="5"
          required
          :disabled="loading"
        />
      </div>

      <button 
        type="submit" 
        class="btn-submit"
        :disabled="loading"
      >
        {{ loading ? 'Göndərilir...' : 'Mesajı Göndər' }}
      </button>
    </form>
  </div>
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
const error = ref(null);
const success = ref(false);

const submitForm = async () => {
  loading.value = true;
  error.value = null;
  success.value = false;

  try {
    const token = localStorage.getItem('token'); // or your token storage method
    
    if (!token) {
      error.value = 'Siz daxil olmalısınız';
      loading.value = false;
      return;
    }

    const response = await fetch('http://localhost:8000/api/v1/contact', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        name: form.value.name,
        email: form.value.email,
        phone: form.value.phone || null,
        category: form.value.category || null,
        subject: form.value.subject,
        message: form.value.message,
      }),
    });

    const result = await response.json();

    if (response.ok) {
      success.value = true;
      // Reset form
      form.value = {
        name: '',
        email: '',
        phone: '',
        category: '',
        subject: '',
        message: '',
      };
      
      // Hide success message after 5 seconds
      setTimeout(() => {
        success.value = false;
      }, 5000);
    } else {
      if (response.status === 401) {
        error.value = 'Siz daxil olmalısınız. Zəhmət olmasa yenidən daxil olun.';
      } else if (result.errors) {
        const errorMessages = Object.values(result.errors).flat().join(', ');
        error.value = errorMessages;
      } else {
        error.value = result.message || 'Xəta baş verdi';
      }
    }
  } catch (err) {
    error.value = 'Şəbəkə xətası baş verdi. Zəhmət olmasa yenidən cəhd edin.';
    console.error('Error:', err);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Add the same CSS styles as React example above */
.contact-form-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
/* ... rest of styles ... */
</style>
```

---

## Vanilla JavaScript Implementation

```javascript
// Contact Form Handler
class ContactForm {
  constructor(formId) {
    this.form = document.getElementById(formId);
    this.init();
  }

  init() {
    this.form.addEventListener('submit', (e) => this.handleSubmit(e));
  }

  async handleSubmit(e) {
    e.preventDefault();
    
    const submitButton = this.form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Göndərilir...';

    const formData = {
      name: this.form.querySelector('[name="name"]').value,
      email: this.form.querySelector('[name="email"]').value,
      phone: this.form.querySelector('[name="phone"]').value || null,
      category: this.form.querySelector('[name="category"]').value || null,
      subject: this.form.querySelector('[name="subject"]').value,
      message: this.form.querySelector('[name="message"]').value,
    };

    try {
      const token = localStorage.getItem('token');
      
      if (!token) {
        this.showError('Siz daxil olmalısınız');
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        return;
      }

      const response = await fetch('http://localhost:8000/api/v1/contact', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      const result = await response.json();

      if (response.ok) {
        this.showSuccess('Mesajınız uğurla göndərildi!');
        this.form.reset();
      } else {
        if (response.status === 401) {
          this.showError('Siz daxil olmalısınız. Zəhmət olmasa yenidən daxil olun.');
        } else if (result.errors) {
          const errorMessages = Object.values(result.errors).flat().join(', ');
          this.showError(errorMessages);
        } else {
          this.showError(result.message || 'Xəta baş verdi');
        }
      }
    } catch (err) {
      this.showError('Şəbəkə xətası baş verdi. Zəhmət olmasa yenidən cəhd edin.');
      console.error('Error:', err);
    } finally {
      submitButton.disabled = false;
      submitButton.textContent = originalText;
    }
  }

  showSuccess(message) {
    // Implement your success message display
    alert(message);
  }

  showError(message) {
    // Implement your error message display
    alert(message);
  }
}

// Initialize the form
document.addEventListener('DOMContentLoaded', () => {
  new ContactForm('contact-form');
});
```

---

## Testing Checklist

1. ✅ **Authentication Check**
   - Test without token (should return 401)
   - Test with valid token (should work)
   - Test with expired token (should return 401)

2. ✅ **Form Validation**
   - Test with empty required fields
   - Test with invalid email format
   - Test with all fields filled correctly

3. ✅ **Success Flow**
   - Submit valid form
   - Check success message appears
   - Check form resets
   - Verify email sent to admins

4. ✅ **Error Handling**
   - Test network errors
   - Test server errors
   - Test validation errors

---

## Important Notes

1. **Token Storage**: Make sure you're storing the authentication token correctly (localStorage, sessionStorage, or your state management solution)

2. **Base URL**: Update the base URL (`http://localhost:8000`) to match your backend URL

3. **Error Messages**: Customize error messages to match your application's language and style

4. **Loading States**: Always show loading indicators during API calls

5. **Form Reset**: Clear the form after successful submission

6. **Email Notifications**: Remember that all admin users will receive email notifications when a message is submitted

