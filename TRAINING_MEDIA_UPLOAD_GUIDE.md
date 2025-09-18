# Training Media Upload Guide

This guide explains how to use the unified media upload functionality for trainings in the Agrar Portal API.

## Overview

The training system now uses a **single unified `media_files` property** to handle all types of media files with proper categorization:
- **Banner Images**: Visual banners for training courses (type: `banner`)
- **Intro Videos**: Introduction videos for trainings (type: `intro_video`)
- **General Media Files**: PDFs, presentations, documents, images, and other files (type: `general`)

## Database Changes

### Unified Media Structure:
- `media_files` (JSON, nullable) - Single array containing all media files with type categorization

### Migration Applied:
```bash
php artisan migrate
# Applied: 2025_09_07_132738_refactor_training_media_to_single_property
```

### Media File Structure:
Each media file in the `media_files` array contains:
```json
{
  "type": "banner|intro_video|general",
  "path": "trainings/banners/file_123.jpg",
  "url": "http://localhost:8000/storage/trainings/banners/file_123.jpg",
  "original_name": "My_Banner.jpg",
  "mime_type": "image/jpeg",
  "size": 1048576,
  "uploaded_at": "2025-09-07T13:30:00.000Z"
}
```

## API Endpoints

All media upload functionality is integrated into existing training endpoints with the same interface:

### 1. Create Training with Media
**POST** `/api/v1/trainings`

**Content-Type**: `multipart/form-data`

**Form Fields**:
```
title: "Training Title"
description: "Training description"
category: "Training Category"
trainer_id: 1
start_date: "2025-01-01"
end_date: "2025-12-31"
is_online: true
banner_image: [FILE] (max 5MB - jpg,jpeg,png,gif,webp)
intro_video: [FILE] (max 100MB - mp4,avi,mov,wmv,flv,webm)
media_files[]: [FILE] (max 50MB each - any format)
media_files[]: [FILE] (additional files)
```

### 2. Update Training with Media
**PATCH** `/api/v1/trainings/{id}`

**Content-Type**: `multipart/form-data`

**Form Fields** (all optional):
```
title: "Updated Title"
description: "Updated description"
banner_image: [FILE] (replaces existing banner)
intro_video: [FILE] (replaces existing video)
media_files[]: [FILE] (adds new media files)
```

### 3. Remove Media Files
**PATCH** `/api/v1/trainings/{id}`

**Content-Type**: `application/json`

**Request Body**:
```json
{
  "remove_banner": true,
  "remove_intro_video": true,
  "remove_media_files": [
    "trainings/media/old_document.pdf",
    "trainings/media/old_presentation.pptx"
  ]
}
```

### 4. Get Training with Media
**GET** `/api/v1/trainings/{id}`

**Response Example**:
```json
{
  "id": 1,
  "title": "Advanced Crop Management",
  "description": "Learn advanced farming techniques",
  "category": "Crop Management",
  "trainer_id": 2,
  "start_date": "2025-01-01",
  "end_date": "2025-12-31",
  "is_online": true,
  "media_files": [
    {
      "type": "banner",
      "path": "trainings/banners/banner_123.jpg",
      "url": "http://localhost:8000/storage/trainings/banners/banner_123.jpg",
      "original_name": "Course_Banner.jpg",
      "mime_type": "image/jpeg",
      "size": 1048576,
      "uploaded_at": "2025-09-07T13:30:00.000Z"
    },
    {
      "type": "intro_video",
      "path": "trainings/videos/intro_456.mp4",
      "url": "http://localhost:8000/storage/trainings/videos/intro_456.mp4",
      "original_name": "Introduction.mp4",
      "mime_type": "video/mp4",
      "size": 52428800,
      "uploaded_at": "2025-09-07T13:32:00.000Z"
    },
    {
      "type": "general",
      "path": "trainings/media/document_789.pdf",
      "url": "http://localhost:8000/storage/trainings/media/document_789.pdf",
      "original_name": "Training_Manual.pdf",
      "mime_type": "application/pdf",
      "size": 2048576,
      "uploaded_at": "2025-09-07T13:35:00.000Z"
    },
    {
      "type": "general",
      "path": "trainings/media/presentation_101.pptx",
      "url": "http://localhost:8000/storage/trainings/media/presentation_101.pptx",
      "original_name": "Course_Slides.pptx",
      "mime_type": "application/vnd.openxmlformats-officedocument.presentationml.presentation",
      "size": 5242880,
      "uploaded_at": "2025-09-07T13:40:00.000Z"
    }
  ],
  "banner_image": "http://localhost:8000/storage/trainings/banners/banner_123.jpg",
  "intro_video": "http://localhost:8000/storage/trainings/videos/intro_456.mp4",
  "general_media_files": [
    {
      "type": "general",
      "path": "trainings/media/document_789.pdf",
      "url": "http://localhost:8000/storage/trainings/media/document_789.pdf",
      "original_name": "Training_Manual.pdf",
      "mime_type": "application/pdf",
      "size": 2048576,
      "uploaded_at": "2025-09-07T13:35:00.000Z"
    },
    {
      "type": "general",
      "path": "trainings/media/presentation_101.pptx",
      "url": "http://localhost:8000/storage/trainings/media/presentation_101.pptx",
      "original_name": "Course_Slides.pptx",
      "mime_type": "application/vnd.openxmlformats-officedocument.presentationml.presentation",
      "size": 5242880,
      "uploaded_at": "2025-09-07T13:40:00.000Z"
    }
  ],
  "created_at": "2025-09-07T13:20:00.000Z",
  "updated_at": "2025-09-07T13:40:00.000Z",
  "modules": []
}
```

