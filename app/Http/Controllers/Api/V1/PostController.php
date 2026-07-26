<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PostController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Post::class, 'post');
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Post::latest()->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = Post::create([
            ...$request->all(),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post
        ], 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $post
        ]);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);
        
        $post->update([
            'title' => $request->title,
            'content' => $request->content
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post
        ], 200);
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully',
        ]);
    }
}