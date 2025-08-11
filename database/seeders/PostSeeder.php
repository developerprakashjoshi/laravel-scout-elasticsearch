<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Getting Started with Laravel Scout',
                'content' => 'Laravel Scout provides a simple, driver-based solution for adding full-text search to your Eloquent models. Using model observers, Scout will automatically keep your search indexes in sync with your Eloquent records.',
                'author' => 'John Doe',
                'published_at' => now(),
            ],
            [
                'title' => 'Elasticsearch Integration Guide',
                'content' => 'Elasticsearch is a distributed, RESTful search and analytics engine capable of addressing a growing number of use cases. As the heart of the Elastic Stack, it centrally stores your data so you can discover the expected and uncover the unexpected.',
                'author' => 'Jane Smith',
                'published_at' => now()->subDays(1),
            ],
            [
                'title' => 'Advanced Search Techniques',
                'content' => 'Learn advanced search techniques including fuzzy matching, phrase queries, and complex aggregations. These techniques will help you build powerful search functionality for your applications.',
                'author' => 'Mike Johnson',
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Performance Optimization Tips',
                'content' => 'Discover best practices for optimizing search performance, including index optimization, query tuning, and caching strategies. These tips will help you build fast and efficient search applications.',
                'author' => 'Sarah Wilson',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Building Search APIs',
                'content' => 'Learn how to build robust search APIs using Laravel and Elasticsearch. This guide covers authentication, rate limiting, and best practices for API design.',
                'author' => 'David Brown',
                'published_at' => now()->subDays(4),
            ],
        ];

        foreach ($posts as $post) {
            Post::create($post);
        }
    }
}
