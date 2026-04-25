@extends('layouts.guru')
@section('title', 'Builder Soal Pribadi')
@section('content')
<div id="builder-app" class="fixed inset-0 z-50 overflow-hidden bg-slate-950/60 p-0 backdrop-blur-sm">
    <div class="flex h-full flex-col overflow-hidden bg-transparent">
        <div class="flex flex-col gap-4 border-b border-white/70 bg-gradient-to-r from-slate-900 via-cyan-900 to-sky-800 px-5 py-5 text-white sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-100">
                    Builder Soal
                </div>
                <div>
                    <h2 class="text-xl font-bold sm:text-2xl">Builder Soal Pribadi</h2>
                    <p class="mt-1 max-w-2xl text-sm text-cyan-50/80">
                        Kelola bank soal guru dalam satu layar dengan warna dan komponen yang konsisten dengan workspace Ujion.
                    </p>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-cyan-50">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-100/80">Jenjang Aktif</div>
                    <div class="mt-1 text-base font-semibold">{{ $user->jenjang }}</div>
                </div>
                <a href="{{ route('guru.personal-questions') }}" class="btn-secondary w-full border-white/20 bg-white/10 text-white hover:bg-white/20 hover:text-white sm:w-auto">Kembali</a>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-row gap-0">
            <div class="min-w-0 flex-1 overflow-y-auto bg-white/92 p-4 sm:p-6 lg:p-7">
                <form @submit.prevent="save">
                    <div v-if="saveError" class="mb-4 rounded-2xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                            <div class="flex-1 min-w-0">@{{ saveError }}</div>
                        </div>
                    </div>
                    <div v-if="saveSuccess" class="mb-4 rounded-2xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check mt-0.5"></i>
                            <div class="flex-1 min-w-0">@{{ saveSuccess }}</div>
                        </div>
                    </div>
                    <div v-if="questions.length" class="space-y-6">
                        <div class="flex flex-col gap-3 rounded-[24px] border border-slate-200/80 bg-white/90 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Editor Aktif</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">Soal @{{ current + 1 }} dari @{{ questions.length }}</div>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <button type="button" class="btn-secondary w-full sm:w-auto" @click="prev" :disabled="current===0">Prev</button>
                                <button type="button" class="btn-secondary w-full sm:w-auto" @click="next" :disabled="current===questions.length-1">Next</button>
                            </div>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr),320px]">
                            <div class="space-y-5">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Tipe Soal</label>
                                        <div class="ssd-wrap mt-1">
                                            <input type="hidden" :value="questions[current].tipe" @change="questions[current].tipe = $event.target.value">
                                            <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                                                <span class="ssd-label">@{{ {PG: 'Pilihan Ganda', Checklist: 'Checklist', Singkat: 'Jawaban Singkat'}[questions[current].tipe] || 'Pilih Tipe' }}</span>
                                                <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                                            </button>
                                            <div class="ssd-panel">
                                                <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari tipe..."></div>
                                                <div class="ssd-list">
                                                    <div class="ssd-option" :class="{'ssd-selected': questions[current].tipe === 'PG'}" data-value="PG">Pilihan Ganda</div>
                                                    <div class="ssd-option" :class="{'ssd-selected': questions[current].tipe === 'Checklist'}" data-value="Checklist">Checklist</div>
                                                    <div class="ssd-option" :class="{'ssd-selected': questions[current].tipe === 'Singkat'}" data-value="Singkat">Jawaban Singkat</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Status</label>
                                        <div class="ssd-wrap mt-1">
                                            <input type="hidden" :value="questions[current].status" @change="questions[current].status = $event.target.value">
                                            <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                                                <span class="ssd-label">@{{ questions[current].status === 'terbit' ? 'Terbit' : 'Draft' }}</span>
                                                <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                                            </button>
                                            <div class="ssd-panel">
                                                <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari status..."></div>
                                                <div class="ssd-list">
                                                    <div class="ssd-option" :class="{'ssd-selected': questions[current].status === 'draft'}" data-value="draft">Draft</div>
                                                    <div class="ssd-option" :class="{'ssd-selected': questions[current].status === 'terbit'}" data-value="terbit">Terbit</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Pertanyaan</label>
                                    <textarea v-model="questions[current].pertanyaan" class="input min-h-[140px] w-full" rows="5"></textarea>
                                </div>

                                <div v-if="['PG','Checklist'].includes(questions[current].tipe)" class="rounded-[24px] border border-slate-200/80 bg-slate-50/70 p-4">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">Opsi Jawaban</div>
                                            <div class="text-xs text-slate-500">Susun pilihan jawaban objektif dengan label A sampai E.</div>
                                        </div>
                                        <button type="button" class="btn-secondary w-full sm:w-auto" @click="addOpsi" :disabled="questions[current].opsi.length >= 5">+ Opsi</button>
                                    </div>
                                    <div class="space-y-2">
                                        <div v-for="(opsi, i) in questions[current].opsi" :key="i" class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:flex-row sm:items-center">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-sm font-semibold text-cyan-700">
                                                @{{ String.fromCharCode(65 + i) }}
                                            </div>
                                            <input v-model="questions[current].opsi[i]" class="input flex-1">
                                            <button type="button" class="btn-danger w-full sm:w-auto" @click="removeOpsi(i)">Hapus</button>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-[11px] text-slate-500">Maksimal 5 opsi.</div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Jawaban Benar</label>
                                        <div v-if="['PG','Checklist'].includes(questions[current].tipe)" class="ssd-wrap mt-1" data-ssd-dynamic>
                                            <input type="hidden" :value="questions[current].jawaban_benar" @change="questions[current].jawaban_benar = $event.target.value">
                                            <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                                                <span class="ssd-label">@{{ questions[current].jawaban_benar || 'Pilih jawaban benar' }}</span>
                                                <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                                            </button>
                                            <div class="ssd-panel">
                                                <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari jawaban..."></div>
                                                <div class="ssd-list">
                                                    <div class="ssd-option" :class="{'ssd-selected': !questions[current].jawaban_benar}" data-value="">Pilih jawaban benar</div>
                                                    <div
                                                        v-for="(opsi, i) in (questions[current].opsi || []).slice(0, 5)"
                                                        :key="`answer-${i}`"
                                                        class="ssd-option"
                                                        :class="{'ssd-selected': questions[current].jawaban_benar === String.fromCharCode(65 + i)}"
                                                        :data-value="String.fromCharCode(65 + i)">
                                                        @{{ String.fromCharCode(65 + i) }} - @{{ opsi || `Opsi ${String.fromCharCode(65 + i)}` }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input v-else v-model="questions[current].jawaban_benar" class="input w-full" placeholder="Tulis jawaban singkat yang benar">
                                        <div class="mt-1 text-[11px] text-slate-500">
                                            <span v-if="['PG','Checklist'].includes(questions[current].tipe)">Pilih huruf jawaban yang benar sesuai opsi aktif.</span>
                                            <span v-else>Isi jawaban teks singkat yang dianggap benar.</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Kategori</label>
                                        <input v-model="questions[current].kategori" class="input w-full">
                                        <div class="mt-1 text-[11px] text-slate-500">Kategori dipakai untuk mengelompokkan soal agar lebih mudah difilter di halaman Bank Soal Pribadi, misalnya Aljabar, Pecahan, atau Literasi.</div>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Pembahasan</label>
                                    <textarea v-model="questions[current].pembahasan" class="input min-h-[120px] w-full"></textarea>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Gambar (opsional)</label>
                                    <input type="file" accept="image/*" class="input w-full" @change="pickImage($event)" :disabled="isSaving || isUploadingImage">
                                    <div class="mt-1 text-[11px] text-slate-500">Maksimal 2 MB. Jika file lebih besar dari 2 MB, sistem akan menolak dan menampilkan pesan error.</div>

                                    <div v-if="questions[current].temp_preview_url || questions[current].image_url" class="mt-3 overflow-hidden rounded-[24px] border border-slate-200 bg-white p-3">
                                        <img :src="questions[current].temp_preview_url || questions[current].image_url" class="max-h-52 rounded-2xl object-contain">
                                        <button type="button" class="btn-danger mt-3 w-full sm:w-auto" @click="clearImage" :disabled="isSaving || isUploadingImage">
                                            Hapus Gambar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <aside class="space-y-4">
                                <div class="rounded-[24px] border border-cyan-100 bg-gradient-to-br from-cyan-50 to-sky-50 p-4">
                                    <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-cyan-700">Info Soal</div>
                                    <div class="mt-3 space-y-3 text-sm text-slate-600">
                                        <div class="rounded-2xl bg-white/80 p-3">
                                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Jenjang</div>
                                            <div class="mt-1 text-base font-semibold text-slate-900">{{ $user->jenjang }}</div>
                                        </div>
                                        <div class="rounded-2xl bg-white/80 p-3">
                                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Tipe Aktif</div>
                                            <div class="mt-1 text-base font-semibold text-slate-900">@{{ questions[current].tipe || 'PG' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-[24px] border border-slate-200/80 bg-white p-4 shadow-sm">
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Preview Soal</label>
                                    <div class="min-h-[220px] rounded-[20px] border border-dashed border-slate-200 bg-slate-50/80 p-4 text-sm leading-7 text-slate-700">
                                        <div v-html="previewSoal(questions[current])"></div>
                                    </div>
                                </div>
                            </aside>
                        </div>

                        <div class="flex flex-col gap-2 border-t border-slate-200/70 pt-5 sm:flex-row sm:justify-between">
                            <button type="button" class="btn-danger w-full sm:w-auto" @click="remove(current)" v-if="questions.length>1">Hapus Soal Ini</button>
                            <button type="submit" class="btn-primary w-full sm:w-auto" :disabled="isSaving">
                                <i class="fa-solid fa-floppy-disk"></i>
                                @{{ isSaving ? 'Menyimpan...' : 'Simpan Semua Soal' }}
                            </button>
                        </div>
                    </div>
                    <div v-else class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50/70 px-6 py-12 text-center">
                        <div class="text-lg font-semibold text-slate-800">Belum ada soal.</div>
                        <p class="mt-2 text-sm text-slate-500">Klik tombol tambah soal untuk mulai menyusun bank soal pribadi Anda.</p>
                    </div>
                </form>
            </div>

            <aside class="w-[360px] shrink-0 overflow-y-auto border-l border-slate-200/70 bg-slate-50/95 p-4 lg:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Navigasi Soal</div>
                        <div class="mt-1 text-lg font-semibold text-slate-900">Daftar Soal</div>
                    </div>
                    <div class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700">
                        @{{ questions.length }} item
                    </div>
                </div>

                <div class="space-y-2">
                    <button
                        v-for="(q, idx) in questions"
                        :key="idx"
                        type="button"
                        @click="go(idx)"
                        class="w-full rounded-2xl border px-4 py-3 text-left transition-all duration-200"
                        :class="idx === current
                            ? 'border-cyan-300 bg-gradient-to-r from-cyan-50 to-sky-50 text-cyan-900 shadow-sm'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-cyan-200 hover:bg-cyan-50/40 hover:text-slate-900'">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold">Soal @{{ idx + 1 }}</div>
                                <div class="mt-1 text-xs uppercase tracking-[0.2em]" :class="idx === current ? 'text-cyan-600' : 'text-slate-400'">
                                    @{{ q.tipe || 'PG' }}
                                </div>
                            </div>
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="q.status === 'terbit'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-amber-100 text-amber-700'">
                                @{{ q.status || 'draft' }}
                            </span>
                        </div>
                        <p class="mt-2 line-clamp-2 text-sm" :class="idx === current ? 'text-slate-700' : 'text-slate-500'">
                            @{{ q.pertanyaan || 'Belum ada pertanyaan. Silakan isi konten soal ini.' }}
                        </p>
                    </button>
                </div>

                <button class="btn-primary mt-4 w-full" @click="add">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Soal
                </button>
            </aside>
        </div>
    </div>
</div>
@php
    $initialQuestions = $questions->map(function ($q) {
        return [
            'tipe' => $q->tipe,
            'pertanyaan' => $q->pertanyaan,
            'opsi' => $q->opsi ?? [],
            'jawaban_benar' => $q->jawaban_benar,
            'pembahasan' => $q->pembahasan,
            'image_path' => $q->image_path,
            'image_url' => $q->image_path ? route('guru.personal-questions.builder.image', ['path' => $q->image_path]) : null,
            'temp_preview_url' => null,
            'kategori' => $q->kategori,
            'status' => $q->status,
        ];
    })->values();
@endphp
<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<script id="personal-question-builder-config" type="application/json">
    {!! json_encode([
        'initialQuestions' => $initialQuestions,
        'saveUrl' => route('guru.personal-questions.builder.save'),
        'uploadImageUrl' => route('guru.personal-questions.builder.upload-image'),
        'csrfToken' => csrf_token(),
    ]) !!}
</script>
@endsection
