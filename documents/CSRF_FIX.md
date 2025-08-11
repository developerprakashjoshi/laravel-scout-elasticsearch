# CSRF Token Fix for API Endpoints

## Problem

The API endpoints were returning CSRF token mismatch errors because they were defined in the `web.php` routes file, which includes CSRF protection by default.

## Solution

### 1. Created API Routes File

Created `routes/api.php` to handle API endpoints without CSRF protection:

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::middleware('api')->group(function () {
    // Posts CRUD operations
    Route::apiResource('posts', PostController::class);
    
    // Additional API routes
    Route::get('/posts/stats', [PostController::class, 'stats']);
    Route::post('/posts/bulk', [PostController::class, 'bulkStore']);
    Route::post('/posts/search', [PostController::class, 'search']);
});
```

### 2. Updated Bootstrap Configuration

Updated `bootstrap/app.php` to include the API routes:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',  // Added this line
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

### 3. Updated API Endpoints

All API endpoints now use the `/api` prefix:

- `GET /api/posts` - List all posts
- `GET /api/posts/{id}` - Get specific post
- `POST /api/posts` - Create post
- `PUT /api/posts/{id}` - Update post
- `DELETE /api/posts/{id}` - Delete post
- `POST /api/posts/search` - Advanced search
- `POST /api/posts/bulk` - Bulk create
- `GET /api/posts/stats` - Get statistics

### 4. Updated Documentation

- Updated Postman collection to use `/api` prefix
- Updated API documentation with correct endpoints
- Updated README with new endpoint URLs

## Benefits

1. **No CSRF Protection**: API endpoints don't require CSRF tokens
2. **Proper Separation**: Web routes and API routes are properly separated
3. **Standard Practice**: Follows Laravel conventions for API endpoints
4. **Better Security**: API endpoints are isolated from web middleware

## Testing

You can now test the API without CSRF token issues:

```bash
# Test GET endpoint
curl -X GET "http://localhost:8000/api/posts" \
  -H "Accept: application/json"

# Test POST endpoint
curl -X POST "http://localhost:8000/api/posts" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Post",
    "content": "Test content",
    "author": "Test Author"
  }'
```

## Postman Collection

The Postman collection has been updated with the correct base URL:
- Base URL: `http://localhost:8000/api`
- All endpoints now work without CSRF tokens

## Files Modified

1. `routes/api.php` - Created new API routes file
2. `routes/web.php` - Removed API routes from web routes
3. `bootstrap/app.php` - Added API routes configuration
4. `Laravel_Scout_Elasticsearch_API.postman_collection.json` - Updated base URL
5. `API_DOCUMENTATION.md` - Updated all endpoint URLs
6. `README.md` - Updated endpoint examples

The API is now ready to use without CSRF token issues! 