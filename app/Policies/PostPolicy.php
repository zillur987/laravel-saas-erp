<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Read Post');    // Must match 'Read Post' in permissions table
    }

    public function create(User $user): bool
    {
        return $user->can('Create Post');  // Must match 'Create Post' in permissions table
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can('Edit Post') && $user->id === $post->user_id;  // Must match 'Edit Post' in permissions table
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->can('Delete Post') && $user->id === $post->user_id;  // Must match 'Delete Post' in permissions table
    }
}
