<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::query()->readyToPublish()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest('published_at')
            ->paginate(9);

        return view('posts.index', ['posts' => $posts, 'defaultCover' => $this->defaultCover()]);
    }

    public function show(string $slug): View
    {
        $post = Post::query()->readyToPublish()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->firstOrFail();

        $relatedPosts = Post::query()->readyToPublish()->where('status', 'published')->whereKeyNot($post->id)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest('published_at')->limit(3)->get();

        return view('posts.show', ['post' => $post, 'relatedPosts' => $relatedPosts, 'defaultCover' => $this->defaultCover()]);
    }

    private function defaultCover(): string
    {
        $path = 'images/vehicle-placeholder.svg';

        return Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : asset($path);
    }
}
