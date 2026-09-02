@extends('layouts.superadmin')

@section('title', 'Blog / Artikel')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Blog / Artikel</h1>
            <p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
                Kelola artikel edukasi yang tampil di halaman publik <span class="font-mono">/artikel</span>. Artikel terbit akan masuk sitemap dan bisa muncul di hasil pencarian Google.
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.blog.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus mr-2"></i>
                Tambah Artikel
            </a>
        </div>
    </div>

    <div class="card">
        <div class="text-xs font-semibold uppercase tracking-wide text-muted">Daftar</div>
        <div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Artikel</div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-muted">
                        <th class="py-2 pr-4">Judul</th>
                        <th class="py-2 pr-4">Slug</th>
                        <th class="py-2 pr-4">Status</th>
                        <th class="py-2 pr-4">Terbit</th>
                        <th class="py-2 pr-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr class="border-t border-slate-200/60 dark:border-slate-700/60">
                            <td class="py-3 pr-4">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $post->title }}</div>
                                @if (!blank($post->excerpt))
                                    <div class="mt-1 text-xs text-textSecondary dark:text-slate-300">{{ Str::limit($post->excerpt, 100) }}</div>
                                @endif
                            </td>
                            <td class="py-3 pr-4 font-mono text-xs">{{ $post->slug }}</td>
                            <td class="py-3 pr-4">
                                @if ($post->is_published)
                                    <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-200">Terbit</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-500/10 px-2 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200">Draf</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-xs text-textSecondary dark:text-slate-300">
                                {{ $post->published_at?->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="py-3 pr-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('superadmin.blog.edit', $post) }}" class="btn-secondary">
                                        <i class="fa-solid fa-pen mr-2"></i>
                                        Edit
                                    </a>

                                    @if ($post->is_published)
                                        <a href="{{ route('artikel.show', $post) }}" target="_blank" rel="noopener" class="btn-secondary">
                                            <i class="fa-solid fa-arrow-up-right-from-square mr-2"></i>
                                            Lihat
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('superadmin.blog.toggle', $post) }}">
                                        @csrf
                                        <button type="submit" class="btn-secondary">
                                            <i class="fa-solid fa-toggle-{{ $post->is_published ? 'on' : 'off' }} mr-2"></i>
                                            {{ $post->is_published ? 'Jadikan Draf' : 'Terbitkan' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('superadmin.blog.destroy', $post) }}" onsubmit="return confirm('Hapus artikel ini?')">
                                        @csrf
                                        <button type="submit" class="btn-danger">
                                            <i class="fa-solid fa-trash mr-2"></i>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-slate-200/60 dark:border-slate-700/60">
                            <td colspan="5" class="py-6 text-center text-textSecondary dark:text-slate-300">
                                Belum ada artikel. Klik "Tambah Artikel" untuk mulai menulis.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($posts->hasPages())
            <div class="mt-4">{{ $posts->links() }}</div>
        @endif
    </div>
</div>
@endsection
