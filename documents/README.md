# Laravel Scout with Elasticsearch

A Laravel application demonstrating full-text search functionality using Laravel Scout with Elasticsearch as the search engine.

## Features

- **Automatic Synchronization**: Laravel Scout automatically syncs create, update, and delete operations with Elasticsearch
- Full-text search using Elasticsearch
- RESTful API for CRUD operations
- Real-time search with pagination
- Modern UI with Tailwind CSS
- Sample data seeder

## Prerequisites

- PHP 8.1 or higher
- Composer
- Laravel 12
- Elasticsearch 8.x
- Node.js (for frontend assets)

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd laravel-scout-elasticsearch
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Environment setup

Copy the environment file and configure your settings:

```bash
cp .env.example .env
```

Update the `.env` file with your Elasticsearch configuration:

```env
# Database
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/your/database.sqlite

# Scout Configuration
SCOUT_DRIVER=elasticsearch
SCOUT_QUEUE=false

# Elasticsearch Configuration
ELASTICSEARCH_HOST=localhost:9200
ELASTICSEARCH_INDEX_PREFIX=laravel_scout
ELASTICSEARCH_NUMBER_OF_SHARDS=1
ELASTICSEARCH_NUMBER_OF_REPLICAS=0
```

### 4. Generate application key

```bash
php artisan key:generate
```

### 5. Run database migrations

```bash
php artisan migrate
```

### 6. Seed the database with sample data

```bash
php artisan db:seed
```

### 7. Index the data in Elasticsearch

```bash
php artisan scout:import "App\Models\Post"
```

**Note**: Laravel Scout automatically handles synchronization for create, update, and delete operations. You only need to run this command once to import existing data.

## Elasticsearch Setup

### Using Docker (Recommended)

1. Start Elasticsearch using Docker:

```bash
docker run -d \
  --name elasticsearch \
  -p 9200:9200 \
  -p 9300:9300 \
  -e "discovery.type=single-node" \
  -e "xpack.security.enabled=false" \
  docker.elastic.co/elasticsearch/elasticsearch:8.11.0
```

2. Verify Elasticsearch is running:

```bash
curl http://localhost:9200
```

### Using Homebrew (macOS)

```bash
brew install elasticsearch
brew services start elasticsearch
```

### Manual Installation

Download and install Elasticsearch from the [official website](https://www.elastic.co/downloads/elasticsearch).

## Usage

### Starting the application

```bash
php artisan serve
```

Visit `http://localhost:8000` to access the application.

### Automatic Synchronization

Laravel Scout automatically synchronizes your models with Elasticsearch:

- **Create**: When you create a new post, it's automatically indexed in Elasticsearch
- **Update**: When you update a post, it's automatically re-indexed in Elasticsearch  
- **Delete**: When you delete a post, it's automatically removed from Elasticsearch

No manual intervention required - everything happens automatically!

### API Endpoints

- `GET /api/posts` - List all posts
- `GET /api/posts?q=search_term` - Search posts
- `POST /api/posts` - Create a new post
- `GET /api/posts/{id}` - Get a specific post
- `PUT /api/posts/{id}` - Update a post
- `DELETE /api/posts/{id}` - Delete a post
- `POST /api/posts/search` - Advanced search with filters
- `POST /api/posts/bulk` - Bulk create posts
- `GET /api/posts/stats` - Get post statistics

### Search Examples

```bash
# Search for posts containing "Laravel"
curl "http://localhost:8000/api/posts?q=Laravel"

# Search for posts containing "Elasticsearch"
curl "http://localhost:8000/api/posts?q=Elasticsearch"
```

## Project Structure

```
laravel-scout-elasticsearch/
├── app/
│   ├── Http/Controllers/
│   │   └── PostController.php          # API controller
│   ├── Models/
│   │   └── Post.php                   # Post model with Scout
│   ├── Providers/
│   │   └── ElasticsearchServiceProvider.php  # Elasticsearch service provider
│   └── Services/
│       └── ElasticsearchEngine.php    # Custom Scout engine
├── config/
│   └── scout.php                      # Scout configuration
├── database/
│   ├── migrations/
│   │   └── create_posts_table.php     # Posts table migration
│   └── seeders/
│       └── PostSeeder.php             # Sample data seeder
├── resources/
│   └── views/
│       └── posts/
│           └── index.blade.php        # Frontend interface
└── routes/
    └── web.php                        # Application routes
```

## Customization

### Adding Search to Other Models

1. Add the `Searchable` trait to your model:

```php
use Laravel\Scout\Searchable;

class YourModel extends Model
{
    use Searchable;
    
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            // Add other searchable fields
        ];
    }
    
    public function searchableAs()
    {
        return 'your_model_index';
    }
}
```

2. Import the model data to Elasticsearch (only needed once for existing data):

```bash
php artisan scout:import "App\Models\YourModel"
```

**Note**: After adding the Searchable trait, all create, update, and delete operations will be automatically synchronized with Elasticsearch.

### Customizing Search Queries

Modify the `performSearch` method in `app/Services/ElasticsearchEngine.php` to customize search behavior:

```php
protected function performSearch(Builder $builder, array $options = [])
{
    $query = [
        'index' => $this->index ?: $builder->model->searchableAs(),
        'body' => [
            'query' => [
                'multi_match' => [
                    'query' => $builder->query,
                    'fields' => ['title^2', 'content'], // Boost title field
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO'
                ]
            ],
            'size' => $options['size'] ?? 10,
        ]
    ];
    
    return $this->elasticsearch->search($query);
}
```

## Troubleshooting

### Automatic Synchronization Issues

1. **Check if Scout is properly configured**:
   - Verify `SCOUT_DRIVER=elasticsearch` in your `.env` file
   - Ensure the `Searchable` trait is added to your model

2. **Test synchronization manually**:
   ```php
   // Create a test post
   $post = Post::create(['title' => 'Test', 'content' => 'Test content']);
   
   // Search for it immediately
   $results = Post::search('Test')->get();
   echo $results->count(); // Should be > 0
   ```

### Elasticsearch Connection Issues

1. Verify Elasticsearch is running:
```bash
curl http://localhost:9200
```

2. Check the Elasticsearch logs:
```bash
docker logs elasticsearch
```

### Index Issues

1. Recreate the index:
```bash
php artisan scout:flush "App\Models\Post"
php artisan scout:import "App\Models\Post"
```

2. Check index status:
```bash
curl http://localhost:9200/_cat/indices
```

### Performance Optimization

1. Enable Scout queuing for better performance:
```env
SCOUT_QUEUE=true
```

2. Configure Elasticsearch settings in `config/scout.php`:
```php
'elasticsearch' => [
    'hosts' => [env('ELASTICSEARCH_HOST', 'localhost:9200')],
    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'laravel_scout'),
    'number_of_shards' => env('ELASTICSEARCH_NUMBER_OF_SHARDS', 1),
    'number_of_replicas' => env('ELASTICSEARCH_NUMBER_OF_REPLICAS', 0),
],
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
