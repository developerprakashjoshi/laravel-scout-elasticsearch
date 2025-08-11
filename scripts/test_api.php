<?php

echo "Testing Laravel Scout with Elasticsearch API\n";
echo "==========================================\n\n";

$baseUrl = 'http://localhost:8000/api';

// Test 1: Get all posts
echo "1. Testing GET /api/posts...\n";
$response = file_get_contents($baseUrl . '/posts');
if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success']) {
        echo "   ✅ Success! Found " . count($data['data']) . " posts\n";
    } else {
        echo "   ❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   ❌ Failed to connect to API\n";
}

echo "\n";

// Test 2: Create a post
echo "2. Testing POST /api/posts...\n";
$postData = [
    'title' => 'API Test Post',
    'content' => 'This is a test post created via API.',
    'author' => 'API Tester',
    'published_at' => date('c')
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'content' => json_encode($postData)
    ]
]);

$response = file_get_contents($baseUrl . '/posts', false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success']) {
        echo "   ✅ Success! Post created with ID: " . $data['data']['id'] . "\n";
        $postId = $data['data']['id'];
    } else {
        echo "   ❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        $postId = null;
    }
} else {
    echo "   ❌ Failed to create post\n";
    $postId = null;
}

echo "\n";

// Test 3: Get post statistics
echo "3. Testing GET /api/posts/stats...\n";
$response = file_get_contents($baseUrl . '/posts/stats');
if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success']) {
        echo "   ✅ Success! Total posts: " . $data['data']['total_posts'] . "\n";
    } else {
        echo "   ❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   ❌ Failed to get statistics\n";
}

echo "\n";

// Test 4: Search posts
echo "4. Testing POST /api/posts/search...\n";
$searchData = [
    'query' => 'API Test',
    'per_page' => 5
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'content' => json_encode($searchData)
    ]
]);

$response = file_get_contents($baseUrl . '/posts/search', false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success']) {
        echo "   ✅ Success! Found " . count($data['data']) . " posts in search\n";
    } else {
        echo "   ❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "   ❌ Failed to search posts\n";
}

echo "\n";

// Test 5: Delete test post if it was created
if ($postId) {
    echo "5. Testing DELETE /api/posts/{id}...\n";
    $context = stream_context_create([
        'http' => [
            'method' => 'DELETE',
            'header' => [
                'Accept: application/json'
            ]
        ]
    ]);
    
    $response = file_get_contents($baseUrl . '/posts/' . $postId, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['success']) && $data['success']) {
            echo "   ✅ Success! Post deleted\n";
        } else {
            echo "   ❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ❌ Failed to delete post\n";
    }
}

echo "\n🎉 API testing completed!\n";
echo "\nTo test with Postman:\n";
echo "1. Import the Laravel_Scout_Elasticsearch_API.postman_collection.json file\n";
echo "2. Set base_url variable to: http://localhost:8000/api\n";
echo "3. Run the requests to test all endpoints\n"; 