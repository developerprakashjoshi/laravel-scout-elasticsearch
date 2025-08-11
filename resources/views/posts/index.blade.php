<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Scout with Elasticsearch</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Laravel Scout with Elasticsearch</h1>
        
        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form id="searchForm" class="flex gap-4">
                <input type="text" id="searchQuery" placeholder="Search posts..." 
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" 
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Search
                </button>
            </form>
        </div>

        <!-- Create Post Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Create New Post</h2>
            <form id="createForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" name="title" required 
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Content</label>
                    <textarea name="content" required rows="4"
                              class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Author</label>
                    <input type="text" name="author" required 
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Published At</label>
                    <input type="datetime-local" name="published_at" 
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" 
                        class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Create Post
                </button>
            </form>
        </div>

        <!-- Posts List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Posts</h2>
            <div id="postsList" class="space-y-4">
                <!-- Posts will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Load posts on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPosts();
        });

        // Search form handler
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const query = document.getElementById('searchQuery').value;
            loadPosts(query);
        });

        // Create form handler
        document.getElementById('createForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            fetch('/posts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                alert('Post created successfully!');
                e.target.reset();
                loadPosts();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating post');
            });
        });

        function loadPosts(query = '') {
            const url = query ? `/posts?q=${encodeURIComponent(query)}` : '/posts';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const postsList = document.getElementById('postsList');
                    postsList.innerHTML = '';

                    if (data.data && data.data.length > 0) {
                        data.data.forEach(post => {
                            const postElement = document.createElement('div');
                            postElement.className = 'border border-gray-200 rounded-lg p-4';
                            postElement.innerHTML = `
                                <h3 class="text-lg font-semibold text-gray-800">${post.title}</h3>
                                <p class="text-gray-600 mt-2">${post.content}</p>
                                <div class="mt-4 text-sm text-gray-500">
                                    <span>By: ${post.author}</span>
                                    ${post.published_at ? `<span class="ml-4">Published: ${new Date(post.published_at).toLocaleDateString()}</span>` : ''}
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <button onclick="deletePost(${post.id})" 
                                            class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                        Delete
                                    </button>
                                </div>
                            `;
                            postsList.appendChild(postElement);
                        });
                    } else {
                        postsList.innerHTML = '<p class="text-gray-500">No posts found.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('postsList').innerHTML = '<p class="text-red-500">Error loading posts.</p>';
                });
        }

        function deletePost(id) {
            if (confirm('Are you sure you want to delete this post?')) {
                fetch(`/posts/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    alert('Post deleted successfully!');
                    loadPosts();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting post');
                });
            }
        }
    </script>
</body>
</html> 