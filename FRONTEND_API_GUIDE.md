# Educational Content API Guide - Frontend

## Endpoints

### Create Educational Content
```
POST /api/v1/education
```

### Get Single Educational Content (Detail Page)
```
GET /api/v1/education/{id}
```

### Get All Articles
```
GET /api/v1/meqaleler
```

### Get All Telimats
```
GET /api/v1/telimats
```

## Authentication
Əvvəlcə token almalısan (Bearer token tələb olunur)

## Complete Example - React/JavaScript

### 1. Meqale (Article) yaratmaq

```jsx
import React, { useState } from 'react';

const CreateArticle = () => {
  const [formData, setFormData] = useState({
    type: 'meqale',
    title: '',
    short_description: '',
    body_html: '',
    sequence: 1,
    hashtags: '',
    category: '',
    send_to_our_user: false,
    image: null,
    media_files: [],
    seo: {
      meta_title: '',
      meta_desc: '',
      meta_tags: [],
      key_words: [],
      canonical_url: '',
      og_title: '',
      og_description: '',
      og_image: ''
    }
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Create FormData
    const data = new FormData();
    
    // Basic fields
    data.append('type', 'meqale');
    data.append('title', formData.title);
    data.append('short_description', formData.short_description);
    data.append('body_html', formData.body_html);
    data.append('sequence', formData.sequence);
    data.append('hashtags', formData.hashtags);
    data.append('category', formData.category);
    data.append('send_to_our_user', formData.send_to_our_user ? 'true' : 'false');
    
    // SEO data
    data.append('seo[meta_title]', formData.seo.meta_title);
    data.append('seo[meta_desc]', formData.seo.meta_desc);
    
    // SEO arrays (meta_tags)
    formData.seo.meta_tags.forEach(tag => {
      data.append('seo[meta_tags][]', tag);
    });
    
    // SEO arrays (key_words)
    formData.seo.key_words.forEach(word => {
      data.append('seo[key_words][]', word);
    });
    
    data.append('seo[canonical_url]', formData.seo.canonical_url);
    data.append('seo[og_title]', formData.seo.og_title);
    data.append('seo[og_description]', formData.seo.og_description);
    data.append('seo[og_image]', formData.seo.og_image);
    
    // Image
    if (formData.image) {
      data.append('image', formData.image);
    }
    
    // Media files
    formData.media_files.forEach((file, index) => {
      data.append(`media_files[${index}][name]`, file.name);
      data.append(`media_files[${index}][file]`, file.file);
      data.append(`media_files[${index}][type]`, file.type);
    });
    
    try {
      const response = await fetch('http://localhost:8000/api/v1/education', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          // Don't set Content-Type! Let browser set it for FormData
        },
        body: data
      });
      
      const result = await response.json();
      console.log('Success:', result);
    } catch (error) {
      console.error('Error:', error);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* Your form inputs here */}
    </form>
  );
};
```

## Field Descriptions

### Required Fields
- `type`: 'meqale' | 'telimat' | 'elan'

### Meqale Fields (Optional but recommended)
- `title`: Article title (max 255 chars)
- `short_description`: Short description of article
- `body_html`: Full HTML content of article
- `sequence`: Display order (integer, min 1)
- `hashtags`: Comma-separated hashtags
- `category`: Article category
- `send_to_our_user`: Boolean (true/false)

### Image Upload
- `image`: File object (image only, max 5MB)
- Supported formats: jpg, jpeg, png, gif, webp

### Media Files
- `media_files`: Array of objects
  - `name`: Display name
  - `file`: File object
  - `type`: 'image' | 'video' | 'pdf' | 'audio' | etc.
- Max size: 50MB per file

### SEO Data
All SEO fields are optional:
- `seo[meta_title]`: Page title for SEO
- `seo[meta_desc]`: Meta description
- `seo[meta_tags][]`: Array of meta tags
- `seo[key_words][]`: Array of keywords
- `seo[canonical_url]`: Canonical URL
- `seo[og_title]`: Open Graph title
- `seo[og_description]`: Open Graph description
- `seo[og_image]`: Open Graph image URL

