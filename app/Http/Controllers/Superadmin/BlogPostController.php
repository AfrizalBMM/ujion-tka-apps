<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BlogPostController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::query()
            ->orderByDesc('is_published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15);

        $posts->getCollection()->each(function (BlogPost $post) {
            $post->excerpt_limited = Str::limit($post->excerpt, 100);
            $post->published_at_formatted = $post->published_at?->format('d M Y H:i');
        });

        return Inertia::render('Superadmin/Blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Superadmin/Blog/Form', [
            'post' => new BlogPost,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePost($request);

        $slug = ($validated['slug'] ?? null) ?: $this->uniqueSlug($validated['title']);
        $isPublished = $request->boolean('is_published');

        BlogPost::query()->create([
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
        ]);

        return redirect()->route('superadmin.blog.index')->with('flash', [
            'type' => 'success',
            'title' => 'Artikel ditambahkan',
            'message' => $isPublished ? 'Artikel sudah terbit dan tampil di halaman publik.' : 'Artikel disimpan sebagai draf.',
        ]);
    }

    public function edit(BlogPost $blogPost): Response
    {
        return Inertia::render('Superadmin/Blog/Form', [
            'post' => $blogPost,
        ]);
    }

    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $validated = $this->validatePost($request, $blogPost);

        $slug = ($validated['slug'] ?? null) ?: $this->uniqueSlug($validated['title'], $blogPost->id);
        $wasPublished = $blogPost->is_published;
        $isPublished = $request->boolean('is_published');

        $blogPost->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_published' => $isPublished,
            'published_at' => $isPublished
                ? ($wasPublished ? $blogPost->published_at ?? now() : now())
                : null,
        ]);

        return redirect()->route('superadmin.blog.index')->with('flash', [
            'type' => 'success',
            'title' => 'Artikel diperbarui',
            'message' => 'Perubahan artikel sudah disimpan.',
        ]);
    }

    public function toggle(BlogPost $blogPost): RedirectResponse
    {
        $publish = ! $blogPost->is_published;

        $blogPost->update([
            'is_published' => $publish,
            'published_at' => $publish ? $blogPost->published_at ?? now() : null,
        ]);

        return redirect()->route('superadmin.blog.index')->with('flash', [
            'type' => 'success',
            'title' => 'Status artikel diperbarui',
            'message' => $publish ? 'Artikel diterbitkan.' : 'Artikel diubah menjadi draf.',
        ]);
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $blogPost->delete();

        return redirect()->route('superadmin.blog.index')->with('flash', [
            'type' => 'success',
            'title' => 'Artikel dihapus',
            'message' => 'Artikel sudah dihapus dari database.',
        ]);
    }

    private function validatePost(Request $request, ?BlogPost $post = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:220'],
            'slug' => [
                'nullable',
                'string',
                'max:220',
                'alpha_dash',
                Rule::unique('blog_posts', 'slug')->ignore($post?->id),
            ],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'content' => ['required', 'string', 'max:60000'],
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'is_published' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'artikel';
        $slug = $base;
        $counter = 2;

        while (
            BlogPost::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
