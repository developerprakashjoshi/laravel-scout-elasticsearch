# Elasticsearch Mapping for Posts Index

## How Mapping is Created

The mapping for the `posts` index in Elasticsearch is created through a combination of automatic and manual processes.

### 1. Automatic Mapping Creation

When you first index a document in Elasticsearch, it automatically creates a mapping based on the data structure. Here's how it works:

#### Data Structure from Post Model

```php
// app/Models/Post.php
public function toSearchableArray()
{
    return [
        'id' => $this->id,                    // integer
        'title' => $this->title,              // string
        'content' => $this->content,           // string
        'author' => $this->author,             // string
        'published_at' => $this->published_at?->toISOString(), // ISO date string
        'created_at' => $this->created_at->toISOString(),      // ISO date string
        'updated_at' => $this->updated_at->toISOString(),      // ISO date string
    ];
}
```

#### Automatic Field Type Detection

Elasticsearch automatically detects field types:

- **`id`**: Detected as `long` (integer)
- **`title`**: Detected as `text` with `keyword` sub-field
- **`content`**: Detected as `text` with `keyword` sub-field
- **`author`**: Detected as `text` with `keyword` sub-field
- **`published_at`**: Detected as `date`
- **`created_at`**: Detected as `date`
- **`updated_at`**: Detected as `date`

### 2. Current Mapping Structure

Based on the actual mapping in Elasticsearch:

```json
{
  "posts": {
    "mappings": {
      "properties": {
        "author": {
          "type": "text",
          "fields": {
            "keyword": {
              "type": "keyword",
              "ignore_above": 256
            }
          }
        },
        "content": {
          "type": "text",
          "fields": {
            "keyword": {
              "type": "keyword",
              "ignore_above": 256
            }
          }
        },
        "created_at": {
          "type": "date"
        },
        "id": {
          "type": "long"
        },
        "published_at": {
          "type": "date"
        },
        "title": {
          "type": "text",
          "fields": {
            "keyword": {
              "type": "keyword",
              "ignore_above": 256
            }
          }
        },
        "updated_at": {
          "type": "date"
        }
      }
    }
  }
}
```

### 3. Manual Mapping Creation

The `ElasticsearchEngine` also provides manual mapping creation in the `flush()` and `createIndex()` methods:

```php
// app/Services/ElasticsearchEngine.php
public function flush($model)
{
    $index = $this->index ?: $model->searchableAs();

    $this->elasticsearch->indices()->delete([
        'index' => $index
    ]);

    $this->elasticsearch->indices()->create([
        'index' => $index,
        'body' => [
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date'],
                ]
            ]
        ]
    ]);
}
```

### 4. Field Type Explanations

#### Text Fields (`title`, `content`, `author`)
- **`type: "text"`**: Full-text searchable, analyzed by Elasticsearch
- **`keyword` sub-field**: Exact match filtering and aggregations
- **`ignore_above: 256`**: Keywords longer than 256 characters are ignored

#### Date Fields (`published_at`, `created_at`, `updated_at`)
- **`type: "date"`**: Proper date handling and range queries
- **ISO 8601 format**: `2024-01-15T10:00:00.000Z`

#### ID Field (`id`)
- **`type: "long"`**: Integer for unique document identification

### 5. Search Capabilities

This mapping enables various search features:

#### Full-Text Search
```json
{
  "query": {
    "multi_match": {
      "query": "Laravel Scout",
      "fields": ["title", "content", "author"]
    }
  }
}
```

#### Exact Match Filtering
```json
{
  "query": {
    "bool": {
      "filter": [
        {"term": {"author.keyword": "John Doe"}}
      ]
    }
  }
}
```

#### Date Range Queries
```json
{
  "query": {
    "range": {
      "published_at": {
        "gte": "2024-01-01T00:00:00Z",
        "lte": "2024-12-31T23:59:59Z"
      }
    }
  }
}
```

### 6. Mapping Management

#### When Mapping is Created

1. **First Document Index**: When you first create a post, Elasticsearch creates the mapping
2. **Manual Flush**: When you run `php artisan scout:flush "App\Models\Post"`
3. **Index Recreation**: When you delete and recreate the index

#### How to View Current Mapping

```bash
# View the current mapping
curl -X GET "http://80.225.213.222:9200/posts/_mapping"

# View index settings
curl -X GET "http://80.225.213.222:9200/posts/_settings"
```

#### How to Update Mapping

```bash
# Add a new field to existing mapping
curl -X PUT "http://80.225.213.222:9200/posts/_mapping" \
  -H "Content-Type: application/json" \
  -d '{
    "properties": {
      "new_field": {
        "type": "text"
      }
    }
  }'
```

### 7. Best Practices

#### Field Naming
- Use snake_case for field names
- Be consistent with naming conventions
- Avoid special characters in field names

#### Data Types
- Use `text` for searchable content
- Use `keyword` for exact matches and aggregations
- Use `date` for timestamps
- Use `long` for IDs and numbers

#### Performance Considerations
- Text fields are analyzed and consume more storage
- Keyword fields are not analyzed and are faster for exact matches
- Date fields support efficient range queries

### 8. Custom Mapping (Optional)

If you want to customize the mapping, you can modify the `flush()` method:

```php
public function flush($model)
{
    $index = $this->index ?: $model->searchableAs();

    $this->elasticsearch->indices()->delete([
        'index' => $index
    ]);

    $this->elasticsearch->indices()->create([
        'index' => $index,
        'body' => [
            'mappings' => [
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ]
                    ],
                    'content' => [
                        'type' => 'text',
                        'analyzer' => 'standard'
                    ],
                    'author' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ]
                    ],
                    'published_at' => ['type' => 'date'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date'],
                ]
            ]
        ]
    ]);
}
```

This mapping structure provides optimal search performance and flexibility for your Laravel Scout with Elasticsearch integration. 