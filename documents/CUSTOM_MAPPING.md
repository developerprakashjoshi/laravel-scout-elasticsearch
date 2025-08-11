# Custom Elasticsearch Mapping

## 🎯 **Overview**

Custom mapping allows you to define specific field types, analyzers, and search behaviors for your Elasticsearch indices. This provides better search performance, more accurate results, and advanced features like autocomplete.

## 🔧 **Implementation**

### **1. ElasticsearchEngine Enhancement**

The `ElasticsearchEngine` now supports custom mapping through the `getSearchableMapping()` method:

```php
public function createIndex($name, array $options = [])
{
    $mapping = [];
    
    // Check if model has custom mapping
    if (isset($options['model']) && method_exists($options['model'], 'getSearchableMapping')) {
        $mapping = $options['model']->getSearchableMapping();
    }
    
    $params = [
        'index' => $name
    ];
    
    // Add mapping if provided
    if (!empty($mapping)) {
        $params['body'] = [
            'mappings' => $mapping
        ];
    }
    
    $this->elasticsearch->indices()->create($params);
}
```

### **2. Model Custom Mapping**

Add the `getSearchableMapping()` method to your model:

```php
public function getSearchableMapping()
{
    return [
        'properties' => [
            'id' => [
                'type' => 'integer'
            ],
            'title' => [
                'type' => 'text',
                'analyzer' => 'standard',
                'fields' => [
                    'keyword' => [
                        'type' => 'keyword'
                    ],
                    'suggest' => [
                        'type' => 'completion'
                    ]
                ]
            ],
            'content' => [
                'type' => 'text',
                'analyzer' => 'standard',
                'fields' => [
                    'keyword' => [
                        'type' => 'keyword'
                    ]
                ]
            ],
            'author' => [
                'type' => 'text',
                'analyzer' => 'standard',
                'fields' => [
                    'keyword' => [
                        'type' => 'keyword'
                    ]
                ]
            ],
            'published_at' => [
                'type' => 'date',
                'format' => 'strict_date_optional_time||epoch_millis'
            ],
            'created_at' => [
                'type' => 'date',
                'format' => 'strict_date_optional_time||epoch_millis'
            ],
            'updated_at' => [
                'type' => 'date',
                'format' => 'strict_date_optional_time||epoch_millis'
            ]
        ]
    ];
}
```

## 📊 **Mapping Types Explained**

### **1. Text Fields with Keyword Sub-fields**
```php
'title' => [
    'type' => 'text',
    'analyzer' => 'standard',
    'fields' => [
        'keyword' => [
            'type' => 'keyword'
        ]
    ]
]
```

**Benefits**:
- ✅ **Full-text search**: `title` field supports fuzzy matching
- ✅ **Exact matching**: `title.keyword` for exact term queries
- ✅ **Aggregations**: `title.keyword` for grouping and faceting

### **2. Completion Suggester**
```php
'suggest' => [
    'type' => 'completion',
    'analyzer' => 'simple',
    'preserve_separators' => true,
    'preserve_position_increments' => true,
    'max_input_length' => 50
]
```

**Benefits**:
- ✅ **Autocomplete**: Real-time search suggestions
- ✅ **Fast queries**: Optimized for prefix matching
- ✅ **Customizable**: Configurable analysis settings

### **3. Date Fields**
```php
'published_at' => [
    'type' => 'date',
    'format' => 'strict_date_optional_time||epoch_millis'
]
```

**Benefits**:
- ✅ **Range queries**: `published_at >= 2024-01-01`
- ✅ **Date aggregations**: Group by date ranges
- ✅ **Sorting**: Proper date ordering

### **4. Integer Fields**
```php
'id' => [
    'type' => 'integer'
]
```

**Benefits**:
- ✅ **Exact matching**: Fast ID lookups
- ✅ **Range queries**: `id >= 10 AND id <= 100`
- ✅ **Aggregations**: Min, max, average calculations

