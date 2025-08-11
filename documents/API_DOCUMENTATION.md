# Laravel Scout with Elasticsearch API Documentation

This document provides comprehensive documentation for the Laravel Scout with Elasticsearch API, including all endpoints, request/response formats, and examples.

## Base URL

```
http://localhost:8000/api
```

## Authentication

Currently, the API does not require authentication. All endpoints are publicly accessible.

## Response Format

All API responses follow a consistent format:

### Success Response
```json
{
    "success": true,
    "data": [...],
    "message": "Operation successful"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": {...} // For validation errors
}
```

## Endpoints

### 1. Posts CRUD Operations

#### GET /posts - Get All Posts

Retrieve all posts with optional pagination and filtering.

**Query Parameters:**
- `q` (string, optional): Search query
- `author` (string, optional): Filter by author
- `published_after` (date, optional): Filter posts published after this date
- `published_before` (date, optional): Filter posts published before this date
- `per_page` (integer, optional): Number of items per page (default: 10, max: 100)
- `page` (integer, optional): Page number (default: 1)

**Example Request:**
```bash
GET /posts?q=Laravel&author=John&per_page=5
```

**Example Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Getting Started with Laravel Scout",
            "content": "Laravel Scout provides a simple...",
            "author": "John Doe",
            "published_at": "2024-01-15T10:00:00.000000Z",
            "created_at": "2024-01-15T10:00:00.000000Z",
            "updated_at": "2024-01-15T10:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 5,
        "total": 1,
        "from": 1,
        "to": 1
    },
    "filters": {
        "search_query": "Laravel",
        "author": "John",
        "published_after": null,
        "published_before": null
    }
}
```

#### GET /posts/{id} - Get Post by ID

Retrieve a specific post by its ID.

**Example Request:**
```bash
GET /posts/1
```

**Example Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Getting Started with Laravel Scout",
        "content": "Laravel Scout provides a simple...",
        "author": "John Doe",
        "published_at": "2024-01-15T10:00:00.000000Z",
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### POST /posts - Create Post

Create a new post.

**Request Body:**
```json
{
    "title": "New Post Title",
    "content": "This is the content of the new post.",
    "author": "John Doe",
    "published_at": "2024-01-15T10:00:00Z"
}
```

**Validation Rules:**
- `title`: required, string, max 255 characters
- `content`: required, string
- `author`: required, string, max 255 characters
- `published_at`: optional, valid date

**Example Response:**
```json
{
    "success": true,
    "message": "Post created successfully",
    "data": {
        "id": 10,
        "title": "New Post Title",
        "content": "This is the content of the new post.",
        "author": "John Doe",
        "published_at": "2024-01-15T10:00:00.000000Z",
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### PUT /posts/{id} - Update Post

Update an existing post.

**Request Body:**
```json
{
    "title": "Updated Post Title",
    "content": "This is the updated content.",
    "author": "Jane Smith",
    "published_at": "2024-01-16T10:00:00Z"
}
```

**Validation Rules:**
- `title`: optional, string, max 255 characters
- `content`: optional, string
- `author`: optional, string, max 255 characters
- `published_at`: optional, valid date

**Example Response:**
```json
{
    "success": true,
    "message": "Post updated successfully",
    "data": {
        "id": 1,
        "title": "Updated Post Title",
        "content": "This is the updated content.",
        "author": "Jane Smith",
        "published_at": "2024-01-16T10:00:00.000000Z",
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-16T10:00:00.000000Z"
    }
}
```

#### DELETE /posts/{id} - Delete Post

Delete a post by its ID.

**Example Request:**
```bash
DELETE /posts/1
```

**Example Response:**
```json
{
    "success": true,
    "message": "Post deleted successfully"
}
```

### 2. Advanced Search

#### POST /posts/search - Advanced Search

Perform advanced search with multiple filters.

**Request Body:**
```json
{
    "query": "Laravel Scout",
    "per_page": 10,
    "author": "John Doe",
    "published_after": "2024-01-01T00:00:00Z",
    "published_before": "2024-12-31T23:59:59Z",
    "sort_by": "created_at",
    "sort_order": "desc"
}
```

**Validation Rules:**
- `query`: required, string, min 2 characters
- `per_page`: optional, integer, min 1, max 100
- `author`: optional, string
- `published_after`: optional, valid date
- `published_before`: optional, valid date
- `sort_by`: optional, one of: title, author, created_at, published_at
- `sort_order`: optional, one of: asc, desc

**Example Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Getting Started with Laravel Scout",
            "content": "Laravel Scout provides a simple...",
            "author": "John Doe",
            "published_at": "2024-01-15T10:00:00.000000Z",
            "created_at": "2024-01-15T10:00:00.000000Z",
            "updated_at": "2024-01-15T10:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1,
        "from": 1,
        "to": 1
    },
    "search_info": {
        "query": "Laravel Scout",
        "total_results": 1,
        "filters_applied": {
            "author": "John Doe",
            "published_after": "2024-01-01T00:00:00Z",
            "published_before": "2024-12-31T23:59:59Z"
        }
    }
}
```

### 3. Bulk Operations

#### POST /posts/bulk - Bulk Create Posts

Create multiple posts in a single request.

**Request Body:**
```json
{
    "posts": [
        {
            "title": "First Bulk Post",
            "content": "This is the first post created in bulk.",
            "author": "John Doe",
            "published_at": "2024-01-15T10:00:00Z"
        },
        {
            "title": "Second Bulk Post",
            "content": "This is the second post created in bulk.",
            "author": "Jane Smith",
            "published_at": "2024-01-16T10:00:00Z"
        }
    ]
}
```

**Validation Rules:**
- `posts`: required, array, min 1, max 10 items
- `posts.*.title`: required, string, max 255 characters
- `posts.*.content`: required, string
- `posts.*.author`: required, string, max 255 characters
- `posts.*.published_at`: optional, valid date

**Example Response:**
```json
{
    "success": true,
    "message": "2 posts created successfully",
    "data": [
        {
            "id": 10,
            "title": "First Bulk Post",
            "content": "This is the first post created in bulk.",
            "author": "John Doe",
            "published_at": "2024-01-15T10:00:00.000000Z",
            "created_at": "2024-01-15T10:00:00.000000Z",
            "updated_at": "2024-01-15T10:00:00.000000Z"
        },
        {
            "id": 11,
            "title": "Second Bulk Post",
            "content": "This is the second post created in bulk.",
            "author": "Jane Smith",
            "published_at": "2024-01-16T10:00:00.000000Z",
            "created_at": "2024-01-15T10:00:00.000000Z",
            "updated_at": "2024-01-15T10:00:00.000000Z"
        }
    ]
}
```

### 4. Statistics

#### GET /posts/stats - Get Posts Statistics

Retrieve comprehensive statistics about posts.

**Example Request:**
```bash
GET /posts/stats
```

**Example Response:**
```json
{
    "success": true,
    "data": {
        "total_posts": 10,
        "published_posts": 8,
        "draft_posts": 2,
        "unique_authors": 5,
        "latest_post": {
            "id": 10,
            "title": "Latest Post Title",
            "created_at": "2024-01-15T10:00:00.000000Z"
        },
        "oldest_post": {
            "id": 1,
            "title": "Oldest Post Title",
            "created_at": "2024-01-01T10:00:00.000000Z"
        }
    }
}
```

## Error Codes

| Status Code | Description |
|-------------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Internal Server Error |

## Search Features

### Elasticsearch Integration

The API uses Laravel Scout with Elasticsearch for powerful full-text search capabilities:

- **Fuzzy Matching**: Automatically handles typos and variations
- **Multi-field Search**: Searches across title, content, and author fields
- **Real-time Indexing**: Changes are automatically synced with Elasticsearch
- **Fast Performance**: Optimized for large datasets

### Search Examples

1. **Basic Search:**
   ```
   GET /posts?q=Laravel
   ```

2. **Search with Filters:**
   ```
   GET /posts?q=Scout&author=John&published_after=2024-01-01
   ```

3. **Advanced Search:**
   ```json
   POST /posts/search
   {
       "query": "Elasticsearch integration",
       "author": "Jane Smith",
       "published_after": "2024-01-01T00:00:00Z",
       "sort_by": "created_at",
       "sort_order": "desc"
   }
   ```

## Pagination

All list endpoints support pagination with the following parameters:

- `per_page`: Number of items per page (default: 10, max: 100)
- `page`: Page number (default: 1)

Pagination information is included in the response:

```json
{
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 10,
        "total": 50,
        "from": 1,
        "to": 10
    }
}
```

## Date Formats

All dates should be provided in ISO 8601 format:

```
YYYY-MM-DDTHH:mm:ssZ
```

Examples:
- `2024-01-15T10:00:00Z`
- `2024-01-15T10:00:00.000Z`

## Rate Limiting

Currently, there are no rate limits applied to the API endpoints.

## Testing

You can test the API using the provided Postman collection:

1. Import the `Laravel_Scout_Elasticsearch_API.postman_collection.json` file into Postman
2. Set the `base_url` variable to your server URL (default: `http://localhost:8000`)
3. Run the requests to test all endpoints

## Examples

### cURL Examples

**Get all posts:**
```bash
curl -X GET "http://localhost:8000/api/posts" \
  -H "Accept: application/json"
```

**Create a post:**
```bash
curl -X POST "http://localhost:8000/api/posts" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New Post",
    "content": "Post content",
    "author": "John Doe",
    "published_at": "2024-01-15T10:00:00Z"
  }'
```

**Search posts:**
```bash
curl -X POST "http://localhost:8000/api/posts/search" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Laravel",
    "author": "John Doe",
    "per_page": 5
  }'
```

### JavaScript Examples

**Get all posts:**
```javascript
fetch('http://localhost:8000/api/posts')
  .then(response => response.json())
  .then(data => console.log(data));
```

**Create a post:**
```javascript
fetch('http://localhost:8000/api/posts', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    title: 'New Post',
    content: 'Post content',
    author: 'John Doe',
    published_at: '2024-01-15T10:00:00Z'
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

## Support

For issues or questions about the API, please refer to the main project documentation or create an issue in the project repository. 