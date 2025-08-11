# Laravel Scout with Elasticsearch - Automatic Synchronization

This document explains how Laravel Scout automatically synchronizes your models with Elasticsearch for create, update, and delete operations.

## How It Works

Laravel Scout uses model observers to automatically sync your models with Elasticsearch. When you add the `Searchable` trait to your model, Scout automatically:

1. **Indexes new records** when they are created
2. **Updates existing records** when they are modified
3. **Removes records** when they are deleted

## Automatic Operations

### 1. Create Operations

When you create a new Post model:

```php
$post = Post::create([
    'title' => 'New Post',
    'content' => 'Post content',
    'author' => 'John Doe',
]);
```

**What happens automatically:**
- The post is saved to the database
- Laravel Scout automatically indexes it in Elasticsearch
- The post becomes immediately searchable

### 2. Update Operations

When you update an existing Post model:

```php
$post->update([
    'title' => 'Updated Post Title',
    'content' => 'Updated content',
]);
```

**What happens automatically:**
- The post is updated in the database
- Laravel Scout automatically re-indexes it in Elasticsearch
- Search results reflect the changes immediately

### 3. Delete Operations

When you delete a Post model:

```php
$post->delete();
```

**What happens automatically:**
- The post is removed from the database
- Laravel Scout automatically removes it from Elasticsearch
- The post is no longer searchable

## Model Configuration

The Post model is configured with the `Searchable` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    protected $fillable = [
        'title',
        'content',
        'author',
        'published_at',
    ];

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author' => $this->author,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs()
    {
        return 'posts';
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable()
    {
        return true; // Always searchable
    }

    /**
     * Get the value used to index the model.
     */
    public function getScoutKey()
    {
        return $this->id;
    }

    /**
     * Get the key name used to index the model.
     */
    public function getScoutKeyName()
    {
        return 'id';
    }
}
```

## Search Usage

Once your model is configured, you can search it easily:

```php
// Search for posts
$posts = Post::search('Laravel Scout')->get();

// Search with pagination
$posts = Post::search('Elasticsearch')->paginate(10);

// Search with filters
$posts = Post::search('search term')
    ->where('author', 'John Doe')
    ->get();
```

## Testing Synchronization

You can test the automatic synchronization using the provided test scripts:

```bash
# Run the basic synchronization test
php test_sync.php

# Run the detailed synchronization test
php test_sync_detailed.php

# Run the demonstration script
php demo_sync.php
```

## Configuration Options

### Scout Configuration

In `config/scout.php`, you can configure:

```php
'queue' => env('SCOUT_QUEUE', false), // Enable queuing for better performance
'after_commit' => false, // Sync after database transactions are committed
```

### Elasticsearch Configuration

In `config/scout.php`, Elasticsearch settings:

```php
'elasticsearch' => [
    'hosts' => [
        env('ELASTICSEARCH_HOST', 'localhost:9200'),
    ],
    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'laravel_scout'),
    'number_of_shards' => env('ELASTICSEARCH_NUMBER_OF_SHARDS', 1),
    'number_of_replicas' => env('ELASTICSEARCH_NUMBER_OF_REPLICAS', 0),
],
```

## Environment Variables

Set these in your `.env` file:

```env
# Scout Configuration
SCOUT_DRIVER=elasticsearch
SCOUT_QUEUE=false

# Elasticsearch Configuration
ELASTICSEARCH_HOST=localhost:9200
ELASTICSEARCH_INDEX_PREFIX=laravel_scout
```

## Manual Operations

While automatic synchronization handles most cases, you can also perform manual operations:

```php
// Manually index a model
$post->searchable();

// Manually remove from index
$post->unsearchable();

// Import all models to Elasticsearch
php artisan scout:import "App\Models\Post"

// Flush all models from Elasticsearch
php artisan scout:flush "App\Models\Post"
```

## Error Handling

The custom ElasticsearchEngine includes error handling to prevent application crashes:

```php
try {
    $this->elasticsearch->bulk($params);
} catch (\Exception $e) {
    \Log::error('Elasticsearch error: ' . $e->getMessage());
}
```

## Performance Considerations

1. **Enable Queuing**: Set `SCOUT_QUEUE=true` for better performance
2. **Batch Operations**: Use `scout:import` for bulk indexing
3. **Index Optimization**: Configure Elasticsearch settings for your use case
4. **Monitoring**: Monitor Elasticsearch performance and logs

## Troubleshooting

### Common Issues

1. **Synchronization not working**: Check if Scout is properly configured
2. **Search not finding results**: Verify Elasticsearch is running and accessible
3. **Performance issues**: Enable queuing and optimize Elasticsearch settings

### Debugging

1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Check Elasticsearch logs: `docker logs elasticsearch`
3. Test Elasticsearch connection: `curl http://localhost:9200`

## Best Practices

1. **Always use the Searchable trait** for models that need search
2. **Implement toSearchableArray()** to control what data is indexed
3. **Use appropriate index names** with searchableAs()
4. **Monitor performance** and adjust settings accordingly
5. **Handle errors gracefully** in production environments

## Conclusion

Laravel Scout provides seamless automatic synchronization between your Laravel models and Elasticsearch. With minimal configuration, you get powerful full-text search capabilities that stay in sync with your database operations. 