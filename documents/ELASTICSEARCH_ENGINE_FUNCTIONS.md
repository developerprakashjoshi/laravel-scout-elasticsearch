# ElasticsearchEngine Functions: Complete Guide

## 🎯 **Overview**

The `ElasticsearchEngine` class implements Laravel Scout's `Engine` interface, providing all necessary methods for indexing, searching, and managing documents in Elasticsearch.

## 📋 **Function Categories**

### **1. CRUD Operations**
- `update()` - Index/update documents
- `delete()` - Remove documents

### **2. Search Operations**
- `search()` - Basic search
- `paginate()` - Paginated search
- `performSearch()` - Core search implementation

### **3. Result Processing**
- `map()` - Convert search results to models
- `lazyMap()` - Lazy version of map
- `mapIds()` - Extract IDs from results
- `getTotalCount()` - Get total result count

### **4. Index Management**
- `flush()` - Recreate index with mapping
- `createIndex()` - Create new index
- `deleteIndex()` - Delete index

### **5. Helper Functions**
- `filters()` - Convert Scout filters to Elasticsearch queries

---

## 🔄 **1. CRUD Operations**

### **`update($models)` - Index/Update Documents**

**Purpose**: Index new documents or update existing ones in Elasticsearch.

**Flow**:
```php
public function update($models)
{
    if ($models->isEmpty()) {
        return;
    }

    $params['body'] = [];

    $models->each(function ($model) use (&$params) {
        // Step 1: Prepare index metadata
        $params['body'][] = [
            'index' => [
                '_index' => $this->index ?: $model->searchableAs(), // 'posts'
                '_id' => $model->getKey(), // 1, 2, 3...
            ]
        ];

        // Step 2: Add document data
        $params['body'][] = $model->toSearchableArray();
    });

    // Step 3: Send bulk request to Elasticsearch
    try {
        $this->elasticsearch->bulk($params);
    } catch (\Exception $e) {
        \Log::error('Elasticsearch update error: ' . $e->getMessage());
    }
}
```

**Example Request**:
```json
POST /posts/_bulk
{"index": {"_index": "posts", "_id": 1}}
{"id": 1, "title": "My Post", "content": "Hello World", "author": "John Doe"}
{"index": {"_index": "posts", "_id": 2}}
{"id": 2, "title": "Another Post", "content": "Content here", "author": "Jane Smith"}
```

**Usage**:
```php
// Automatic: When you create/update a Post model
Post::create(['title' => 'New Post', 'content' => 'Content']);

// Manual: Force update specific models
Post::where('id', 1)->get()->searchable();
```

### **`delete($models)` - Remove Documents**

**Purpose**: Remove documents from Elasticsearch index.

**Flow**:
```php
public function delete($models)
{
    if ($models->isEmpty()) {
        return;
    }

    $params['body'] = [];

    $models->each(function ($model) use (&$params) {
        // Prepare delete metadata
        $params['body'][] = [
            'delete' => [
                '_index' => $this->index ?: $model->searchableAs(),
                '_id' => $model->getKey(),
            ]
        ];
    });

    // Send bulk delete request
    try {
        $this->elasticsearch->bulk($params);
    } catch (\Exception $e) {
        \Log::error('Elasticsearch delete error: ' . $e->getMessage());
    }
}
```

**Example Request**:
```json
POST /posts/_bulk
{"delete": {"_index": "posts", "_id": 1}}
{"delete": {"_index": "posts", "_id": 2}}
```

**Usage**:
```php
// Automatic: When you delete a Post model
Post::find(1)->delete();

// Manual: Force remove from index
Post::where('id', 1)->get()->unsearchable();
```

---

## 🔍 **2. Search Operations**

### **`search(Builder $builder)` - Basic Search**

**Purpose**: Perform basic search with Scout Builder.

**Flow**:
```php
public function search(Builder $builder)
{
    return $this->performSearch($builder, array_filter([
        'numericFilters' => $this->filters($builder),
        'size' => $builder->limit,
    ]));
}
```

**Usage**:
```php
// Basic search
$results = Post::search('Laravel Scout')->get();

// Search with filters
$results = Post::search('Laravel')
    ->where('author', 'John Doe')
    ->get();
```

### **`paginate(Builder $builder, $perPage, $page)` - Paginated Search**

**Purpose**: Perform paginated search with offset and limit.

**Flow**:
```php
public function paginate(Builder $builder, $perPage, $page)
{
    $result = $this->performSearch($builder, [
        'numericFilters' => $this->filters($builder),
        'from' => ($page - 1) * $perPage,  // Offset
        'size' => $perPage,                 // Limit
    ]);

    $result['nbPages'] = ceil($result['nbHits'] / $perPage);

    return $result;
}
```

**Usage**:
```php
// Paginated search
$results = Post::search('Laravel Scout')->paginate(10, 1);

// Access pagination info
echo "Total pages: " . $results['nbPages'];
echo "Total hits: " . $results['nbHits'];
```

### **`performSearch(Builder $builder, array $options = [])` - Core Search**

**Purpose**: Core search implementation that builds Elasticsearch query.

**Flow**:
```php
protected function performSearch(Builder $builder, array $options = [])
{
    $query = [
        'index' => $this->index ?: $builder->model->searchableAs(),
        'body' => [
            'query' => [
                'multi_match' => [
                    'query' => $builder->query,
                    'fields' => ['*'],           // Search all fields
                    'type' => 'best_fields',     // Best matching fields
                    'fuzziness' => 'AUTO'        // Fuzzy matching
                ]
            ],
            'size' => $options['size'] ?? 10,
        ]
    ];

    // Add pagination offset
    if (isset($options['from'])) {
        $query['body']['from'] = $options['from'];
    }

    // Add filters
    if (isset($options['numericFilters']) && count($options['numericFilters'])) {
        $query['body']['query']['bool']['filter'] = $options['numericFilters'];
    }

    return $this->elasticsearch->search($query);
}
```

