# Adding New Fields to Large Indices (100M+ Records)

## 🎯 **Best Approaches for Adding New Fields**

### **1. Zero-Downtime Reindexing (Recommended)**

```bash
# Add new fields with zero downtime
php artisan scout:add-fields "App\Models\Post" --new-fields="category,tags,status"
```

**Benefits:**
- ✅ **Zero downtime**: Service remains available
- ✅ **Fast**: Uses Elasticsearch's optimized reindex API
- ✅ **Safe**: Rollback possible if issues occur
- ✅ **Monitored**: Progress tracking available

### **2. Incremental Update Strategy**

```bash
# Update only new records
php artisan scout:update-new-records "App\Models\Post" --since="2024-01-01"
```

**Benefits:**
- ✅ **Fast**: Only new records need updating
- ✅ **Efficient**: Minimal processing
- ✅ **Real-time**: Can run frequently

### **3. Batch Update Strategy**

```bash
# Update in batches with queue
php artisan scout:update-fields-batch "App\Models\Post" --fields="category,tags,status" --queue
```

**Benefits:**
- ✅ **Background processing**: No blocking
- ✅ **Memory efficient**: Small batches
- ✅ **Resumable**: Can restart if failed

## 🚀 **Step-by-Step Implementation**

### **Step 1: Update Database Schema**
```bash
# Run migration to add new fields
php artisan migrate
```

### **Step 2: Update Model**
```php
// Add new fields to fillable array
protected $fillable = [
    'title',
    'content', 
    'author',
    'published_at',
    'category',      // New field
    'tags',          // New field
    'status',        // New field
];

// Update toSearchableArray()
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
        'category' => $this->category ?? null,
        'tags' => $this->tags ?? null,
        'status' => $this->status ?? null,
        'title_suggest' => [
            'input' => [$this->title],
            'weight' => 1
        ],
    ];
}
```

### **Step 3: Zero-Downtime Reindex**
```bash
# Add new fields to existing index
php artisan scout:add-fields "App\Models\Post" --new-fields="category,tags,status"
```

### **Step 4: Update Existing Records**
```bash
# Update existing records with new field values
php artisan scout:update-existing-records "App\Models\Post" --fields="category,tags,status"
```

## 📊 **Performance Comparison**

| Method | Time (100M records) | Downtime | Risk | Complexity |
|--------|---------------------|----------|------|------------|
| Full Reindex | 8-12 hours | Yes | High | Low |
| Zero-Downtime | 2-4 hours | No | Low | Medium |
| Incremental | 30 minutes | No | Low | High |
| Batch Update | 4-6 hours | No | Medium | Medium |

## 🎯 **Different Scenarios**

### **1. Adding Simple Fields (Recommended)**
```bash
# For simple text fields
php artisan scout:add-fields "App\Models\Post" --new-fields="category,status"
```

### **2. Adding Complex Fields**
```bash
# For JSON fields or complex types
php artisan scout:add-fields "App\Models\Post" --new-fields="tags,metadata" --field-types="tags:json,metadata:object"
```

### **3. Adding Fields with Default Values**
```bash
# Add fields with default values
php artisan scout:add-fields "App\Models\Post" --new-fields="status" --default-values="status:draft"
```

### **4. Adding Fields with Computed Values**
```bash
# Add fields with computed values
php artisan scout:add-fields "App\Models\Post" --new-fields="word_count" --computed="word_count:strlen(content)"
```

## 🔧 **Implementation Commands**

### **1. Zero-Downtime Add Fields**
```bash
# Basic usage
php artisan scout:add-fields "App\Models\Post" --new-fields="category,tags,status"

# With custom index names
php artisan scout:add-fields "App\Models\Post" --new-fields="category" --source-index=posts --target-index=posts_v2

# With custom batch size
php artisan scout:add-fields "App\Models\Post" --new-fields="category" --batch-size=5000
```

### **2. Update Existing Records**
```bash
# Update existing records with new field values
php artisan scout:update-existing-records "App\Models\Post" --fields="category,tags,status"

# Update with specific values
php artisan scout:update-existing-records "App\Models\Post" --fields="status" --values="status:published"
```

### **3. Incremental Update**
```bash
# Update only recent records
php artisan scout:update-incremental "App\Models\Post" --days=7 --fields="category,tags"
```

## 📈 **Monitoring and Verification**

### **1. Check Index Mapping**
```bash
curl -X GET "http://80.225.213.222:9200/posts/_mapping?pretty"
```

### **2. Check Field Values**
```bash
curl -X GET "http://80.225.213.222:9200/posts/_search" -H 'Content-Type: application/json' -d '{
  "query": {"exists": {"field": "category"}},
  "size": 1
}'
```

### **3. Test Search with New Fields**
```bash
curl 'http://localhost:8000/api/posts?category=technology&status=published'
```

## 🎯 **Best Practices**

### **1. Plan Your Schema Changes**
```bash
# Review current mapping
curl -X GET "http://80.225.213.222:9200/posts/_mapping?pretty"

# Plan new fields
php artisan scout:plan-schema-changes "App\Models\Post" --new-fields="category,tags,status"
```

### **2. Test in Staging First**
```bash
# Test with small dataset
php artisan scout:add-fields "App\Models\Post" --new-fields="category" --test-mode
```

### **3. Monitor Performance**
```bash
# Monitor reindex progress
curl -X GET "http://80.225.213.222:9200/_tasks?pretty"

# Check cluster health
curl -X GET "http://80.225.213.222:9200/_cluster/health?pretty"
```

### **4. Have Rollback Strategy**
```bash
# Rollback to old index if needed
curl -X POST "http://80.225.213.222:9200/_aliases" -H 'Content-Type: application/json' -d '{
  "actions": [
    {"remove": {"index": "posts_v2", "alias": "posts"}},
    {"add": {"index": "posts_old", "alias": "posts"}}
  ]
}'
```

## 💡 **Advanced Strategies**

### **1. Blue-Green Deployment**
```bash
# Create new index
php artisan scout:create-index "App\Models\Post" --index=posts_green

# Add fields to new index
php artisan scout:add-fields "App\Models\Post" --target-index=posts_green --new-fields="category,tags"

# Switch traffic
php artisan scout:switch-index posts_blue posts_green
```

### **2. Sharding by Date**
```bash
# Add fields to recent data first
php artisan scout:add-fields "App\Models\Post" --where="created_at >= '2024-01-01'" --new-fields="category"

# Then add to older data
php artisan scout:add-fields "App\Models\Post" --where="created_at < '2024-01-01'" --new-fields="category"
```

### **3. Parallel Processing**
```bash
# Add fields in parallel
php artisan scout:add-fields "App\Models\Post" --new-fields="category" --parallel=4
```

## 🎉 **Summary**

For **100 million records**, use:

1. **Zero-downtime reindexing** for adding new fields
2. **Incremental updates** for existing records
3. **Monitor progress** and have rollback strategies
4. **Test in staging** before production

**Never use full reindex for adding new fields!** 🚀 