# Postman Exam Management Testing Guide

This guide provides step-by-step instructions for testing the enhanced exam management APIs using the updated Postman collection.

## Prerequisites

1. **Import the Collection**: Import `Agrar_Portal_API.postman_collection.json` into Postman
2. **Set Environment Variables**:
   - `base_url`: Your API base URL (e.g., `http://localhost:8000`)
   - `auth_token`: Admin authentication token
   - `user_email`: Admin email address
   - `default_password`: Default password for testing

## Authentication Setup

### Step 1: Login as Admin
1. Use **Authentication > Login Admin** request
2. Set the request body with valid admin credentials:
   ```json
   {
     "email": "admin@example.com",
     "password": "password123"
   }
   ```
3. Send the request
4. Copy the `token` from the response
5. Update the `auth_token` environment variable with this token

### Step 2: Verify Admin Access
1. Test any admin endpoint to ensure proper authentication
2. All exam management endpoints require admin role

## Testing the Enhanced Exam Management APIs

### 1. Dashboard Statistics

**Request**: `GET /api/v1/exams/stats`
- **Endpoint**: Exam Management (Admin Dashboard) > Get Dashboard Statistics
- **Purpose**: Get overview statistics for the admin dashboard
- **Expected Response**:
```json
{
  "total_exams": 127,
  "active_exams": 45,
  "upcoming_exams": 12,
  "total_registrations": 1847,
  "completed_exams": 1203,
  "average_score": 76.4,
  "completion_rate": 65.2
}
```

### 2. Form Data for Dropdowns

**Request**: `GET /api/v1/exams/form-data`
- **Endpoint**: Exam Management (Admin Dashboard) > Get Form Data
- **Purpose**: Get data needed for create/edit forms
- **Expected Response**:
```json
{
  "categories": ["Bitki Becerilmesi", "Torpaq Sağlamlığı"],
  "trainings": [
    {
      "id": 1,
      "title": "Training Title",
      "category": "Category Name",
      "trainer": {
        "id": 5,
        "first_name": "Trainer",
        "last_name": "Name"
      }
    }
  ],
  "trainers": [...]
}
```

### 3. Enhanced Exam Listing with Filtering

**Request**: `GET /api/v1/exams` (with query parameters)
- **Endpoint**: Exam Management (Admin Dashboard) > List Exams (Enhanced with Filtering)
- **Test Different Scenarios**:

#### Basic Listing
- Remove all query parameters
- Should return paginated list of all exams

#### Search Functionality
- Enable `search` parameter: `bitki`
- Should return exams matching the search term in title, description, or related training

#### Category Filtering
- Enable `category` parameter: `Bitki Becerilmesi`
- Should return only exams from trainings in that category

#### Status Filtering
- Enable `status` parameter with values:
  - `upcoming`: Future exams
  - `active`: Currently running exams
  - `ended`: Past exams

#### Sorting
- Test different `sort_by` values: `title`, `created_at`, `start_date`, `end_date`, `passing_score`
- Test `sort_order`: `asc`, `desc`

#### Pagination
- Test `per_page`: `10`, `20`, `50`
- Test `page`: `1`, `2`, etc.

