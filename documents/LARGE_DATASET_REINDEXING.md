# Large Dataset Reindexing Guide

## 🚫 **Why NOT to Reindex 1B Records**

### **Problems with Full Reindex:**
- ⏰ **Time**: 24-48+ hours for 1B records
- 💾 **Memory**: High memory usage (out of memory errors)
- 🔄 **Downtime**: Service unavailable during reindex
- 💰 **Cost**: High Elasticsearch cluster costs
- ⚡ **Performance**: Slows down other operations
- 🔥 **Risk**: Single point of failure

## ✅ **Better Approaches for Large Datasets**

### **1. Zero-Downtime Reindexing (Recommended)**

```bash
# Use the custom command
php artisan scout:reindex-zero-downtime "App\Models\Post"

# Or manually with Elasticsearch API
curl -X POST "http://80.225.213.222:9200/_reindex" -H 'Content-Type: application/json' -d '{
  "source": {"index": "posts"},
  "dest": {"index": "posts_v2"}
}'
```

**Benefits:**
- ✅ **Zero downtime**: Service remains available
- ✅ **Fast**: Uses Elasticsearch's optimized reindex API
- ✅ **Safe**: Rollback possible if issues occur
- ✅ **Monitored**: Progress tracking available

### **2. Batch Processing with Queue**

```bash
# Process in background with queue
php artisan scout:reindex-large "App\Models\Post" --queue --chunk-size=50000

# Monitor queue
php artisan queue:work --queue=default
```

**Benefits:**
- ✅ **Background processing**: No blocking
- ✅ **Memory efficient**: Small batches
- ✅ **Resumable**: Can restart if failed
- ✅ **Monitored**: Progress tracking

### **3. Incremental Reindexing**

```php
// Only reindex changed records
Post::where('updated_at', '>=', $lastReindexTime)
    ->chunk(1000, function ($chunk) {
        foreach ($chunk as $post) {
            $post->searchable();
        }
    });
```

**Benefits:**
- ✅ **Fast**: Only changed data
- ✅ **Efficient**: Minimal processing
- ✅ **Real-time**: Can run frequently

## 🚀 **Implementation Commands**

### **1. Large Dataset Reindex**
```bash
# Basic large reindex
php artisan scout:reindex-large "App\Models\Post"

# With custom batch sizes
php artisan scout:reindex-large "App\Models\Post" --batch-size=500 --chunk-size=10000

# With queue processing
php artisan scout:reindex-large "App\Models\Post" --queue --chunk-size=50000
```

### **2. Zero-Downtime Reindex**
```bash
# Zero-downtime reindex
php artisan scout:reindex-zero-downtime "App\Models\Post"

# With custom index names
php artisan scout:reindex-zero-downtime "App\Models\Post" --source-index=posts --target-index=posts_v2
```

### **3. Incremental Reindex**
```bash
# Reindex only recent changes
php artisan scout:reindex-incremental "App\Models\Post" --days=7
```

## 📊 **Performance Comparison**

| Method | Time (1B records) | Memory | Downtime | Risk |
|--------|-------------------|---------|----------|------|
| Full Reindex | 24-48 hours | High | Yes | High |
| Zero-Downtime | 2-4 hours | Low | No | Low |
| Batch Queue | 6-12 hours | Medium | No | Medium |
| Incremental | 30 minutes | Low | No | Low |

## 🎯 **Best Practices**

### **1. For Schema Changes**
```bash
# Use zero-downtime reindex
php artisan scout:reindex-zero-downtime "App\Models\Post"
```

### **2. For Data Corrections**
```bash
# Use batch processing
php artisan scout:reindex-large "App\Models\Post" --queue
```

### **3. For Regular Updates**
```bash
# Use incremental reindex
php artisan scout:reindex-incremental "App\Models\Post"
```

### **4. For New Models**
```bash
# Use standard import for small datasets
php artisan scout:import "App\Models\NewModel"

# Use batch for large datasets
php artisan scout:reindex-large "App\Models\NewModel"
```

## 🔧 **Monitoring and Troubleshooting**

### **1. Monitor Progress**
```bash
# Check queue status
php artisan queue:monitor

# Check Elasticsearch tasks
curl -X GET "http://80.225.213.222:9200/_tasks?pretty"
```

### **2. Check Index Status**
```bash
# Check index health
curl -X GET "http://80.225.213.222:9200/_cluster/health?pretty"

# Check index count
curl -X GET "http://80.225.213.222:9200/posts/_count?pretty"
```

### **3. Rollback Strategy**
```bash
# If new index has issues, rollback to old index
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

# Reindex to new index
php artisan scout:reindex-zero-downtime "App\Models\Post" --target-index=posts_green

# Switch traffic
php artisan scout:switch-index posts_blue posts_green
```

### **2. Sharding Strategy**
```bash
# Reindex by date ranges
php artisan scout:reindex-large "App\Models\Post" --where="created_at >= '2024-01-01'"
php artisan scout:reindex-large "App\Models\Post" --where="created_at >= '2024-02-01'"
```

### **3. Parallel Processing**
```bash
# Run multiple reindex jobs in parallel
php artisan scout:reindex-large "App\Models\Post" --parallel=4
```

## 🎉 **Summary**

For **1 billion records**, use:

1. **Zero-downtime reindexing** for schema changes
2. **Batch processing with queue** for data corrections
3. **Incremental reindexing** for regular updates
4. **Monitor progress** and have rollback strategies

**Never use full reindex for large datasets!** 🚀 