<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(9);

        return view('artikel.index', [
            'posts' => $posts,
        ]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->is_published, 404);

        $description = $post->meta_description
            ?: $post->excerpt
            ?: Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags(Str::markdown($post->content)))), 160);

        $relatedPosts = BlogPost::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('artikel.show', [
            'post' => $post,
            'description' => $description,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
