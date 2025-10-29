# API для работы с фотографиями профиля

## Обзор

Система поддерживает загрузку, обновление и удаление фотографий профиля пользователей. Фотографии сохраняются в папке `storage/app/public/profile_photos/` и доступны через публичные URL.

## Структура данных

### Поле profile_photo в модели User
- **Тип**: `string` (nullable)
- **Содержимое**: Имя файла фотографии (например: `user_123_1697123456.jpg`)
- **URL доступа**: `{APP_URL}/storage/profile_photos/{filename}`

## API Endpoints

### 1. Загрузка фотографии профиля

**POST** `/api/v1/profile/upload-photo`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (Form Data):**
```
profile_photo: [файл изображения]
```

**Валидация:**
- `profile_photo`: обязательное поле
- Тип файла: `image`
- Разрешенные форматы: `jpeg`, `png`, `jpg`, `gif`
- Максимальный размер: `2048 KB` (2MB)

**Успешный ответ (200):**
```json
{
    "message": "Profile photo uploaded successfully",
    "profile_photo_url": "http://localhost:8000/storage/profile_photos/user_123_1697123456.jpg",
    "user": {
        "id": 123,
        "first_name": "Имя",
        "last_name": "Фамилия",
        "email": "user@example.com",
        "profile_photo": "user_123_1697123456.jpg",
        "profile_photo_url": "http://localhost:8000/storage/profile_photos/user_123_1697123456.jpg",
        // ... другие поля пользователя
    }
}
```

**Ошибки:**
- `422` - Ошибка валидации (неправильный формат файла, превышен размер)
- `401` - Не авторизован

### 2. Удаление фотографии профиля

**DELETE** `/api/v1/profile/delete-photo`

**Headers:**
```
Authorization: Bearer {token}
```

**Успешный ответ (200):**
```json
{
    "message": "Profile photo deleted successfully",
    "user": {
        "id": 123,
        "first_name": "Имя",
        "last_name": "Фамилия",
        "email": "user@example.com",
        "profile_photo": null,
        "profile_photo_url": null,
        // ... другие поля пользователя
    }
}
```

**Ошибки:**
- `400` - Нет фотографии для удаления
- `401` - Не авторизован

### 3. Получение профиля пользователя

**GET** `/api/v1/profile`

**Headers:**
```
Authorization: Bearer {token}
```

**Успешный ответ (200):**
```json
{
    "user": {
        "id": 123,
        "first_name": "Имя",
        "last_name": "Фамилия",
        "email": "user@example.com",
        "profile_photo": "user_123_1697123456.jpg",
        "profile_photo_url": "http://localhost:8000/storage/profile_photos/user_123_1697123456.jpg",
        // ... другие поля пользователя
    }
}
```

## Примеры использования для Frontend

### JavaScript (Fetch API)

#### Загрузка фотографии
```javascript
const uploadProfilePhoto = async (file, token) => {
    const formData = new FormData();
    formData.append('profile_photo', file);
    
    try {
        const response = await fetch('/api/v1/profile/upload-photo', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (response.ok) {
            console.log('Фотография загружена:', data.profile_photo_url);
            return data;
        } else {
            console.error('Ошибка загрузки:', data.message);
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Ошибка:', error);
        throw error;
    }
};
```

#### Удаление фотографии
```javascript
const deleteProfilePhoto = async (token) => {
    try {
        const response = await fetch('/api/v1/profile/delete-photo', {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            console.log('Фотография удалена');
            return data;
        } else {
            console.error('Ошибка удаления:', data.message);
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Ошибка:', error);
        throw error;
    }
};
```

### React пример

```jsx
import React, { useState } from 'react';

const ProfilePhotoUpload = ({ user, token, onPhotoUpdate }) => {
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState(null);

    const handleFileUpload = async (event) => {
        const file = event.target.files[0];
        if (!file) return;

        // Валидация на клиенте
        if (!file.type.startsWith('image/')) {
            setError('Пожалуйста, выберите изображение');
            return;
        }

        if (file.size > 2 * 1024 * 1024) { // 2MB
            setError('Размер файла не должен превышать 2MB');
            return;
        }

        setUploading(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('profile_photo', file);

            const response = await fetch('/api/v1/profile/upload-photo', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                onPhotoUpdate(data.user);
                setError(null);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Ошибка загрузки файла');
        } finally {
            setUploading(false);
        }
    };

    const handleDeletePhoto = async () => {
        setUploading(true);
        setError(null);

        try {
            const response = await fetch('/api/v1/profile/delete-photo', {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok) {
                onPhotoUpdate(data.user);
                setError(null);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Ошибка удаления файла');
        } finally {
            setUploading(false);
        }
    };

    return (
        <div className="profile-photo-upload">
            {user.profile_photo_url && (
                <div className="current-photo">
                    <img 
                        src={user.profile_photo_url} 
                        alt="Текущее фото профиля"
                        style={{ width: '100px', height: '100px', objectFit: 'cover' }}
                    />
                </div>
            )}
            
            <div className="upload-controls">
                <input
                    type="file"
                    accept="image/*"
                    onChange={handleFileUpload}
                    disabled={uploading}
                />
                
                {user.profile_photo && (
                    <button 
                        onClick={handleDeletePhoto}
                        disabled={uploading}
                        style={{ marginLeft: '10px' }}
                    >
                        Удалить фото
                    </button>
                )}
            </div>

            {uploading && <p>Загрузка...</p>}
            {error && <p style={{ color: 'red' }}>{error}</p>}
        </div>
    );
};

export default ProfilePhotoUpload;
```

## Важные замечания

1. **Автоматическое удаление**: При загрузке новой фотографии старая автоматически удаляется из файловой системы.

2. **Именование файлов**: Файлы сохраняются с уникальными именами в формате `user_{user_id}_{timestamp}.{extension}`.

3. **Публичный доступ**: Фотографии доступны через публичные URL, убедитесь, что символическая ссылка `storage` настроена правильно.

4. **Безопасность**: Все endpoints требуют аутентификации через Bearer token.

5. **Валидация**: Сервер проверяет тип файла, размер и формат изображения.

6. **Обработка ошибок**: Всегда обрабатывайте ошибки на клиенте и показывайте пользователю понятные сообщения.
