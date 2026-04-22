@extends('layouts.superadmin')
@section('title', 'Builder Soal Ujian')
@section('content')
<div id="builder-app" class="fixed inset-0 z-50 flex flex-col bg-gray-900/90">
    <div class="flex flex-col gap-3 bg-white p-4 shadow sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-bold text-lg sm:text-xl">Builder Soal Snapshot: {{ $exam->judul }}</h2>
            <p class="mt-1 text-sm text-textSecondary">Snapshot ini opsional dan terpisah dari 4 bagian utama paket lengkap.</p>
        </div>
        <a href="{{ route('superadmin.exams.index') }}" class="btn-secondary w-full sm:w-auto">Keluar</a>
    </div>
    <div class="flex flex-1 flex-col overflow-hidden lg:flex-row">
        <div class="max-h-[280px] bg-white p-4 overflow-y-auto border-b lg:max-h-none lg:w-1/4 lg:border-r lg:border-b-0">
            <div class="mb-4 font-bold">Daftar Soal</div>
            <ul>
                <li v-for="(q, idx) in questions" :key="idx" :class="{'font-bold text-blue-600': idx === current}">
                    <button @click="go(idx)">Soal @{{ idx+1 }}</button>
                </li>
            </ul>
            <button class="btn-primary mt-4 w-full" @click="add">+ Tambah Soal</button>
            <form method="POST" action="{{ route('superadmin.exams.import-bank', $exam) }}" class="mt-6">
                @csrf
                <div class="font-bold mb-2">Import dari Bank Soal</div>
                <select name="global_question_ids[]" multiple class="input h-32 w-full">
                    @foreach($bankQuestions as $bq)
                        <option value="{{ $bq->id }}">{{ $bq->question_text }}</option>
                    @endforeach
                </select>
                <button class="btn-secondary mt-2 w-full">Import</button>
            </form>
        </div>
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <form @submit.prevent="save">
                <div v-if="questions.length">
                    <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <span class="font-bold">Soal @{{ current+1 }} dari @{{ questions.length }}</span>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <button type="button" class="btn-secondary w-full sm:w-auto" @click="prev" :disabled="current===0">Prev</button>
                            <button type="button" class="btn-secondary w-full sm:w-auto" @click="next" :disabled="current===questions.length-1">Next</button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Materi</label>
                        <select v-model="questions[current].material_id" class="input w-full">
                            <option value="">Pilih materi</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->curriculum }} - {{ $material->sub_unit }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Tipe Soal</label>
                        <select v-model="questions[current].tipe" class="input w-full">
                            <option value="PG">Pilihan Ganda</option>
                            <option value="Checklist">Checklist</option>
                            <option value="Singkat">Jawaban Singkat</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Pertanyaan</label>
                        <textarea v-model="questions[current].pertanyaan" class="input w-full" rows="3"></textarea>
                    </div>
                    <div class="mb-4" v-if="['PG','Checklist'].includes(questions[current].tipe)">
                        <label class="text-xs font-bold">Opsi Jawaban</label>
                        <div v-for="(opsi, i) in questions[current].opsi" :key="i" class="mb-1 flex flex-col gap-2 sm:flex-row">
                            <input v-model="questions[current].opsi[i]" class="input flex-1">
                            <button type="button" class="btn-danger w-full sm:w-auto" @click="removeOpsi(i)">Hapus</button>
                        </div>
                        <button type="button" class="btn-secondary mt-1 w-full sm:w-auto" @click="addOpsi">+ Opsi</button>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Jawaban Benar</label>
                        <input v-model="questions[current].jawaban_benar" class="input w-full">
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Pembahasan</label>
                        <textarea v-model="questions[current].pembahasan" class="input w-full"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Gambar (URL)</label>
                        <input v-model="questions[current].image" class="input w-full">
                        <div v-if="questions[current].image" class="mt-2"><img :src="questions[current].image" class="max-h-40"></div>
                    </div>
                    <div class="mb-4">
                        <label class="text-xs font-bold">Preview Soal</label>
                        <div class="p-4 border bg-gray-50 rounded">
                            <div v-html="previewSoal(questions[current])"></div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="button" class="btn-danger w-full sm:w-auto" @click="remove(current)" v-if="questions.length>1">Hapus Soal Ini</button>
                        <button type="submit" class="btn-primary w-full sm:w-auto">Simpan Semua Soal</button>
                    </div>
                </div>
                <div v-else>
                    <div class="text-center text-gray-500">Belum ada soal. Klik tambah soal.</div>
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
    },
    methods: {
        go(idx){ this.current=idx; },
        next(){ if(this.current<this.questions.length-1) this.current++; },
        prev(){ if(this.current>0) this.current--; },
        add(){ this.questions.push({material_id:'',tipe:'PG',pertanyaan:'',opsi:[''],jawaban_benar:'',pembahasan:'',image:''}); this.current=this.questions.length-1; },
        remove(idx){ this.questions.splice(idx,1); if(this.current>=this.questions.length) this.current=this.questions.length-1; },
        addOpsi(){ this.questions[this.current].opsi.push(''); },
        removeOpsi(i){ this.questions[this.current].opsi.splice(i,1); },
        previewSoal(q){
            let html = `<b>${q.pertanyaan}</b><br>`;
            if(['PG','Checklist'].includes(q.tipe)){
                html += '<ul>' + (q.opsi||[]).map(o=>`<li>${o}</li>`).join('') + '</ul>';
            }
            if(q.image){ html += `<img src='${q.image}' class='max-h-32'>`; }
            return html;
        },
        save(){
            fetch("{{ route('superadmin.exams.builder.save', $exam) }}",{
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body:JSON.stringify({questions:this.questions})
            }).then(()=>location.reload());
        }
    }
});
</script>
@endsection
