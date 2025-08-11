<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;

echo "Starting reindex with sub_category field...\n";

// Flush existing index
echo "Flushing existing index...\n";
Artisan::call('scout:flush', ['model' => 'App\Models\Post']);

// Import with new field
echo "Importing posts with sub_category field...\n";
Artisan::call('scout:import', ['model' => 'App\Models\Post']);

echo "Reindex completed!\n";

// Verify a sample record
$post = Post::first();
echo "Sample post sub_category: " . $post->sub_category . "\n"; 