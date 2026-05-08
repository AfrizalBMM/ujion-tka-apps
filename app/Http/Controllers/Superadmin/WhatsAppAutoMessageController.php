<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\WaMessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WhatsAppAutoMessageController extends Controller
{
    public function index(): View
    {
        $templates = WaMessageTemplate::query()
            ->orderBy('key')
            ->get();

        return view('superadmin.wa-pesan-otomatis.index', compact('templates'));
    }

    public function create(): View
    {
        $template = new WaMessageTemplate([
            'is_active' => true,
        ]);

        return view('superadmin.wa-pesan-otomatis.form', [
            'template' => $template,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_\-\.]+$/', 'unique:wa_message_templates,key'],
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        WaMessageTemplate::create($validated);

        return redirect()->route('superadmin.wa-templates.index')->with('flash', [
            'type' => 'success',
            'title' => 'Template dibuat',
            'message' => 'Pesan otomatis berhasil ditambahkan.',
        ]);
    }

    public function edit(WaMessageTemplate $template): View
    {
        return view('superadmin.wa-pesan-otomatis.form', [
            'template' => $template,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, WaMessageTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_\-\.]+$/', Rule::unique('wa_message_templates', 'key')->ignore($template->id)],
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $template->update($validated);

        return redirect()->route('superadmin.wa-templates.index')->with('flash', [
            'type' => 'success',
            'title' => 'Template disimpan',
            'message' => 'Perubahan pesan otomatis berhasil disimpan.',
        ]);
    }

    public function toggle(WaMessageTemplate $template): RedirectResponse
    {
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => $template->is_active ? 'Template diaktifkan.' : 'Template dinonaktifkan.',
        ]);
    }

    public function destroy(WaMessageTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Template dihapus.',
        ]);
    }
}