## 🚀 **Usage Examples**

### **1. Flush and Reindex with Custom Mapping**
```bash
# Flush existing index
php artisan scout:flush "App\Models\Post"

# Reindex with custom mapping
php artisan scout:import "App\Models\Post"
```

### **2. Verify Mapping**
```bash
curl -X GET "http://80.225.213.222:9200/posts/_mapping?pretty"
```

### **3. Search with Custom Mapping**
```bash
# Full-text search (uses text analyzer)
curl 'http://localhost:8000/api/posts?q=Laravel'

# Exact author match (uses keyword sub-field)
curl 'http://localhost:8000/api/posts?author=John Doe'

# Date range query
curl 'http://localhost:8000/api/posts?published_after=2024-01-01'
```

## 🎯 **Advanced Features**

### **1. Autocomplete Search**
```php
// In your controller
public function autocomplete(Request $request)
{
    $query = $request->get('q');
    
    $response = $this->elasticsearch->search([
        'index' => 'posts',
        'body' => [
            'suggest' => [
                'title_suggest' => [
                    'prefix' => $query,
                    'completion' => [
                        'field' => 'title.suggest',
                        'size' => 5
                    ]
                ]
            ]
        ]
    ]);
    
    return response()->json($response['suggest']['title_suggest'][0]['options']);
}
```

### **2. Aggregations**
```php
// Get author statistics
$response = $this->elasticsearch->search([
    'index' => 'posts',
    'body' => [
        'size' => 0,
        'aggs' => [
            'authors' => [
                'terms' => [
                    'field' => 'author.keyword',
                    'size' => 10
                ]
            ]
        ]
    ]
]);
```

### **3. Complex Queries**
```php
// Multi-field search with boost
$query = [
    'multi_match' => [
        'query' => $searchTerm,
        'fields' => [
            'title^3',      // Boost title matches
            'content^2',    // Boost content matches
            'author'        // Normal weight
        ],
        'type' => 'best_fields',
        'fuzziness' => 'AUTO'
    ]
];
```

## 📈 **Performance Benefits**

### **1. Optimized Search**
- ✅ **Text fields**: Full-text search with stemming
- ✅ **Keyword fields**: Fast exact matches
- ✅ **Date fields**: Efficient range queries

### **2. Reduced Storage**
- ✅ **Appropriate types**: Smaller index size
- ✅ **Efficient encoding**: Optimized for query patterns

### **3. Better Relevance**
- ✅ **Field boosting**: Prioritize important fields
- ✅ **Analyzers**: Language-specific text processing
- ✅ **Fuzzy matching**: Handle typos and variations

## 🔧 **Customization Options**

### **1. Different Analyzers**
```php
'title' => [
    'type' => 'text',
    'analyzer' => 'english',  // English language analyzer
    'fields' => [
        'keyword' => ['type' => 'keyword'],
        'ngram' => [
            'type' => 'text',
            'analyzer' => 'ngram_analyzer'
        ]
    ]
]
```

### **2. Custom Analyzers**
```php
// In your mapping
'settings' => [
    'analysis' => [
        'analyzer' => [
            'ngram_analyzer' => [
                'type' => 'custom',
                'tokenizer' => 'standard',
                'filter' => ['lowercase', 'ngram_filter']
            ]
        ],
        'filter' => [
            'ngram_filter' => [
                'type' => 'ngram',
                'min_gram' => 3,
                'max_gram' => 4
            ]
        ]
    ]
]
```

### **3. Geo Fields**
```php
'location' => [
    'type' => 'geo_point'
]
```

## 🎉 **Summary**

Custom mapping provides:

- ✅ **Better search performance**
- ✅ **More accurate results**
- ✅ **Advanced features** (autocomplete, aggregations)
- ✅ **Flexible configuration**
- ✅ **Type safety** and validation

The implementation is **backward compatible** and **optional** - models without `getSearchableMapping()` will use automatic mapping. 