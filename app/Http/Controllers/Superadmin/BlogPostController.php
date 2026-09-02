<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->orderByDesc('is_published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('superadmin.blog.index', [
            'posts' => $posts,
        ]);
    }

    public function create(): View
    {
        return view('superadmin.blog.form', [
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

    public function edit(BlogPost $blogPost): View
    {
        return view('superadmin.blog.form', [
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