**Expected Response Structure**:
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "title": "Exam Title",
      "status": "active",
      "completion_rate": 78.5,
      "pass_rate": 89.2,
      "training": {
        "title": "Training Title",
        "category": "Category"
      },
      "questions_count": 25,
      "registrations_count": 156
    }
  ],
  "total": 127,
  "per_page": 15,
  "last_page": 9
}
```

### 4. Create Exam

**Request**: `POST /api/v1/exams`
- **Endpoint**: Exam Management (Admin Dashboard) > Create Exam (Enhanced)
- **Test Body**:
```json
{
  "training_id": 1,
  "title": "Test Exam Creation",
  "description": "Test exam description...",
  "passing_score": 70,
  "duration_minutes": 60,
  "start_date": "2024-12-01",
  "end_date": "2024-12-31"
}
```

**Validation Tests**:
1. **Missing required fields**: Remove `training_id` - should return 422 error
2. **Invalid passing_score**: Use `110` - should return 422 error
3. **Invalid duration**: Use `500` - should return 422 error
4. **Invalid dates**: Use past date for `start_date` - should return 422 error
5. **Valid request**: Should return 201 with created exam

### 5. Get Exam Details

**Request**: `GET /api/v1/exams/{id}`
- **Endpoint**: Exam Management (Admin Dashboard) > Get Exam Details (Enhanced)
- **Purpose**: Get detailed exam information with statistics
- **Expected Response**: Should include exam data, questions, recent registrations, and statistics

### 6. Update Exam

**Request**: `PUT /api/v1/exams/{id}`
- **Endpoint**: Exam Management (Admin Dashboard) > Update Exam (Enhanced)

**Test Scenarios**:
1. **Full Update**: Update all fields
2. **Partial Update**: Use PATCH method with only some fields
3. **Invalid Data**: Test validation rules
4. **Non-existent Exam**: Use invalid ID - should return 404

### 7. Delete Exam

**Request**: `DELETE /api/v1/exams/{id}`
- **Endpoint**: Exam Management (Admin Dashboard) > Delete Exam (Enhanced)

**Test Scenarios**:
1. **Exam without registrations**: Should delete successfully
2. **Exam with registrations**: Should return 422 error with registration count
3. **Non-existent exam**: Should return 404 error

## Testing Workflow

### Complete Admin Dashboard Testing Flow

1. **Dashboard Overview**:
   ```
   GET /api/v1/exams/stats
   GET /api/v1/exams/form-data
   ```

2. **List and Filter Exams**:
   ```
   GET /api/v1/exams
   GET /api/v1/exams?search=test
   GET /api/v1/exams?category=Bitki%20Becerilmesi
   GET /api/v1/exams?status=active
   ```

3. **Create New Exam**:
   ```
   POST /api/v1/exams
   ```

4. **View Exam Details**:
   ```
   GET /api/v1/exams/{created_exam_id}
   ```

5. **Update Exam**:
   ```
   PUT /api/v1/exams/{created_exam_id}
   PATCH /api/v1/exams/{created_exam_id}
   ```

6. **Delete Exam**:
   ```
   DELETE /api/v1/exams/{created_exam_id}
   ```

## Error Testing

### Authentication Errors
1. **No Token**: Remove `Authorization` header - should return 401
2. **Invalid Token**: Use wrong token - should return 401
3. **Non-Admin User**: Use student/trainer token - should return 403

### Validation Errors
Test each endpoint with invalid data to ensure proper validation:

1. **Invalid Training ID**: Use non-existent training_id
2. **Invalid Score Range**: Use negative or >100 passing_score
3. **Invalid Duration**: Use 0 or >480 duration_minutes
4. **Invalid Date Format**: Use wrong date format
5. **Invalid Date Logic**: end_date before start_date

### Resource Errors
1. **Non-existent Resources**: Use invalid exam IDs
2. **Constraint Violations**: Try to delete exam with registrations

## Expected Status Codes

- **200**: Successful GET, PUT, PATCH requests
- **201**: Successful POST (create) requests
- **401**: Unauthorized (missing/invalid token)
- **403**: Forbidden (insufficient permissions)
- **404**: Resource not found
- **422**: Validation errors
- **500**: Server errors

## Sample Test Scenarios

### Scenario 1: Complete Exam Lifecycle
1. Get dashboard stats (should show initial counts)
2. Create new exam
3. Verify exam appears in listing
4. Update exam details
5. Get exam details (verify changes)
6. Delete exam
7. Verify exam no longer appears in listing

### Scenario 2: Search and Filter Testing
1. Create exams with different categories and titles
2. Test search functionality with various terms
3. Test category filtering
4. Test status filtering with different date ranges
5. Test sorting by different fields
6. Test pagination with different page sizes

### Scenario 3: Validation Testing
1. Test each validation rule individually
2. Verify appropriate error messages
3. Test edge cases (boundary values)
4. Test required vs optional fields

## Tips for Effective Testing

1. **Environment Management**: Use different environments for testing (dev, staging)
2. **Data Setup**: Ensure test data exists (trainings, categories, users)
3. **Clean Up**: Delete test data after testing
4. **Automation**: Consider using Postman's test scripts for automated validation
5. **Documentation**: Keep track of test results and any issues found

## Troubleshooting

### Common Issues

1. **403 Forbidden**: Ensure you're using an admin token
2. **422 Validation Error**: Check request body format and required fields
3. **404 Not Found**: Verify the exam ID exists
4. **500 Server Error**: Check server logs for detailed error information

### Debug Steps

1. **Check Authentication**: Verify token is valid and user has admin role
2. **Validate Request**: Ensure JSON format is correct
3. **Check Dependencies**: Ensure referenced trainings exist
4. **Review Logs**: Check application logs for detailed error messages

This testing guide should help you thoroughly test all the enhanced exam management functionality for your admin dashboard.