## Response Examples

### Create Response
```json
{
  "message": "Educational content created",
  "content": {
    "id": 5,
    "type": "meqale",
    "title": "Test Article",
    "short_description": "Short description",
    "image_url": "http://localhost:8000/storage/education/xyz.jpg",
    "media_files": [
      {
        "name": "sekil-ana",
        "path": "education/media/abc.jpg",
        "type": "image",
        "url": "http://localhost:8000/storage/education/media/abc.jpg"
      }
    ],
    "created_at": "2025-10-30T01:00:00.000000Z"
  }
}
```

### Single Content Detail Response (GET /api/v1/education/{id})
```json
{
  "id": 8,
  "type": "meqale",
  "title": "Torpaq Becərilməsi və Kənd Təsərrüfatı",
  "likes_count": 15,
  "is_liked": false,
  "is_saved": false,
  "short_description": "Azərbaycanda torpaq becərilməsi üçün əsas prinsiplər",
  "body_html": "<p>Ətraflı mətn...</p>",
  "sequence": 1,
  "hashtags": "#torpaq #kəndtəsərrüfatı",
  "category": "Aqrar texnika",
  "send_to_our_user": false,
  "image_path": "education/xyz123.jpg",
  "image_url": "http://localhost:8000/storage/education/xyz123.jpg",
  "media_files": [
    {
      "name": "sekil-ana",
      "path": "education/media/abc.jpg",
      "type": "image",
      "url": "http://localhost:8000/storage/education/media/abc.jpg"
    },
    {
      "name": "video-material",
      "path": "education/media/video.mp4",
      "type": "video",
      "url": "http://localhost:8000/storage/education/media/video.mp4"
    }
  ],
  "seo": {
    "meta_title": "Torpaq Becərilməsi",
    "meta_desc": "Azərbaycanda torpaq becərilməsi haqqında",
    "meta_tags": ["torpaq", "kənd təsərrüfatı"],
    "key_words": ["torpaq becərilməsi", "aqrar"],
    "canonical_url": "https://aqrar.az/education/meqale/torpaq",
    "og_title": "Torpaq Becərilməsi",
    "og_description": "Torpaq becərilməsi haqqında",
    "og_image": "https://aqrar.az/storage/education/og.jpg"
  },
  "created_by": 1,
  "creator": {
    "id": 1,
    "first_name": "Umud",
    "last_name": "Abbasli"
  },
  "created_at": "2025-10-30T01:00:00.000000Z",
  "updated_at": "2025-10-30T01:00:00.000000Z"
}
```

### Frontend Usage - Get Single Article
```javascript
// React/Vue/vanilla JS example
const getArticleDetail = async (id) => {
  const response = await fetch(`http://localhost:8000/api/v1/education/${id}`, {
    method: 'GET',
    // No authentication required for public endpoint
  });
  
  const data = await response.json();
  console.log(data);
  
  // Use data in your component
  // data.title, data.body_html, data.image_url, etc.
};
```

## Important Notes

1. **Use FormData**: When uploading files, always use `FormData`, not JSON
2. **Don't set Content-Type header**: Browser will automatically set it with boundary
3. **Array fields**: Use `fieldName[]` syntax for arrays
4. **Nested objects**: Use `parent[key]` syntax for nested objects
5. **File size**: Image max 5MB, media files max 50MB each
6. **Authentication**: Include Bearer token in Authorization header
7. **Image URL fallback**: If no image uploaded, will use `seo[og_image]`

## Example using Axios

```javascript
import axios from 'axios';

