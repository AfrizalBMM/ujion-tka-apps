<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    public function index(): View
    {
        $testimonials = Testimonial::query()
            ->orderBy('sort_order')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        return view('superadmin.testimonials.index', [
            'testimonials' => $testimonials,
        ]);
    }

    public function create(): View
    {
        return view('superadmin.testimonials.form', [
            'testimonial' => new Testimonial,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'role' => ['nullable', 'string', 'max:191'],
            'content' => ['required', 'string', 'max:5000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->storePublicly('testimonials', 'public')
            : null;

        Testimonial::query()->create([
            'name' => $validated['name'],
            'role' => $validated['role'] ?? null,
            'content' => $validated['content'],
            'rating' => (int) $validated['rating'],
            'photo_path' => $photoPath,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('superadmin.testimonials.index')->with('flash', [
            'type' => 'success',
            'title' => 'Testimoni ditambahkan',
            'message' => 'Testimoni baru sudah disimpan.',
        ]);
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('superadmin.testimonials.form', [
            'testimonial' => $testimonial,
        ]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'role' => ['nullable', 'string', 'max:191'],
            'content' => ['required', 'string', 'max:5000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $photoPath = $testimonial->photo_path;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->storePublicly('testimonials', 'public');

            if ($testimonial->photo_path && $testimonial->photo_path !== $photoPath) {
                Storage::disk('public')->delete($testimonial->photo_path);
            }
        }

        $testimonial->update([
            'name' => $validated['name'],
            'role' => $validated['role'] ?? null,
            'content' => $validated['content'],
            'rating' => (int) $validated['rating'],
            'photo_path' => $photoPath,
            'sort_order' => (int) ($validated['sort_order'] ?? $testimonial->sort_order),
            'is_active' => (bool) ($validated['is_active'] ?? $testimonial->is_active),
        ]);

        return redirect()->route('superadmin.testimonials.index')->with('flash', [
            'type' => 'success',
            'title' => 'Testimoni diperbarui',
            'message' => 'Perubahan testimoni sudah disimpan.',
        ]);
    }

    public function toggle(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update([
            'is_active' => ! $testimonial->is_active,
        ]);

        return redirect()->route('superadmin.testimonials.index')->with('flash', [
            'type' => 'success',
            'title' => 'Status testimoni diperbarui',
            'message' => $testimonial->is_active ? 'Testimoni ditampilkan.' : 'Testimoni disembunyikan.',
        ]);
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        if ($testimonial->photo_path) {
            Storage::disk('public')->delete($testimonial->photo_path);
        }

        $testimonial->delete();

        return redirect()->route('superadmin.testimonials.index')->with('flash', [
            'type' => 'success',
            'title' => 'Testimoni dihapus',
            'message' => 'Testimoni sudah dihapus dari database.',
        ]);
    }
}
