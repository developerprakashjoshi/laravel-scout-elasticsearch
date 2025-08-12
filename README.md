# Laravel Scout Elasticsearch Driver

A powerful Laravel Scout driver for Elasticsearch with advanced features like zero-downtime reindexing and lazy backfill.

## Features

- **Elasticsearch Integration**: Full integration with Elasticsearch 8.x
- **Zero-Downtime Reindexing**: Update mappings without service interruption
- **Lazy Backfill**: Efficiently add new fields to existing documents
- **Advanced Mapping**: Custom field mapping support
- **Authentication**: Basic authentication and SSL support
- **Artisan Commands**: Built-in commands for common operations

## Installation

```bash
composer require laravel-scout/elasticsearch
```

## Configuration

Add the following to your `.env` file:

```env
SCOUT_DRIVER=elasticsearch
ELASTICSEARCH_HOST=https://your-elasticsearch-host:9200
ELASTICSEARCH_USER=elastic
ELASTICSEARCH_PASS=your-password
ELASTICSEARCH_INDEX_PREFIX=laravel_scout
ELASTICSEARCH_SSL_VERIFICATION=false
```

## Usage

### Basic Setup

1. **Configure Scout** in `config/scout.php`:

```php
'elasticsearch' => [
    'hosts' => [
        env('ELASTICSEARCH_HOST', 'localhost:9200'),
    ],
    'username' => env('ELASTICSEARCH_USER', 'elastic'),
    'password' => env('ELASTICSEARCH_PASS', ''),
    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'laravel_scout'),
    'number_of_shards' => env('ELASTICSEARCH_NUMBER_OF_SHARDS', 1),
    'number_of_replicas' => env('ELASTICSEARCH_NUMBER_OF_REPLICAS', 0),
    'ssl_verification' => env('ELASTICSEARCH_SSL_VERIFICATION', false),
],
```

2. **Make your model searchable**:

```php
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'misc' => $this->misc ?? 'others',
        ];
    }

    public function getSearchableMapping()
    {
        return [
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'content' => ['type' => 'text'],
                'misc' => [
                    'type' => 'text',
                    'analyzer' => 'standard',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
            ]
        ];
    }
}
```

### Artisan Commands

#### Test Connection
```bash
php artisan scout:test-elasticsearch
```

#### Create Index
```bash
php artisan scout:index posts
```

#### Import Data
```bash
php artisan scout:import "App\Models\Post"
```

#### Zero-Downtime Reindex
```bash
php artisan scout:reindex-zero-downtime "App\Models\Post"
```

#### Lazy Backfill (Add New Fields)
```bash
# Add a new field with default value
php artisan scout:lazy-backfill misc --type=text

# Add a keyword field
php artisan scout:lazy-backfill priority --type=keyword

# Force backfill all documents
php artisan scout:lazy-backfill status --type=keyword --force
```

## Advanced Features

### Zero-Downtime Reindexing

This feature allows you to update Elasticsearch mappings without downtime:

1. Creates a new index with updated mapping
2. Reindexes all data from the old index
3. Switches to the new index seamlessly
4. Maintains the original index name

### Lazy Backfill

Efficiently add new fields to existing documents:

1. Adds the field to the index mapping
2. Uses `update_by_query` to populate existing documents
3. Sets default values for new fields
4. No full reindex required

### Custom Field Types

Supported field types:
- `text` - Full-text search with keyword sub-field
- `keyword` - Exact match searches
- `integer` - Numeric searches
- `date` - Date range queries
- `boolean` - Boolean filters
- `float` - Decimal searches

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
