@extends('layouts.superadmin')
@section('title', 'Builder Soal Ujian')
@section('content')
<div id="builder-app" class="fixed inset-0 z-50 flex flex-col bg-gray-900/90">
    <div class="flex flex-col gap-3 bg-white p-4 shadow sm:flex-row sm:items-center sm:justify-between">
        <h2 class="font-bold text-lg sm:text-xl">Builder Soal Ujian: {{ $exam->judul }}</h2>
        <a href="{{ route('superadmin.exams.index') }}" class="btn-secondary w-full sm:w-auto">Keluar</a>
    </div>
    <div class="flex flex-1 flex-col overflow-hidden lg:flex-row">
        <div class="max-h-[350px] bg-white p-4 overflow-y-auto border-b lg:max-h-none lg:w-1/4 lg:border-r lg:border-b-0 flex flex-col">
            <div class="mb-4 font-bold flex items-center justify-between">
                <span>Daftar Soal Ujian</span>
                <span class="text-xs text-muted">@{{ questions.length }} soal</span>
            </div>
            <ul class="flex-1 overflow-y-auto pr-1 space-y-1">
                <li v-for="(q, idx) in questions" :key="idx" class="group">
                    <button @click="go(idx)" 
                            class="w-full text-left px-3 py-2 rounded-lg text-sm transition"
                            :class="idx === current ? 'bg-primary/10 text-primary font-bold border border-primary/20' : 'hover:bg-gray-100 text-gray-700'">
                        <span class="opacity-60 mr-1">#@{{ idx+1 }}</span>
                        <span class="line-clamp-1 inline">@{{ q.pertanyaan || '(Belum ada teks)' }}</span>
                    </button>
                </li>
            </ul>
            <button class="btn-primary mt-4 w-full" @click="add">
                <i class="fa-solid fa-plus mr-2"></i> Tambah Soal Baru
            </button>

            <div class="mt-8 pt-6 border-t border-gray-100">
                <div class="font-bold mb-3 flex items-center justify-between">
                    <span>Import dari Bank Soal</span>
                    <i class="fa-solid fa-magnifying-glass text-xs text-muted"></i>
                </div>
                
                <input v-model="bankSearch" 
                       @input="debounceSearch"
                       placeholder="Cari soal..." 
                       class="input w-full text-sm mb-3">

                <div class="max-h-64 overflow-y-auto border rounded-xl bg-gray-50/50 p-2 space-y-2">
                    <div v-if="loadingBank && bankPage === 1" class="p-4 text-center text-xs text-muted">
                        <i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Mencari...
                    </div>
                    <div v-else-if="bankQuestions.length === 0" class="p-4 text-center text-xs text-muted">
                        Tidak ditemukan soal.
                    </div>
                    <div v-else v-for="bq in bankQuestions" :key="bq.id" class="relative">
                        <label class="flex items-start gap-2 p-2 rounded-lg border border-transparent hover:border-primary/20 hover:bg-white transition cursor-pointer group">
                            <input type="checkbox" v-model="selectedBankIds" :value="bq.id" class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                            <div class="text-xs leading-relaxed line-clamp-2 text-gray-600 group-hover:text-gray-900">
                                <span class="font-bold text-primary mr-1">[@{{ bq.material_mapel || '-' }}]</span>
                                @{{ bq.question_text }}
                            </div>
                        </label>
                    </div>
                    
                    <button v-if="hasMoreBank && !loadingBank" 
                            @click="loadMoreBank"
                            class="w-full py-2 text-xs font-bold text-primary hover:bg-white rounded-lg transition">
                        Muat lebih banyak...
                    </button>
                    <div v-if="loadingBank && bankPage > 1" class="py-2 text-center text-xs text-muted italic">
                        Memuat...
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <div class="text-xs text-muted flex-1">@{{ selectedBankIds.length }} soal dipilih</div>
                    <button @click="importFromBank" 
                            :disabled="selectedBankIds.length === 0 || loadingImport"
                            class="btn-secondary text-xs py-1.5">
                        <i v-if="loadingImport" class="fa-solid fa-circle-notch fa-spin mr-1"></i>
                        Import
                    </button>
                </div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <form @submit.prevent="save">
                <div v-if="questions.length">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                        <span class="font-bold text-lg">Soal @{{ current+1 }} <span class="text-muted font-normal text-sm">dari @{{ questions.length }}</span></span>
                        <div class="flex items-center gap-2">
                            <button type="button" class="btn-secondary px-4" @click="prev" :disabled="current===0">
                                <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                            </button>
                            <button type="button" class="btn-secondary px-4" @click="next" :disabled="current===questions.length-1">
                                Next <i class="fa-solid fa-chevron-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-muted">Materi</label>
                            <div class="ssd-wrap mt-1">
                                <input type="hidden" :value="questions[current].material_id" @change="questions[current].material_id = $event.target.value">
                                <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                                    <span class="ssd-label">@{{ materials.find(m => m.id == questions[current].material_id)?.sub_unit || 'Pilih materi' }}</span>
                                    <i class="fa-solid fa-chevron-down text-[10px] text-muted flex-shrink-0 ssd-icon"></i>
                                </button>
                                <div class="ssd-panel">
                                    <div class="ssd-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="ssd-search" placeholder="Cari materi..."></div>
                                    <div class="ssd-list">
                                        <div class="ssd-option" :class="{'ssd-selected': !questions[current].material_id}" data-value="">Pilih materi</div>
                                        @foreach($materials as $material)
                                            <div class="ssd-option" :class="{'ssd-selected': questions[current].material_id == '{{ $material->id }}'}" data-value="{{ $material->id }}">{{ $material->curriculum }} - {{ $material->sub_unit }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-muted">Tipe Soal</label>
                            <div class="ssd-wrap mt-1">
                                <input type="hidden" :value="questions[current].tipe" @change="questions[current].tipe = $event.target.value">
                                <button type="button" class="ssd-trigger input text-sm flex items-center justify-between gap-2 w-full">
                                    <span class="ssd-label">@{{ {PG: 'Pilihan Ganda', Checklist: 'Checklist', Singkat: 'Jawaban Singkat'}[questions[current].tipe] || 'PG' }}</span>
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
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold uppercase tracking-wider text-muted">Pertanyaan</label>
                        <textarea v-model="questions[current].pertanyaan" class="input w-full mt-1" rows="4" placeholder="Tuliskan teks pertanyaan di sini..."></textarea>
                    </div>
                    <div class="mb-4" v-if="['PG','Checklist'].includes(questions[current].tipe)">
                        <label class="text-xs font-bold uppercase tracking-wider text-muted">Opsi Jawaban</label>
                        <div class="mt-2 space-y-2">
                            <div v-for="(opsi, i) in questions[current].opsi" :key="i" class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">@{{ String.fromCharCode(65 + i) }}</div>
                                <input v-model="questions[current].opsi[i]" class="input flex-1" placeholder="Teks opsi...">
                                <button type="button" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition" @click="removeOpsi(i)" v-if="questions[current].opsi.length > 1">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                            <button type="button" class="text-sm font-bold text-primary hover:underline mt-2 inline-flex items-center gap-1" @click="addOpsi">
                                <i class="fa-solid fa-circle-plus"></i> Tambah Opsi
                            </button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold uppercase tracking-wider text-muted">Jawaban Benar</label>
                        <input v-model="questions[current].jawaban_benar" class="input w-full mt-1" placeholder="Contoh: A (untuk PG) atau teks jawaban (untuk Singkat)">
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold uppercase tracking-wider text-muted">Pembahasan</label>
                        <textarea v-model="questions[current].pembahasan" class="input w-full mt-1" rows="3" placeholder="Opsional: penjelasan jawaban..."></textarea>
                    </div>
                    <div class="mb-6">
                        <label class="text-xs font-bold uppercase tracking-wider text-muted">Gambar Pendukung (URL)</label>
                        <input v-model="questions[current].image" class="input w-full mt-1" placeholder="https://example.com/image.jpg">
                        <div v-if="questions[current].image" class="mt-3 p-2 border border-dashed rounded-xl bg-gray-50 flex justify-center">
                            <img :src="questions[current].image" class="max-h-56 rounded-lg shadow-sm">
                        </div>
                    </div>
                    <div class="mb-8 pt-8 border-t border-gray-100">
                        <label class="text-xs font-bold uppercase tracking-wider text-muted block mb-3">Live Preview</label>
                        <div class="p-6 border border-primary/10 bg-primary/5 rounded-3xl shadow-inner">
                            <div class="prose prose-slate max-w-none" v-html="previewSoal(questions[current])"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-4 sticky bottom-0 bg-white/80 backdrop-blur py-4 border-t mt-8 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                        <button type="button" class="text-rose-500 font-bold text-sm hover:underline" @click="remove(current)" v-if="questions.length>1">
                            <i class="fa-solid fa-trash mr-1"></i> Hapus Soal
                        </button>
                        <div class="flex-1"></div>
                        <button type="submit" class="btn-primary px-8 py-3 shadow-lg shadow-primary/20" :disabled="loadingSave">
                            <i v-if="loadingSave" class="fa-solid fa-circle-notch fa-spin mr-2"></i>
                            <i v-else class="fa-solid fa-floppy-disk mr-2"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
                <div v-else class="h-64 flex items-center justify-center flex-col gap-4">
                    <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center text-gray-300">
                        <i class="fa-solid fa-file-circle-plus text-3xl"></i>
                    </div>
                    <div class="text-center">
                        <div class="font-bold text-gray-900">Belum ada soal ujian</div>
                        <div class="text-sm text-gray-500">Mulai dengan menambah soal baru atau import dari bank soal.</div>
                    </div>
                    <button type="button" class="btn-primary" @click="add">Tambah Soal Pertama</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
<script>
new Vue({
    el: '#builder-app',
    data: {
        questions: @json($questions->map(fn($q)=>[
            'material_id'=>$q->material_id,
            'tipe'=>$q->tipe,
            'pertanyaan'=>$q->pertanyaan,
            'opsi'=>$q->opsi ?? [],
            'jawaban_benar'=>$q->jawaban_benar,
            'pembahasan'=>$q->pembahasan,
            'image'=>$q->image_path,
        ])),
        current: 0,
        materials: @json($materials->map(fn($m)=>['id'=>$m->id,'sub_unit'=>$m->sub_unit])),
        
        bankQuestions: [],
        bankSearch: '',
        bankPage: 1,
        hasMoreBank: false,
        loadingBank: false,
        selectedBankIds: [],
        loadingImport: false,
        loadingSave: false,
        searchTimer: null
    },
    mounted() {
        this.fetchBankQuestions();
    },
    watch: {
        current() {
            this.$nextTick(() => {
                document.querySelectorAll('.ssd-wrap input[type="hidden"]').forEach((input) => {
                    if (window.syncSSD) window.syncSSD(input);
                });
            });
        },
        questions: {
            deep: true,
            handler() {
                this.$nextTick(() => {
                    if (window.initSSD) window.initSSD();
                });
            }
        }
    },
    methods: {
        go(idx){ this.current=idx; },
        next(){ if(this.current<this.questions.length-1) this.current++; },
        prev(){ if(this.current>0) this.current--; },
        add(){ 
            this.questions.push({material_id:'',tipe:'PG',pertanyaan:'',opsi:[''],jawaban_benar:'',pembahasan:'',image:''}); 
            this.current=this.questions.length-1; 
        },
        remove(idx){ 
            if(confirm('Hapus soal ini?')) {
                this.questions.splice(idx,1); 
                if(this.current>=this.questions.length) this.current=this.questions.length-1; 
            }
        },
        addOpsi(){ this.questions[this.current].opsi.push(''); },
        removeOpsi(i){ this.questions[this.current].opsi.splice(i,1); },
        previewSoal(q){
            let html = `<div class="font-bold text-lg mb-4">${q.pertanyaan || '(Teks pertanyaan...)'}</div>`;
            if(['PG','Checklist'].includes(q.tipe)){
                html += '<div class="space-y-2 mb-4">';
                (q.opsi||[]).forEach((o, i) => {
                    html += `<div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded border flex items-center justify-center text-[10px] font-bold">${String.fromCharCode(65+i)}</div>
                        <div>${o || '<span class="text-muted italic">Kosong</span>'}</div>
                    </div>`;
                });
                html += '</div>';
            }
            if(q.image){ html += `<div class="mt-4"><img src='${q.image}' class='max-h-48 rounded-xl'></div>`; }
            return html;
        },
        debounceSearch() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.bankPage = 1;
                this.bankQuestions = [];
                this.fetchBankQuestions();
            }, 500);
        },
        loadMoreBank() {
            this.bankPage++;
            this.fetchBankQuestions();
        },
        fetchBankQuestions() {
            this.loadingBank = true;
            const url = `{{ route('superadmin.exams.bank-questions', $exam) }}?q=${this.bankSearch}&page=${this.bankPage}`;
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    this.bankQuestions = [...this.bankQuestions, ...data.data];
                    this.hasMoreBank = data.current_page < data.last_page;
                    this.loadingBank = false;
                });
        },
        importFromBank() {
            if(confirm(`Import ${this.selectedBankIds.length} soal terpilih ke dalam builder? (Catatan: Perubahan yang belum disimpan akan hilang jika tidak hati-hati)`)) {
                this.loadingImport = true;
                fetch("{{ route('superadmin.exams.import-bank', $exam) }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ global_question_ids: this.selectedBankIds })
                })
                .then(r => {
                    if(r.ok) {
                        alert('Berhasil mengimpor soal. Halaman akan dimuat ulang untuk mengambil data terbaru.');
                        location.reload();
                    } else {
                        alert('Gagal mengimpor soal.');
                        this.loadingImport = false;
                    }
                });
            }
        },
        save(){
            this.loadingSave = true;
            fetch("{{ route('superadmin.exams.builder.save', $exam) }}",{
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body:JSON.stringify({questions:this.questions})
            }).then(() => {
                alert('Semua soal berhasil disimpan!');
                location.reload();
            }).finally(() => {
                this.loadingSave = false;
            });
        }
    }
});
</script>
@endsection