const createArticle = async (data) => {
  const formData = new FormData();
  
  // Add all fields
  Object.keys(data).forEach(key => {
    if (key === 'media_files' || key === 'image') {
      // Handle files separately
      return;
    }
    
    if (key === 'seo') {
      // Handle SEO nested object
      Object.keys(data[key]).forEach(seoKey => {
        if (Array.isArray(data[key][seoKey])) {
          data[key][seoKey].forEach(item => {
            formData.append(`seo[${seoKey}][]`, item);
          });
        } else {
          formData.append(`seo[${seoKey}]`, data[key][seoKey]);
        }
      });
      return;
    }
    
    formData.append(key, data[key]);
  });
  
  // Add files
  if (data.image) {
    formData.append('image', data.image);
  }
  
  if (data.media_files && data.media_files.length > 0) {
    data.media_files.forEach((file, index) => {
      formData.append(`media_files[${index}][name]`, file.name);
      formData.append(`media_files[${index}][file]`, file.file);
      formData.append(`media_files[${index}][type]`, file.type);
    });
  }
  
  const response = await axios.post('http://localhost:8000/api/v1/education', formData, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'multipart/form-data'
    }
  });
  
  return response.data;
};
```

## Like/Save/Unsave Endpoints

### Like Article
```javascript
// POST /api/v1/education/{id}/like
const likeArticle = async (articleId) => {
  const response = await fetch(`http://localhost:8000/api/v1/education/${articleId}/like`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
};

// Response: { "message": "Content liked successfully", "is_liked": true, "likes_count": 16 }
```

### Unlike Article
```javascript
// POST /api/v1/education/{id}/unlike
const unlikeArticle = async (articleId) => {
  const response = await fetch(`http://localhost:8000/api/v1/education/${articleId}/unlike`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
};

// Response: { "message": "Content unliked successfully", "is_liked": false, "likes_count": 15 }
```

### Save Article (Bookmark)
```javascript
// POST /api/v1/education/{id}/save
const saveArticle = async (articleId) => {
  const response = await fetch(`http://localhost:8000/api/v1/education/${articleId}/save`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
};

// Response: { "message": "Content saved successfully", "is_saved": true }
```

### Unsave Article
```javascript
// POST /api/v1/education/{id}/unsave
const unsaveArticle = async (articleId) => {
  const response = await fetch(`http://localhost:8000/api/v1/education/${articleId}/unsave`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  
  return await response.json();
};

// Response: { "message": "Content unsaved successfully", "is_saved": false }
```

### Get My Saved Articles
```javascript
// GET /api/v1/my-saved-articles
const getMySavedArticles = async (page = 1) => {
  const response = await fetch(`http://localhost:8000/api/v1/my-saved-articles?page=${page}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
    }
  });
  
  return await response.json();
};

// Response: Paginated list of saved articles
```

## Complete React Example with Like/Save Functionality

```jsx
import React, { useState, useEffect } from 'react';

const ArticleDetail = ({ articleId }) => {
  const [article, setArticle] = useState(null);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    fetchArticle();
  }, [articleId]);
  
  const fetchArticle = async () => {
    const response = await fetch(`http://localhost:8000/api/v1/education/${articleId}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    });
    const data = await response.json();
    setArticle(data);
    setLoading(false);
  };
  
  const handleLike = async () => {
    const endpoint = article.is_liked ? 'unlike' : 'like';
    const response = await fetch(`http://localhost:8000/api/v1/education/${articleId}/${endpoint}`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    });
    const data = await response.json();
    setArticle({ ...article, ...data });
  };
  
  const handleSave = async () => {
    const endpoint = article.is_saved ? 'unsave' : 'save';
    const response = await fetch(`http://localhost:8000/api/v1/education/${articleId}/${endpoint}`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    });
    const data = await response.json();
    setArticle({ ...article, ...data });
  };
  
  if (loading) return <div>Loading...</div>;
  
  return (
    <div>
      <h1>{article.title}</h1>
      <div>
        <button onClick={handleLike}>
          {article.is_liked ? '❤️' : '🤍'} {article.likes_count}
        </button>
        <button onClick={handleSave}>
          {article.is_saved ? '🔖' : '📄'} Saved
        </button>
      </div>
      <div dangerouslySetInnerHTML={{ __html: article.body_html }} />
    </div>
  );
};
```