### 5. Delete Training
**DELETE** `/api/v1/trainings/{id}`

Automatically deletes all associated media files from storage.

## Model Accessor Methods

The Training model provides convenient accessor methods:

### Get Banner Image
```php
$training->banner_image // Returns URL of first media file with type 'banner'
```

### Get Intro Video
```php
$training->intro_video // Returns URL of first media file with type 'intro_video'
```

### Get General Media Files
```php
$training->general_media_files // Returns array of media files excluding banner and intro_video
```

### Get All Media Files
```php
$training->media_files // Returns all media files with full URLs
```

## File Upload Specifications

### Banner Images
- **Type**: `banner`
- **Formats**: JPG, JPEG, PNG, GIF, WebP
- **Max Size**: 5MB
- **Storage Path**: `storage/app/public/trainings/banners/`
- **Behavior**: Replaces existing banner when new one is uploaded

### Intro Videos
- **Type**: `intro_video`
- **Formats**: MP4, AVI, MOV, WMV, FLV, WebM
- **Max Size**: 100MB
- **Storage Path**: `storage/app/public/trainings/videos/`
- **Behavior**: Replaces existing intro video when new one is uploaded

### General Media Files
- **Type**: `general`
- **Formats**: Any file type
- **Max Size**: 50MB per file
- **Storage Path**: `storage/app/public/trainings/media/`
- **Behavior**: Adds to existing media files (no replacement)

## Postman Collection Examples

### Create Training with Media
```bash
curl -X POST "{{base_url}}/api/v1/trainings" \
  -H "Authorization: Bearer {{auth_token}}" \
  -F "title=Advanced Crop Management" \
  -F "description=Learn advanced farming techniques" \
  -F "category=Crop Management" \
  -F "trainer_id=2" \
  -F "is_online=true" \
  -F "banner_image=@/path/to/banner.jpg" \
  -F "intro_video=@/path/to/intro.mp4" \
  -F "media_files[]=@/path/to/manual.pdf" \
  -F "media_files[]=@/path/to/slides.pptx"
```

### Update Training - Replace Banner
```bash
curl -X PATCH "{{base_url}}/api/v1/trainings/1" \
  -H "Authorization: Bearer {{auth_token}}" \
  -F "banner_image=@/path/to/new_banner.jpg"
```

### Update Training - Add Media Files
```bash
curl -X PATCH "{{base_url}}/api/v1/trainings/1" \
  -H "Authorization: Bearer {{auth_token}}" \
  -F "media_files[]=@/path/to/additional_doc.pdf" \
  -F "media_files[]=@/path/to/extra_image.jpg"
```

### Remove Specific Media Files
```bash
curl -X PATCH "{{base_url}}/api/v1/trainings/1" \
  -H "Authorization: Bearer {{auth_token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "remove_banner": true,
    "remove_intro_video": true,
    "remove_media_files": ["trainings/media/old_file.pdf"]
  }'
```

## Media Type Management

### Banner Image Management
- Only one banner image per training
- Uploading a new banner replaces the existing one
- Use `remove_banner: true` to remove banner without replacement

### Intro Video Management
- Only one intro video per training
- Uploading a new intro video replaces the existing one
- Use `remove_intro_video: true` to remove video without replacement

### General Media Files Management
- Multiple general media files allowed
- New files are added to existing collection
- Use `remove_media_files` array to remove specific files by path

## Security & Permissions

- **Authentication**: Bearer token required
- **Authorization**: Admin or Trainer roles only
- **File Validation**: MIME type and size validation enforced
- **Storage**: Files stored in Laravel's public disk with proper permissions

## Error Handling

### Common Validation Errors:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "banner_image": ["The banner image must be an image.", "The banner image may not be greater than 5120 kilobytes."],
    "intro_video": ["The intro video must be a file of type: mp4, avi, mov, wmv, flv, webm."],
    "media_files.0": ["The media files.0 may not be greater than 51200 kilobytes."]
  }
}
```

## Advantages of Unified Structure

1. **Simplified Database Schema**: Single JSON column instead of multiple fields
2. **Flexible Media Types**: Easy to add new media types without schema changes
3. **Consistent API**: Same endpoints handle all media types
4. **Rich Metadata**: Each file includes comprehensive metadata
5. **Type Safety**: Clear categorization with type field
6. **Backward Compatibility**: Accessor methods provide familiar interface

## Migration Notes

- Existing separate `banner_image_url` and `intro_video_url` fields have been removed
- All media files are now stored in the unified `media_files` JSON structure
- Accessor methods provide backward-compatible access patterns
- File storage locations remain the same for organization

## Testing

Use the provided test script:
```bash
./test_training_media_upload.sh
```

## Support

For issues or questions regarding the unified media upload functionality:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify file permissions on storage directories
3. Ensure storage symlink exists: `php artisan storage:link`
4. Check available disk space for large file uploads 