**Generated Query Example**:
```json
{
  "index": "posts",
  "body": {
    "query": {
      "bool": {
        "must": [
          {
            "multi_match": {
              "query": "Laravel Scout",
              "fields": ["*"],
              "type": "best_fields",
              "fuzziness": "AUTO"
            }
          }
        ],
        "filter": [
          {"term": {"author.keyword": "John Doe"}}
        ]
      }
    },
    "from": 0,
    "size": 10
  }
}
```

---

## 🔄 **3. Result Processing**

### **`map(Builder $builder, $results, $model)` - Convert Results to Models**

**Purpose**: Convert Elasticsearch search results back to Laravel models.

**Flow**:
```php
public function map(Builder $builder, $results, $model)
{
    if (count($results['hits']['hits']) === 0) {
        return Collection::make();
    }

    // Step 1: Extract IDs from search results
    $keys = collect($results['hits']['hits'])
        ->pluck('_id')
        ->values()
        ->all();

    // Step 2: Fetch models from database
    $models = $model->whereIn(
        $model->getKeyName(),
        $keys
    )->get()->keyBy($model->getKeyName());

    // Step 3: Map results to models in correct order
    return collect($results['hits']['hits'])->map(function ($hit) use ($models) {
        $id = $hit['_id'];

        if (isset($models[$id])) {
            return $models[$id];
        }
    })->filter();
}
```

**Example**:
```php
// Search results from Elasticsearch
$results = [
    'hits' => [
        'hits' => [
            ['_id' => '3', '_score' => 1.5],
            ['_id' => '1', '_score' => 1.2],
            ['_id' => '5', '_score' => 0.8]
        ]
    ]
];

// Converted to Laravel models
$models = [
    Post::find(3),  // Highest score first
    Post::find(1),
    Post::find(5)
];
```

### **`lazyMap(Builder $builder, $results, $model)` - Lazy Version**

**Purpose**: Same as `map()` but returns `LazyCollection` for memory efficiency.

**Usage**:
```php
// For large result sets
$results = Post::search('Laravel')->lazyMap();

foreach ($results as $post) {
    // Process one at a time
    echo $post->title;
}
```

### **`mapIds($results)` - Extract IDs**

**Purpose**: Extract just the IDs from search results.

**Flow**:
```php
public function mapIds($results)
{
    return collect($results['hits']['hits'])->pluck('_id')->values();
}
```

**Usage**:
```php
$ids = Post::search('Laravel')->mapIds();
// Returns: [1, 3, 5, 7]
```

### **`getTotalCount($results)` - Get Total Hits**

**Purpose**: Get the total number of matching documents.

**Flow**:
```php
public function getTotalCount($results)
{
    return $results['hits']['total']['value'] ?? 0;
}
```

**Usage**:
```php
$total = Post::search('Laravel')->getTotalCount();
// Returns: 42
```

---

## 🛠️ **4. Index Management**

### **`flush($model)` - Recreate Index**

**Purpose**: Delete and recreate index with custom mapping.

**Flow**:
```php
public function flush($model)
{
    $index = $this->index ?: $model->searchableAs();

    // Step 1: Delete existing index
    $this->elasticsearch->indices()->delete([
        'index' => $index
    ]);

    // Step 2: Create new index with custom mapping
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

**Usage**:
```bash
# Recreate index with custom mapping
php artisan scout:flush "App\Models\Post"
```

### **`createIndex($name, array $options = [])` - Create Index**

**Purpose**: Create a new index with custom mapping.

**Usage**:
```php
// In your code
$engine = app(ElasticsearchEngine::class);
$engine->createIndex('custom_posts');
```

### **`deleteIndex($name)` - Delete Index**

**Purpose**: Delete an entire index.

**Usage**:
```php
$engine = app(ElasticsearchEngine::class);
$engine->deleteIndex('posts');
```

---

## 🔧 **5. Helper Functions**

### **`filters(Builder $builder)` - Convert Scout Filters**

**Purpose**: Convert Laravel Scout filters to Elasticsearch term queries.

**Flow**:
```php
protected function filters(Builder $builder)
{
    return collect($builder->wheres)->map(function ($value, $key) {
        return ['term' => [$key => $value]];
    })->values()->all();
}
```

**Example**:
```php
// Scout filter
Post::search('Laravel')->where('author', 'John Doe')->get();

// Converts to Elasticsearch query
{
  "bool": {
    "must": [
      {"multi_match": {"query": "Laravel", "fields": ["*"]}}
    ],
    "filter": [
      {"term": {"author.keyword": "John Doe"}}
    ]
  }
}
```

---

## 🎯 **Complete Search Flow Example**

```php
// 1. User performs search
$results = Post::search('Laravel Scout')
    ->where('author', 'John Doe')
    ->paginate(10, 1);

// 2. Scout calls search() method
// 3. search() calls performSearch() with filters
// 4. performSearch() builds Elasticsearch query
// 5. Query sent to Elasticsearch
// 6. Results returned to Scout
// 7. Scout calls map() to convert to models
// 8. Models returned to user
```

## 📊 **Performance Considerations**

- **Bulk operations**: `update()` and `delete()` use bulk API for efficiency
- **Lazy loading**: `lazyMap()` for large result sets
- **Error handling**: All operations wrapped in try-catch
- **Caching**: Models fetched from database in batches
- **Pagination**: Proper offset/limit for large datasets

Each function plays a specific role in the Laravel Scout → Elasticsearch integration, providing a seamless search experience! 