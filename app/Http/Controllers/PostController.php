<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    /**
     * Display a listing of the resource with search and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get search query
            $searchQuery = $request->get('q');
            
            // Get filters
            $author = $request->get('author');
            $publishedAfter = $request->get('published_after');
            $publishedBefore = $request->get('published_before');
            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);
            
            // Validate per_page
            if ($perPage > 100) {
                $perPage = 100;
            }
            
            if ($searchQuery) {
                // Search with filters
                $posts = Post::search($searchQuery);
                
                // Apply filters
                if ($author) {
                    // For search queries, we need to handle partial matching differently
                    // since Elasticsearch requires exact matches for term queries
                    // We'll apply author filtering after the search results
                    $searchResults = $posts->paginate($perPage);
                    
                    // Filter results by author (partial match)
                    $filteredResults = collect($searchResults->items());
                    if ($author) {
                        $filteredResults = $filteredResults->filter(function ($post) use ($author) {
                            return stripos($post->author, $author) !== false;
                        });
                    }
                    
                    // Apply date filters
                    if ($publishedAfter) {
                        $filteredResults = $filteredResults->filter(function ($post) use ($publishedAfter) {
                            return $post->published_at && $post->published_at >= $publishedAfter;
                        });
                    }
                    
                    if ($publishedBefore) {
                        $filteredResults = $filteredResults->filter(function ($post) use ($publishedBefore) {
                            return $post->published_at && $post->published_at <= $publishedBefore;
                        });
                    }
                    
                    return response()->json([
                        'success' => true,
                        'data' => $filteredResults->values()->all(),
                        'pagination' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => $perPage,
                            'total' => $filteredResults->count(),
                            'from' => 1,
                            'to' => $filteredResults->count(),
                        ],
                        'filters' => [
                            'search_query' => $searchQuery,
                            'author' => $author,
                            'published_after' => $publishedAfter,
                            'published_before' => $publishedBefore,
                        ]
                    ]);
                } else {
                    // No author filter, apply date filters to Elasticsearch query
                    if ($publishedAfter) {
                        $posts = $posts->where('published_at', '>=', $publishedAfter);
                    }
                    
                    if ($publishedBefore) {
                        $posts = $posts->where('published_at', '<=', $publishedBefore);
                    }
                    
                    $posts = $posts->paginate($perPage);
                }
            } else {
                // Regular query with filters
                $posts = Post::query();
                
                if ($author) {
                    $posts = $posts->where('author', 'like', "%{$author}%");
                }
                
                if ($publishedAfter) {
                    $posts = $posts->where('published_at', '>=', $publishedAfter);
                }
                
                if ($publishedBefore) {
                    $posts = $posts->where('published_at', '<=', $publishedBefore);
                }
                
                $posts = $posts->orderBy('created_at', 'desc')->paginate($perPage);
            }
            
            return response()->json([
                'success' => true,
                'data' => $posts->items(),
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                    'from' => $posts->firstItem(),
                    'to' => $posts->lastItem(),
                ],
                'filters' => [
                    'search_query' => $searchQuery,
                    'author' => $author,
                    'published_after' => $publishedAfter,
                    'published_before' => $publishedBefore,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'author' => 'required|string|max:255',
                'published_at' => 'nullable|date',
            ]);

            $post = Post::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $post
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $post = Post::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $post
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $post = Post::findOrFail($id);
            
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'author' => 'sometimes|required|string|max:255',
                'published_at' => 'nullable|date',
            ]);

            $post->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => $post
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $post = Post::findOrFail($id);
            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get posts statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $totalPosts = Post::count();
            $publishedPosts = Post::whereNotNull('published_at')->count();
            $authors = Post::distinct('author')->count('author');
            $latestPost = Post::latest()->first();
            $oldestPost = Post::oldest()->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_posts' => $totalPosts,
                    'published_posts' => $publishedPosts,
                    'draft_posts' => $totalPosts - $publishedPosts,
                    'unique_authors' => $authors,
                    'latest_post' => $latestPost ? [
                        'id' => $latestPost->id,
                        'title' => $latestPost->title,
                        'created_at' => $latestPost->created_at
                    ] : null,
                    'oldest_post' => $oldestPost ? [
                        'id' => $oldestPost->id,
                        'title' => $oldestPost->title,
                        'created_at' => $oldestPost->created_at
                    ] : null,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk create posts.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'posts' => 'required|array|min:1|max:10',
                'posts.*.title' => 'required|string|max:255',
                'posts.*.content' => 'required|string',
                'posts.*.author' => 'required|string|max:255',
                'posts.*.published_at' => 'nullable|date',
            ]);

            $posts = [];
            foreach ($request->posts as $postData) {
                $posts[] = Post::create($postData);
            }

            return response()->json([
                'success' => true,
                'message' => count($posts) . ' posts created successfully',
                'data' => $posts
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search posts with advanced filters.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'author' => 'nullable|string',
                'published_after' => 'nullable|date',
                'published_before' => 'nullable|date',
                'sort_by' => 'nullable|string|in:created_at,updated_at,published_at,title,author',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            $query = $request->get('query');
            $perPage = $request->get('per_page', 10);
            $author = $request->get('author');
            $publishedAfter = $request->get('published_after');
            $publishedBefore = $request->get('published_before');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $posts = Post::search($query);
            
            // Apply filters
            if ($author) {
                $posts = $posts->where('author', $author);
            }

            $searchResults = $posts->paginate($perPage);

            // Apply date filters after search (since Scout doesn't support range operators)
            $filteredResults = collect($searchResults->items());
            
            if ($publishedAfter) {
                $filteredResults = $filteredResults->filter(function ($post) use ($publishedAfter) {
                    return $post->published_at && $post->published_at >= $publishedAfter;
                });
            }
            
            if ($publishedBefore) {
                $filteredResults = $filteredResults->filter(function ($post) use ($publishedBefore) {
                    return $post->published_at && $post->published_at <= $publishedBefore;
                });
            }

            // Sort results
            if ($sortBy && $sortOrder) {
                $filteredResults = $filteredResults->sortBy($sortBy, SORT_REGULAR, $sortOrder === 'desc');
            }

            return response()->json([
                'success' => true,
                'data' => $filteredResults->values()->all(),
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => $filteredResults->count(),
                    'from' => 1,
                    'to' => $filteredResults->count(),
                ],
                'search_info' => [
                    'query' => $query,
                    'total_results' => $filteredResults->count(),
                    'filters_applied' => [
                        'author' => $author,
                        'published_after' => $publishedAfter,
                        'published_before' => $publishedBefore,
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder,
                    ]
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get autocomplete suggestions for titles.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'required|string|min:1|max:50',
                'size' => 'nullable|integer|min:1|max:20',
            ]);

            $query = $request->get('q');
            $size = $request->get('size', 5);

            $response = app(\Elastic\Elasticsearch\Client::class)->search([
                'index' => 'posts',
                'body' => [
                    'suggest' => [
                        'title_suggest' => [
                            'prefix' => $query,
                            'completion' => [
                                'field' => 'title_suggest',
                                'size' => $size,
                                'skip_duplicates' => true
                            ]
                        ]
                    ]
                ]
            ]);

            $suggestions = collect($response['suggest']['title_suggest'][0]['options'])
                ->map(function ($option) {
                    return [
                        'text' => $option['text'],
                        'score' => $option['_score'] ?? 1.0
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'query' => $query
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting autocomplete suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
